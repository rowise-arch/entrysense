# RSU Security Management System

The RSU Security Management System is a comprehensive solution for managing access control using RFID technology. It's designed to monitor and control entry and exit points, manage user data, and provide real-time logging and reporting.

## Features

- **RFID Access Control:** Secure access using RFID cards for students, employees, and guests.
- **Real-time Monitoring:** Live feed of entry and exit events.
- **User Management:** Easily add, edit, and manage user profiles and RFID card assignments.
- **Guest Registration:** A simple interface for registering guests and assigning temporary access.
- **Logging and Reporting:** Detailed logs of all access events, with options to export reports.
- **Dashboard Analytics:** Visual representation of access data and system status.
- **Gate Control:** Manual and automatic control of entry gates.

## System Architecture

The system is composed of three main components:

1.  **Web Interface (PHP):** The frontend is built with HTML, CSS, and JavaScript, with a PHP backend for user authentication, data management, and serving the user interface.
2.  **WebSocket Server (Node.js):** A Node.js server using WebSockets provides real-time communication between the RFID listener and the web interface.
3.  **RFID Listener (Java):** A Java application that communicates with the RFID reader hardware via a serial port, processes the RFID data, and sends it to the WebSocket server.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

- A web server with PHP support (e.g., XAMPP, WAMP)
- MySQL database
- Node.js and npm
- Java Development Kit (JDK)
- Arduino IDE (for the RFID reader)

### Installation

1.  **Database Setup:**
    -   Create a new database named `entrysense` in your MySQL server.
    -   Import the table structure from `entrysense_sql_query.txt`.

2.  **Web Server Configuration:**
    -   Clone this repository into your web server's root directory (e.g., `htdocs` in XAMPP).
    -   Update the database connection details in `Srcipt/db_connect.php` if necessary.

3.  **WebSocket Server:**
    -   Navigate to the project's root directory in your terminal.
    -   Run `npm install` to install the required dependencies.
    -   Start the server with `node server.js`.

4.  **RFID Listener (Java):**
    -   Open the `JavaListener` directory in your preferred Java IDE.
    -   Make sure the `jSerialComm`, `mysql-connector-j`, and `json` libraries are included in the project's build path.
    -   Compile and run `RFIDListener.java`.

### Usage

1.  **Launch the System:** Open your web browser and navigate to the project's URL (e.g., `http://localhost/entrysense`).
2.  **Login:** Use the default admin credentials to log in.
3.  **Dashboard:** The dashboard provides an overview of the system's activity.
4.  **Database Management:** Use the "Database" section to manage students, employees, and guests. You can import data from a CSV file.
5.  **Entry Monitor:** View real-time access events in the "Entry Monitor" section.
6.  **Logs:** Access detailed logs of all RFID scans in the "RFID Logs" section.
7.  **Register Guest:** Register new guests and assign them temporary RFID cards.
8.  **Control Panel:** Manually control the entry gate.
