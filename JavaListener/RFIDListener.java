import com.fazecast.jSerialComm.SerialPort;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.sql.*;
import org.json.JSONObject;
import java.net.ServerSocket;
import java.net.Socket;
import java.time.LocalDate;
import java.time.temporal.ChronoUnit;

public class RFIDListener {
    private static final String PORT_NAME = "COM10";
    private static final int BAUD_RATE = 9600;
    private static final String DB_URL = "jdbc:mysql://localhost:3306/entrysense";
    private static final String DB_USER = "root";
    private static final String DB_PASS = "";
    private static final int CONTROL_PORT = 9090;
    private static final int VALIDITY_MONTHS = 6; // 6 months validity

    private static Connection dbConnection;
    private static SerialPort serialPort;
    private static boolean isRunning = true;

    public static void main(String[] args) {
        System.out.println("🚀 RFID Listener Starting...");
        
        // Start gate control server in a separate thread
        Thread gateControlThread = new Thread(() -> startGateControlServer());
        gateControlThread.setDaemon(true);
        gateControlThread.start();

        // Auto-restart loop for RFID functionality
        while (isRunning) {
            try {
                initializeSystem();
                startRFIDListening();
            } catch (Exception e) {
                System.err.println("❌ System crash: " + e.getMessage());
                cleanup();
                if (isRunning) {
                    waitForRestart();
                }
            }
        }
        
        System.out.println("🛑 RFID Listener stopped gracefully");
    }

    // ===== GATE CONTROL SERVER =====
    private static void startGateControlServer() {
        try (ServerSocket serverSocket = new ServerSocket(CONTROL_PORT)) {
            System.out.println("🎮 Gate Control Server started on port " + CONTROL_PORT);
            
            while (isRunning) {
                try (Socket clientSocket = serverSocket.accept();
                     BufferedReader in = new BufferedReader(new InputStreamReader(clientSocket.getInputStream()));
                     OutputStream out = clientSocket.getOutputStream()) {
                    
                    String command = in.readLine();
                    if (command != null) {
                        System.out.println("🎮 Received gate command: " + command);
                        
                        if ("OPEN".equalsIgnoreCase(command)) {
                            // MANUAL OPEN - Send different command for manual control (no auto-close)
                            sendGateCommand("Access Granted Manual\n");
                            out.write("SUCCESS: Gate opened (manual control)\n".getBytes());
                            System.out.println("🎮 MANUAL OPEN: Sent 'Access Granted Manual' to Arduino - NO AUTO-CLOSE");
                        } else if ("CLOSE".equalsIgnoreCase(command)) {
                            sendGateCommand("Access Denied\n");
                            out.write("SUCCESS: Gate closed\n".getBytes());
                            System.out.println("🎮 MANUAL CLOSE: Sent 'Access Denied' to Arduino");
                        } else if ("STOP".equalsIgnoreCase(command)) {
                            isRunning = false;
                            out.write("SUCCESS: Listener stopping\n".getBytes());
                        } else if ("STATUS".equalsIgnoreCase(command)) {
                            out.write("SUCCESS: RFID Listener running with gate control\n".getBytes());
                        } else {
                            out.write("ERROR: Unknown command\n".getBytes());
                        }
                    }
                } catch (Exception e) {
                    if (isRunning) {
                        System.err.println("🎮 Gate control error: " + e.getMessage());
                    }
                }
            }
        } catch (Exception e) {
            System.err.println("🎮 Failed to start gate control server: " + e.getMessage());
        }
    }

    private static void sendGateCommand(String command) {
        try {
            if (serialPort != null && serialPort.isOpen()) {
                OutputStream outputStream = serialPort.getOutputStream();
                outputStream.write(command.getBytes());
                outputStream.flush();
                System.out.println("🎮 Sent to Arduino: " + command.trim());
            } else {
                System.err.println("🎮 Serial port not available for gate control");
            }
        } catch (Exception e) {
            System.err.println("🎮 Error sending gate command: " + e.getMessage());
        }
    }

    // ===== RFID METHODS =====
    private static void initializeSystem() throws Exception {
        // Initialize Serial Port
        serialPort = SerialPort.getCommPort(PORT_NAME);
        serialPort.setBaudRate(BAUD_RATE);
        serialPort.setComPortTimeouts(SerialPort.TIMEOUT_READ_SEMI_BLOCKING, 0, 0);

        if (!serialPort.openPort()) {
            throw new Exception("Failed to open serial port: " + PORT_NAME);
        }
        System.out.println("✅ Serial port opened: " + PORT_NAME);

        // Initialize Database
        Class.forName("com.mysql.cj.jdbc.Driver");
        dbConnection = DriverManager.getConnection(DB_URL, DB_USER, DB_PASS);
        System.out.println("✅ Database connected.");
    }

    private static void startRFIDListening() throws Exception {
        InputStream in = serialPort.getInputStream();
        StringBuilder buffer = new StringBuilder();

        System.out.println("🔍 Listening for RFID scans...");

        while (isRunning) {
            if (in.available() > 0) {
                char c = (char) in.read();
                if (c == '\n') {
                    String line = buffer.toString().trim();
                    buffer.setLength(0);

                    // Print raw card value
                    System.out.println("📡 Raw card data: " + line);

                    if (line.startsWith("{") && line.endsWith("}")) {
                        processRFIDScan(line);
                    }
                } else {
                    buffer.append(c);
                }
            }
            Thread.sleep(5);
        }
    }

    private static void processRFIDScan(String jsonLine) {
        try {
            JSONObject arduinoData = new JSONObject(jsonLine);
            String rfidUid = arduinoData.getString("rfid");
            System.out.println("🎫 RFID Scanned: " + rfidUid);

            JSONObject response = new JSONObject();
            JSONObject details = new JSONObject();

            // Lookup RFID info
            String query = "SELECT * FROM rfid_info WHERE rfid_uid = ?";
            PreparedStatement stmt = dbConnection.prepareStatement(query);
            stmt.setString(1, rfidUid);
            ResultSet rs = stmt.executeQuery();

            if (rs.next()) {
                String role = rs.getString("role");
                int rfid_id = rs.getInt("rfid_id");
                String name = "", department = "", idNumber = "", photo = "";
                boolean isValid = true;
                String validityStatus = "Valid";

                // Check validity based on role
                if (role.equals("student")) {
                    String studentQuery = """
                                SELECT s.student_id, s.first_name, s.middle_name, s.last_name,
                                       s.course AS department, s.photo, s.created_at
                                FROM rfid_student_info rsi
                                JOIN student s ON s.student_id = rsi.student_id
                                WHERE rsi.rfid_id = ?
                            """;
                    PreparedStatement ps = dbConnection.prepareStatement(studentQuery);
                    ps.setInt(1, rfid_id);
                    ResultSet studentRs = ps.executeQuery();
                    if (studentRs.next()) {
                        idNumber = studentRs.getString("student_id");
                        name = formatName(studentRs.getString("first_name"),
                                studentRs.getString("middle_name"),
                                studentRs.getString("last_name"));
                        department = studentRs.getString("department");
                        photo = studentRs.getString("photo");
                        
                        // Check student validity using created_at
                        Date createdAt = studentRs.getDate("created_at");
                        if (createdAt != null) {
                            isValid = checkValidity(createdAt);
                            if (!isValid) {
                                validityStatus = "Expired - Student ID older than " + VALIDITY_MONTHS + " months";
                            }
                        }
                        
                        System.out.println("✅ STUDENT DATA RETRIEVED SUCCESSFULLY:");
                        System.out.println("   Student ID: " + idNumber);
                        System.out.println("   Name: " + name);
                        System.out.println("   Department: " + department);
                        System.out.println("   Created At: " + createdAt);
                        System.out.println("   Validity: " + (isValid ? "Valid" : "Expired"));
                    }
                } else if (role.equals("employee")) {
                    String employeeQuery = """
                                SELECT e.employee_id, e.first_name, e.middle_name, e.last_name,
                                       e.department, e.photo, e.hire_date
                                FROM rfid_employee_info rei
                                JOIN employee e ON e.employee_id = rei.employee_id
                                WHERE rei.rfid_id = ?
                            """;
                    PreparedStatement ps = dbConnection.prepareStatement(employeeQuery);
                    ps.setInt(1, rfid_id);
                    ResultSet employeeRs = ps.executeQuery();
                    if (employeeRs.next()) {
                        idNumber = employeeRs.getString("employee_id");
                        name = formatName(employeeRs.getString("first_name"),
                                employeeRs.getString("middle_name"),
                                employeeRs.getString("last_name"));
                        department = employeeRs.getString("department");
                        photo = employeeRs.getString("photo");
                        
                        // Check employee validity
                        Date hireDate = employeeRs.getDate("hire_date");
                        if (hireDate != null) {
                            isValid = checkValidity(hireDate);
                            if (!isValid) {
                                validityStatus = "Expired - Employment older than " + VALIDITY_MONTHS + " months";
                            }
                        }
                    }
                } else if (role.equals("guest")) {
                    // PROPER GUEST HANDLING: Use rfid_assignments table
                    String guestQuery = """
                                SELECT g.guest_id, g.first_name, g.middle_name, g.last_name,
                                       g.office, g.photo, g.person_to_visit, g.status,
                                       ra.assigned_at
                                FROM guest g
                                JOIN rfid_assignments ra ON g.guest_id = ra.guest_id
                                WHERE g.status = 'checked_in' 
                                AND ra.rfid_number = ?
                                AND ra.released_at IS NULL
                                AND ra.is_active = true
                            """;
                    PreparedStatement ps = dbConnection.prepareStatement(guestQuery);
                    ps.setString(1, rfidUid);
                    ResultSet guestRs = ps.executeQuery();
                    
                    System.out.println("🔍 Executing guest query for RFID: " + rfidUid);
                    
                    if (guestRs.next()) {
                        idNumber = "G" + guestRs.getString("guest_id");
                        name = formatName(guestRs.getString("first_name"),
                                guestRs.getString("middle_name"),
                                guestRs.getString("last_name"));
                        department = "Guest - Visiting: " + guestRs.getString("person_to_visit");
                        photo = guestRs.getString("photo");
                        
                        // Check guest validity
                        Date assignedAt = guestRs.getDate("assigned_at");
                        if (assignedAt != null) {
                            isValid = checkValidity(assignedAt);
                            if (!isValid) {
                                validityStatus = "Expired - Guest assignment older than " + VALIDITY_MONTHS + " months";
                            }
                        }
                        
                        System.out.println("✅ GUEST DATA RETRIEVED SUCCESSFULLY:");
                        System.out.println("   Guest ID: " + guestRs.getString("guest_id"));
                        System.out.println("   Name: " + name);
                        System.out.println("   Visiting: " + guestRs.getString("person_to_visit"));
                        System.out.println("   Office: " + guestRs.getString("office"));
                        System.out.println("   Status: " + guestRs.getString("status"));
                        System.out.println("   Photo: " + (photo != null && !photo.isEmpty() ? "exists" : "not available"));
                        System.out.println("   Validity: " + (isValid ? "Valid" : "Expired"));
                        
                    } else {
                        System.out.println("❌ GUEST QUERY RETURNED NO RESULTS - CHECKING DATA MISMATCH");
                        
                        // Debug: Check if there's a data type mismatch
                        String debugQuery = "SELECT rfid_number, guest_id FROM rfid_assignments WHERE rfid_number = ?";
                        PreparedStatement debugStmt = dbConnection.prepareStatement(debugQuery);
                        debugStmt.setString(1, rfidUid);
                        ResultSet debugRs = debugStmt.executeQuery();
                        
                        if (debugRs.next()) {
                            String storedRfid = debugRs.getString("rfid_number");
                            String storedGuestId = debugRs.getString("guest_id");
                            System.out.println("   🔍 FOUND IN rfid_assignments:");
                            System.out.println("      Stored RFID: '" + storedRfid + "'");
                            System.out.println("      Searching for: '" + rfidUid + "'");
                            System.out.println("      Stored Guest ID: " + storedGuestId);
                            System.out.println("      Exact match: " + storedRfid.equals(rfidUid));
                            
                            // Check the guest separately
                            String guestCheck = "SELECT first_name, last_name, status FROM guest WHERE guest_id = ?";
                            PreparedStatement guestCheckStmt = dbConnection.prepareStatement(guestCheck);
                            guestCheckStmt.setString(1, storedGuestId);
                            ResultSet guestCheckRs = guestCheckStmt.executeQuery();
                            
                            if (guestCheckRs.next()) {
                                System.out.println("   🔍 GUEST EXISTS:");
                                System.out.println("      Name: " + guestCheckRs.getString("first_name") + " " + guestCheckRs.getString("last_name"));
                                System.out.println("      Status: " + guestCheckRs.getString("status"));
                            }
                        }
                        
                        // Fallback: Try alternative query approach
                        System.out.println("🔄 TRYING ALTERNATIVE QUERY...");
                        String altQuery = """
                                SELECT g.guest_id, g.first_name, g.middle_name, g.last_name,
                                       g.office, g.photo, g.person_to_visit, g.status,
                                       ra.assigned_at
                                FROM rfid_assignments ra
                                JOIN guest g ON ra.guest_id = g.guest_id
                                WHERE ra.rfid_number = ?
                                AND g.status = 'checked_in'
                                AND ra.released_at IS NULL
                            """;
                        PreparedStatement altStmt = dbConnection.prepareStatement(altQuery);
                        altStmt.setString(1, rfidUid);
                        ResultSet altRs = altStmt.executeQuery();
                        
                        if (altRs.next()) {
                            System.out.println("✅ ALTERNATIVE QUERY WORKED!");
                            idNumber = "G" + altRs.getString("guest_id");
                            name = formatName(altRs.getString("first_name"),
                                    altRs.getString("middle_name"),
                                    altRs.getString("last_name"));
                            department = "Guest - Visiting: " + altRs.getString("person_to_visit");
                            photo = altRs.getString("photo");
                            
                            // Check guest validity for alternative query result
                            Date assignedAt = altRs.getDate("assigned_at");
                            if (assignedAt != null) {
                                isValid = checkValidity(assignedAt);
                                if (!isValid) {
                                    validityStatus = "Expired - Guest assignment older than " + VALIDITY_MONTHS + " months";
                                }
                            }
                        } else {
                            System.out.println("❌ ALTERNATIVE QUERY ALSO FAILED");
                            details.put("id_number", "NO_GUEST");
                            details.put("name", "No Active Guest");
                            details.put("course_or_department", "Guest Card Not Assigned");
                            details.put("role", "guest");
                            details.put("photo", "");
                            details.put("status", "Access Denied - No Guest");

                            response.put("status", "Access Denied");
                            response.put("details", details);
                            sendGateCommand("Access Denied\n");
                            logAccess(rfidUid, "guest", "Access Denied - No Guest", "NO_GUEST", "No Active Guest");
                            sendToPHP("NO_GUEST", "No Active Guest", "Guest Card Not Assigned", "guest", "Access Denied - No Guest", "");
                            return;
                        }
                    }
                }

                // Debug photo data
                System.out.println("📸 Photo data length: " + (photo != null ? photo.length() : "null"));
                if (photo != null && photo.length() > 50) {
                    System.out.println("📸 Photo preview: " + photo.substring(0, 50) + "...");
                }

                details.put("id_number", idNumber);
                details.put("name", name.trim());
                details.put("course_or_department", department);
                details.put("role", role);
                details.put("photo", photo != null ? photo : "");
                
                // Set access status based on validity check
                if (isValid) {
                    details.put("status", "Access Granted");
                    response.put("status", "Access Granted");
                    System.out.println("✅ Access Granted: " + response.toString(2));
                    sendGateCommand("Access Granted\n"); // RFID command - 5 seconds auto-close
                    
                    // Log the access to database
                    logAccess(rfidUid, role, "Access Granted", idNumber, name.trim());
                    
                    // Send to PHP with photo
                    sendToPHP(idNumber, name.trim(), department, role, "Access Granted", photo);
                } else {
                    details.put("status", validityStatus);
                    response.put("status", "Access Denied");
                    System.out.println("❌ Access Denied - ID Expired: " + validityStatus);
                    sendGateCommand("Access Denied\n");
                    
                    // Log the denied access to database
                    logAccess(rfidUid, role, validityStatus, idNumber, name.trim());
                    
                    // Send to PHP with expired status
                    sendToPHP(idNumber, name.trim(), department, role, validityStatus, photo);
                }

            } else {
                // Unknown RFID - Access Denied
                System.out.println("🔴 ENTERING ACCESS DENIED BLOCK - RFID NOT FOUND IN DATABASE");
                System.out.println("🔴 RFID UID that triggered denial: " + rfidUid);

                // Send to Arduino
                sendGateCommand("Access Denied\n");
                System.out.println("❌ Access Denied for unknown RFID: " + rfidUid);

                // Use NULL for role since it's not applicable for denied access
                String deniedRole = null;

                // Log the denied access to database
                System.out.println("📝 Calling logAccess for denied RFID...");
                logAccess(rfidUid, deniedRole, "Access Denied", "UNKNOWN", "Unknown User");

                // Notify PHP of denied access
                System.out.println("🌐 Calling sendToPHP for denied RFID...");
                sendToPHP("UNKNOWN", "Unknown User", "Access Denied", "unknown", "Access Denied", "");

                System.out.println("🔴 ACCESS DENIED BLOCK COMPLETED");
            }
        } catch (Exception e) {
            System.err.println("❌ Error processing RFID scan: " + e.getMessage());
            e.printStackTrace();
        }
    }

    // ===== VALIDITY CHECK METHOD =====
    private static boolean checkValidity(Date startDate) {
        try {
            if (startDate == null) {
                return true; // If no date is set, consider it valid
            }
            
            // Convert java.sql.Date to LocalDate
            LocalDate startLocalDate = startDate.toLocalDate();
            LocalDate currentDate = LocalDate.now();
            
            // Calculate months between dates
            long monthsBetween = ChronoUnit.MONTHS.between(startLocalDate, currentDate);
            
            System.out.println("📅 Validity Check:");
            System.out.println("   Start Date: " + startLocalDate);
            System.out.println("   Current Date: " + currentDate);
            System.out.println("   Months Passed: " + monthsBetween);
            System.out.println("   Validity Period: " + VALIDITY_MONTHS + " months");
            System.out.println("   Is Valid: " + (monthsBetween <= VALIDITY_MONTHS));
            
            return monthsBetween <= VALIDITY_MONTHS;
        } catch (Exception e) {
            System.err.println("❌ Error checking validity: " + e.getMessage());
            return true; // In case of error, allow access
        }
    }

    private static String formatName(String first, String middle, String last) {
        return String.format("%s %s %s",
                first != null ? first : "",
                middle != null ? middle : "",
                last != null ? last : "").trim().replaceAll("\\s+", " ");
    }

    private static void logAccess(String rfidUid, String role, String status, String idNumber, String name) {
        try {
            System.out.println("📝 LOG ACCESS METHOD CALLED WITH:");
            System.out.println("   RFID UID: " + rfidUid);
            System.out.println("   Role: " + role);
            System.out.println("   Status: " + status);
            System.out.println("   ID Number: " + idNumber);
            System.out.println("   Name: " + name);

            String logQuery = "INSERT INTO logs (rfid_uid, role, status, id_number, name, scan_time) VALUES (?, ?, ?, ?, ?, NOW())";
            PreparedStatement logStmt = dbConnection.prepareStatement(logQuery);
            logStmt.setString(1, rfidUid);

            // Handle NULL role properly
            if (role == null) {
                logStmt.setNull(2, java.sql.Types.VARCHAR);
            } else {
                logStmt.setString(2, role);
            }

            logStmt.setString(3, status);
            logStmt.setString(4, idNumber);
            logStmt.setString(5, name);

            int rowsAffected = logStmt.executeUpdate();
            System.out.println("✅ LOG ENTRY SUCCESSFUL! Rows affected: " + rowsAffected);

        } catch (Exception e) {
            System.err.println("❌ LOG ENTRY FAILED: " + e.getMessage());
            e.printStackTrace();
        }
    }

    private static void sendToPHP(String idNumber, String name, String department, String role, String status,
            String photo) {
        try {
            URL url = new URL("http://localhost/entrysense/PHPFile/update_entry.php");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setDoOutput(true);
            conn.setRequestProperty("Content-Type", "application/json");
            conn.setConnectTimeout(5000);
            conn.setReadTimeout(5000);

            JSONObject jsonData = new JSONObject();

            // Handle denied access - generate proper ID number
            if ("UNKNOWN".equals(idNumber) && "Access Denied".equals(status)) {
                idNumber = "DENIED_" + System.currentTimeMillis();
            }

            jsonData.put("id_number", idNumber);
            jsonData.put("name", name);
            jsonData.put("department", department);
            jsonData.put("role", role);
            jsonData.put("status", status);

            if (photo != null && !photo.isEmpty()) {
                jsonData.put("photo", photo);
                System.out.println("📤 Including photo data (" + photo.length() + " chars)");
            } else {
                jsonData.put("photo", "");
                System.out.println("⚠️ Photo skipped (null, empty, or too large)");
            }

            String jsonString = jsonData.toString();
            System.out.println("📤 Sending to PHP: " + jsonString);

            try (OutputStream os = conn.getOutputStream()) {
                os.write(jsonString.getBytes());
                os.flush();
            }

            // Read response
            int responseCode = conn.getResponseCode();
            BufferedReader in = new BufferedReader(new InputStreamReader(
                    responseCode == 200 ? conn.getInputStream() : conn.getErrorStream()));

            StringBuilder response = new StringBuilder();
            String line;
            while ((line = in.readLine()) != null) {
                response.append(line);
            }
            in.close();

            System.out.println("🌐 PHP Response (" + responseCode + "): " + response.toString());

            if (responseCode == 200) {
                JSONObject phpResponse = new JSONObject(response.toString());
                if (phpResponse.getBoolean("success")) {
                    System.out.println("✅ Entry monitor updated successfully");
                } else {
                    System.out.println("❌ Entry monitor update failed: " + phpResponse.getString("message"));
                }
            }

            conn.disconnect();

        } catch (Exception e) {
            System.out.println("❌ Error sending to PHP: " + e.getMessage());
        }
    }

    private static void cleanup() {
        try {
            if (serialPort != null && serialPort.isOpen()) {
                serialPort.closePort();
                System.out.println("🔒 Serial port closed");
            }
            if (dbConnection != null && !dbConnection.isClosed()) {
                dbConnection.close();
                System.out.println("🔒 Database connection closed");
            }
        } catch (Exception e) {
            System.err.println("Error during cleanup: " + e.getMessage());
        }
    }

    private static void waitForRestart() {
        try {
            System.out.println("🔄 Restarting in 5 seconds...");
            Thread.sleep(5000);
        } catch (InterruptedException ie) {
            Thread.currentThread().interrupt();
        }
    }
}