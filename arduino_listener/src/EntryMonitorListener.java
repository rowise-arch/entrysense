import com.fazecast.jSerialComm.SerialPort;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.sql.*;
import org.json.JSONObject;

public class EntryMonitorListener {
    public static void main(String[] args) {
        String portName = "COM10";
        int baudRate = 9600;

        String dbUrl = "jdbc:mysql://localhost:3306/entrysense";
        String dbUser = "root";
        String dbPass = "";

        try {
            // Connect to serial
            SerialPort port = SerialPort.getCommPort(portName);
            port.setBaudRate(baudRate);
            port.openPort();
            System.out.println("🎯 EntryMonitorListener started on " + portName);

            // Connect to DB
            Class.forName("com.mysql.cj.jdbc.Driver");
            Connection conn = DriverManager.getConnection(dbUrl, dbUser, dbPass);
            System.out.println("✅ Connected to database.");

            BufferedReader reader = new BufferedReader(new InputStreamReader(port.getInputStream()));
            String line;

            while ((line = reader.readLine()) != null) {
                if (line.trim().startsWith("{") && line.trim().endsWith("}")) {
                    JSONObject data = new JSONObject(line.trim());
                    String rfidUid = data.getString("rfid");
                    System.out.println("🎫 RFID Scanned: " + rfidUid);

                    // Query database for full info
                    JSONObject userData = getUserData(conn, rfidUid);

                    if (userData != null) {
                        System.out.println("📦 Sending to PHP: " + userData.toString());
                        sendToPHP(userData);
                    } else {
                        System.out.println("⚠️ Unknown RFID: " + rfidUid);
                    }
                }
            }

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private static JSONObject getUserData(Connection conn, String rfidUid) throws SQLException {
        String query = "SELECT * FROM rfid_info WHERE rfid_uid = ? AND is_enabled = 1";
        PreparedStatement stmt = conn.prepareStatement(query);
        stmt.setString(1, rfidUid);
        ResultSet rs = stmt.executeQuery();

        if (rs.next()) {
            String role = rs.getString("role");
            int rfid_id = rs.getInt("rfid_id");

            String name = "", department = "", idNumber = "", status = "Access Granted";

            switch (role) {
                case "student" -> {
                    String q = """
                        SELECT s.student_id, s.first_name, s.middle_name, s.last_name, s.course
                        FROM rfid_student_info rsi
                        JOIN student s ON s.student_id = rsi.student_id
                        WHERE rsi.rfid_id = ?
                    """;
                    PreparedStatement ps = conn.prepareStatement(q);
                    ps.setInt(1, rfid_id);
                    ResultSet r2 = ps.executeQuery();
                    if (r2.next()) {
                        idNumber = r2.getString("student_id");
                        name = r2.getString("first_name") + " " +
                               r2.getString("middle_name") + " " +
                               r2.getString("last_name");
                        department = r2.getString("course");
                    }
                }
                case "employee" -> {
                    String q = """
                        SELECT e.employee_id, e.first_name, e.middle_name, e.last_name, e.department
                        FROM rfid_employee_info rei
                        JOIN employee e ON e.employee_id = rei.employee_id
                        WHERE rei.rfid_id = ?
                    """;
                    PreparedStatement ps = conn.prepareStatement(q);
                    ps.setInt(1, rfid_id);
                    ResultSet r2 = ps.executeQuery();
                    if (r2.next()) {
                        idNumber = r2.getString("employee_id");
                        name = r2.getString("first_name") + " " +
                               r2.getString("middle_name") + " " +
                               r2.getString("last_name");
                        department = r2.getString("department");
                    }
                }
                case "guest" -> {
                    String q = """
                        SELECT g.guest_id, g.first_name, g.middle_name, g.last_name, g.purpose
                        FROM rfid_guest_info rgi
                        JOIN guest g ON g.guest_id = rgi.guest_id
                        WHERE rgi.rfid_id = ?
                    """;
                    PreparedStatement ps = conn.prepareStatement(q);
                    ps.setInt(1, rfid_id);
                    ResultSet r2 = ps.executeQuery();
                    if (r2.next()) {
                        idNumber = r2.getString("guest_id");
                        name = r2.getString("first_name") + " " +
                               r2.getString("middle_name") + " " +
                               r2.getString("last_name");
                        department = r2.getString("purpose");
                    }
                }
            }

            JSONObject json = new JSONObject();
            json.put("id_number", idNumber);
            json.put("name", name.trim());
            json.put("course_or_department", department);
            json.put("role", role);
            json.put("status", status);
            return json;
        }
        return null;
    }

    private static void sendToPHP(JSONObject userData) {
    try {
        URL url = new URL("http://localhost/entrysense/PHPFile/update_entry.php");
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setDoOutput(true);
        conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        
        // Convert JSON to POST parameters
        String postData = String.format(
            "id_number=%s&name=%s&department=%s&role=%s&status=%s",
            URLEncoder.encode(userData.getString("id_number"), "UTF-8"),
            URLEncoder.encode(userData.getString("name"), "UTF-8"),
            URLEncoder.encode(userData.optString("course_or_department", ""), "UTF-8"),
            URLEncoder.encode(userData.getString("role"), "UTF-8"),
            URLEncoder.encode(userData.getString("status"), "UTF-8")
        );

        try (OutputStream os = conn.getOutputStream()) {
            os.write(postData.getBytes());
            os.flush();
        }

        int responseCode = conn.getResponseCode();
        if (responseCode == 200) {
            System.out.println("✅ Successfully sent to PHP");
        } else {
            System.out.println("❌ PHP returned error: " + responseCode);
        }

    } catch (Exception e) {
        System.out.println("❌ Error sending to PHP: " + e.getMessage());
    }
}
}
