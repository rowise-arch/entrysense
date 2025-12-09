## Data Flow Diagram (DFD) Generation Prompt for EntrySense System

**Objective:** Create a Level 0 (Context Diagram) and a Level 1 Data Flow Diagram for the EntrySense RFID Access Control System. Use the Gane & Sarson notation.

**System Overview:**
EntrySense is a multi-component system for managing access to a university. It consists of a PHP web interface for users, a Java listener for hardware interaction (RFID reader, gate), a Node.js server for real-time updates, and a MySQL database.

---

### Level 0 DFD (Context Diagram)

**1. Central Process:**
*   `EntrySense System` (a single process bubble representing the entire system)

**2. External Entities:**
*   **User:** Represents all system users (Admin, Security, Receptionist).
*   **RFID Reader:** The hardware that scans RFID cards.
*   **Gate:** The physical barrier that opens and closes.

**3. Data Flows:**
*   **From `User` to `EntrySense System`:**
    *   `Login Credentials`
    *   `User Management Details` (e.g., create/edit user)
    *   `Guest Registration Info`
    *   `Manual Gate Command` (e.g., force open/close)
    *   `Log & Data Requests`
*   **From `EntrySense System` to `User`:**
    *   `Login Status`
    *   `Dashboard & Monitoring Data`
    *   `System Logs` (RFID access, user activity)
    *   `User & Guest Records`
*   **From `RFID Reader` to `EntrySense System`:**
    *   `RFID UID`
*   **From `EntrySense System` to `Gate`:**
    *   `Gate Control Signal` (Open/Close)

---

### Level 1 DFD

**1. Processes:**
*   `1.0 Manage User Access`: Handles user login, logout, and session management.
*   `2.0 Manage Entity Records`: Handles creation and management of user, guest, student, and employee records.
*   `3.0 Process RFID Scan`: Receives the raw RFID UID from the reader.
*   `4.0 Validate Access`: Checks the RFID UID against the database to determine if access should be granted or denied.
*   `5.0 Control Gate`: Sends the final open or close command to the physical gate hardware.
*   `6.0 Monitor System & Logs`: Provides data for the dashboard and manages the logging of all access events.
*   `7.0 Broadcast Real-time Updates`: Pushes live access event data to the web interface via WebSockets.

**2. Data Stores:**
*   `D1: Users`: Stores user credentials and roles.
*   `D2: Entity_Data`: Stores information on RFID cards and the profiles of students, employees, and guests.
*   `D3: Logs`: A record of all scan events (granted or denied).

**3. External Entities:**
*   **User**
*   **RFID Reader**
*   **Gate**

**4. Data Flows (Examples):**

*   **User Login Flow:**
    1.  `User` sends `Login Credentials` to `1.0 Manage User Access`.
    2.  `1.0 Manage User Access` reads from `D1: Users` to verify credentials.
    3.  `1.0 Manage User Access` sends `Login Status` back to the `User`.

*   **RFID Scan & Access Validation Flow:**
    1.  `RFID Reader` sends `RFID UID` to `3.0 Process RFID Scan`.
    2.  `3.0 Process RFID Scan` sends the `RFID UID` to `4.0 Validate Access`.
    3.  `4.0 Validate Access` reads `Entity Data` from `D2` to check the UID's validity and retrieve user details.
    4.  `4.0 Validate Access` generates an `Access Decision` (Grant/Deny) and sends it to `5.0 Control Gate`.
    5.  `4.0 Validate Access` also sends `Log Entry Details` (UID, name, status, timestamp) to `6.0 Monitor System & Logs`.
    6.  `6.0 Monitor System & Logs` writes the `Log Entry Details` to `D3: Logs`.
    7.  The validated `Access Event Data` is also sent to `7.0 Broadcast Real-time Updates`, which then pushes it to the `User`.
    8.  `5.0 Control Gate` sends a `Gate Control Signal` to the `Gate`.

*   **Guest Registration Flow:**
    1.  `User` sends `Guest Registration Info` to `2.0 Manage Entity Records`.
    2.  `2.0 Manage Entity Records` writes the new `Guest Data` to `D2: Entity_Data`.

*   **Dashboard View Flow:**
    1.  `User` logs in and requests to view the dashboard.
    2.  `6.0 Monitor System & Logs` reads `Log Data` from `D3` and `Entity Data` from `D2`.
    3.  `6.0 Monitor System & Logs` sends `Dashboard & Monitoring Data` to the `User`.

*   **Manual Gate Control Flow:**
    1.  `User` sends `Manual Gate Command` to `5.0 Control Gate`.
    2.  `5.0 Control Gate` sends the corresponding `Gate Control Signal` to the `Gate`.
    3.  `5.0 Control Gate` sends `Manual Control Log` details to `6.0 Monitor System & Logs`, which writes to `D3: Logs`.
