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

public class RFIDListener {
    private static final String PORT_NAME = "COM10";
    private static final int BAUD_RATE = 9600;
    private static final String DB_URL = "jdbc:mysql://localhost:3306/entrysense";
    private static final String DB_USER = "root";
    private static final String DB_PASS = "";

    private static Connection dbConnection;
    private static SerialPort serialPort;

    public static void main(String[] args) {
        System.out.println("🚀 RFID Listener Starting...");

        // Auto-restart loop
        while (true) {
            try {
                initializeSystem();
                startRFIDListening();
            } catch (Exception e) {
                System.err.println("❌ System crash: " + e.getMessage());
                cleanup();
                waitForRestart();
            }
        }
    }

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

        while (true) {
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
            Thread.sleep(10); // Small delay to prevent CPU overuse
        }
    }

    private static void processRFIDScan(String jsonLine) {
        try {
            JSONObject arduinoData = new JSONObject(jsonLine);
            String rfidUid = arduinoData.getString("rfid");
            System.out.println("🎫 RFID Scanned: " + rfidUid);

            JSONObject response = new JSONObject();
            JSONObject details = new JSONObject();

            // 🔍 Lookup RFID info - REMOVED is_enabled condition
            String query = "SELECT * FROM rfid_info WHERE rfid_uid = ?";
            PreparedStatement stmt = dbConnection.prepareStatement(query);
            stmt.setString(1, rfidUid);
            ResultSet rs = stmt.executeQuery();

            if (rs.next()) {
                String role = rs.getString("role");
                int rfid_id = rs.getInt("rfid_id");
                String name = "", department = "", idNumber = "", photo = "";

                if (role.equals("student")) {
                    String studentQuery = """
                                SELECT s.student_id, s.first_name, s.middle_name, s.last_name,
                                       s.course AS department, s.photo
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
                    }
                } else if (role.equals("employee")) {
                    String employeeQuery = """
                                SELECT e.employee_id, e.first_name, e.middle_name, e.last_name,
                                       e.department, e.photo
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
                    }
                } else if (role.equals("guest")) {
                    String guestQuery = """
                                SELECT g.guest_id, g.first_name, g.middle_name, g.last_name,
                                       g.purpose AS department, g.photo
                                FROM rfid_guest_info rgi
                                JOIN guest g ON g.guest_id = rgi.guest_id
                                WHERE rgi.rfid_id = ?
                            """;
                    PreparedStatement ps = dbConnection.prepareStatement(guestQuery);
                    ps.setInt(1, rfid_id);
                    ResultSet guestRs = ps.executeQuery();
                    if (guestRs.next()) {
                        idNumber = guestRs.getString("guest_id");
                        name = formatName(guestRs.getString("first_name"),
                                guestRs.getString("middle_name"),
                                guestRs.getString("last_name"));
                        department = guestRs.getString("department");
                        photo = guestRs.getString("photo");
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
                details.put("status", "Access Granted");

                // Send to Arduino
                response.put("status", "Access Granted");
                response.put("details", details);
                System.out.println("✅ Access Granted: " + response.toString(2));
                serialPort.getOutputStream().write("Access Granted\n".getBytes());

                // Log the access to database
                logAccess(rfidUid, role, "Access Granted", idNumber, name.trim());

                // Send to PHP with photo
                sendToPHP(idNumber, name.trim(), department, role, "Access Granted", photo);

            } else {
                // Unknown RFID - Access Denied
                System.out.println("🔴 ENTERING ACCESS DENIED BLOCK - RFID NOT FOUND IN DATABASE");
                System.out.println("🔴 RFID UID that triggered denial: " + rfidUid);

                // Send to Arduino
                serialPort.getOutputStream().write("Access Denied\n".getBytes());
                System.out.println("❌ Access Denied for unknown RFID: " + rfidUid);

                // Use NULL for role since it's not applicable for denied access
                String deniedRole = null;

                // Log the denied access to database
                System.out.println("📝 Calling logAccess for denied RFID...");
                logAccess(rfidUid, deniedRole, "Access Denied", "UNKNOWN", "Unknown User");

                // Notify PHP of denied access (still use "unknown" for PHP)
                System.out.println("🌐 Calling sendToPHP for denied RFID...");
                sendToPHP("UNKNOWN", "Unknown User", "Access Denied", "unknown", "Access Denied", "");

                System.out.println("🔴 ACCESS DENIED BLOCK COMPLETED");
            }
        } catch (Exception e) {
            System.err.println("❌ Error processing RFID scan: " + e.getMessage());
            e.printStackTrace();
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