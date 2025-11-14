// ../JavaListener/GateControl.java
import com.fazecast.jSerialComm.SerialPort;

public class GateControl {
    private static final String PORT_NAME = "COM10";
    private static final int BAUD_RATE = 9600;
    
    public static void main(String[] args) {
        if (args.length < 1) {
            System.out.println("Usage: java GateControl <OPEN|CLOSE>");
            return;
        }
        
        String action = args[0].toUpperCase();
        SerialPort serialPort = SerialPort.getCommPort(PORT_NAME);
        serialPort.setBaudRate(BAUD_RATE);
        serialPort.setComPortTimeouts(SerialPort.TIMEOUT_WRITE_BLOCKING, 1000, 0);
        
        if (!serialPort.openPort()) {
            System.err.println("Failed to open serial port: " + PORT_NAME);
            return;
        }
        
        try {
            String command;
            if ("OPEN".equals(action)) {
                command = "Access Granted\n";
            } else if ("CLOSE".equals(action)) {
                command = "Access Denied\n";
            } else {
                System.err.println("Invalid action: " + action);
                return;
            }
            
            byte[] data = command.getBytes();
            int bytesWritten = serialPort.writeBytes(data, data.length);
            
            if (bytesWritten == data.length) {
                System.out.println("Gate command sent: " + action);
            } else {
                System.err.println("Failed to write complete command");
            }
            
            Thread.sleep(100); // Small delay
            
        } catch (Exception e) {
            System.err.println("Error: " + e.getMessage());
        } finally {
            serialPort.closePort();
        }
    }
}