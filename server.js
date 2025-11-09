const WebSocket = require('ws');
const net = require('net');

// Create WebSocket server
const wss = new WebSocket.Server({ port: 8080 });

// Store connected WebSocket clients
const clients = new Set();

// Handle WebSocket connections
wss.on('connection', function connection(ws) {
    clients.add(ws);
    
    ws.on('close', () => {
        clients.delete(ws);
    });
});

// Broadcast message to all connected clients
function broadcast(data) {
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(data);
        }
    });
}

// Create TCP server to receive data from Java application
const tcpServer = net.createServer((socket) => {
    socket.on('data', (data) => {
        broadcast(data.toString());
    });
});

tcpServer.listen(3000, () => {
    console.log('TCP Server listening on port 3000');
    console.log('WebSocket Server listening on port 8080');
});

// In the RFIDListener.java, modify the response sending part:
// ...existing code...
Socket socket = new Socket("localhost", 3000);
socket.getOutputStream().write((response.toString() + "\n").getBytes());
socket.close();
// ...existing code...