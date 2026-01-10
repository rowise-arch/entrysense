# EntrySense Installation Guide

This guide provides step-by-step instructions for setting up the EntrySense RFID Access Control System.

## Prerequisites

Before you begin, ensure you have the following software installed on your system:

*   **XAMPP:** A web server solution that includes Apache, MySQL, and PHP.
*   **Node.js:** A JavaScript runtime environment.
*   **Java Development Kit (JDK):** Required to run the Java-based RFID listener.

## Installation Steps

### 1. Database Setup

1.  **Start Apache and MySQL:** Open the XAMPP control panel and start the Apache and MySQL services.
2.  **Create the Database:**
    *   Open your web browser and navigate to `http://localhost/phpmyadmin`.
    *   Create a new database named `entrysense_new`.
3.  **Import the Database Schema:**
    *   Select the `entrysense_new` database in phpMyAdmin.
    *   Click on the **Import** tab.
    *   Upload the `entrysense_sql_query.txt` file and click **Go**.

### 2. Web Interface Setup

1.  **Clone the Repository:** Clone the EntrySense repository to the `htdocs` directory in your XAMPP installation folder (e.g., `C:/xampp/htdocs`).
2.  **Verify Configuration:** Ensure the database credentials in `Srcipt/db_connect.php` match your MySQL setup. By default, the username is `root` and the password is an empty string.

### 3. Node.js Server Setup

1.  **Install Dependencies:**
    *   Open a terminal or command prompt.
    *   Navigate to the `JavaListener` directory within the project folder.
    *   Run the command `npm install` to install the required Node.js packages.
2.  **Start the Server:**
    *   In the same terminal, run the command `node server.js`.
    *   The WebSocket server will start on port 8080, and the TCP server will start on port 3000.

### 4. Java RFID Listener Setup

1.  **Configure the COM Port:**
    *   Open the `JavaListener/RFIDListener.java` file.
    *   Modify the `PORT_NAME` variable to match the COM port your RFID reader is connected to.
2.  **Compile and Run the Listener:**
    *   Open a terminal or command prompt.
    *   Navigate to the `JavaListener` directory.
    *   **To compile:** `javac -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener.java`
    *   **To run:** `java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener`

## Accessing the Application

Once all the components are running, you can access the EntrySense web interface by navigating to `http://localhost/entrysense` in your web browser.
