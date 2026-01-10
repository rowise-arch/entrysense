Generate a detailed use case diagram for the EntrySense RFID Access Control System.

**System Overview:**
EntrySense is a comprehensive RFID-based access control system designed for a university campus. It features a web interface for system management and monitoring, a Java-based listener for processing RFID card data, and a Node.js server for real-time communication with the frontend. The system manages access for students, employees, and guests, with different user roles having specific permissions.

**Actors:**
The primary actors in the system are:
*   **Admin:** The system administrator with full access to all features.
*   **Security:** Security staff responsible for monitoring access and responding to events.
*   **Receptionist:** Front-desk staff responsible for guest management.
*   **Unauthenticated User:** Any individual who is not logged into the system.

**Use Cases:**

**1. Unauthenticated User:**
*   **Log In:** An unauthenticated user can log in to the system using their credentials.

**2. Authenticated Users (General):**
*   **Log Out:** Any authenticated user can log out of the system.
*   **View Dashboard:** All authenticated users can view the main dashboard, which displays real-time statistics and analytics of RFID scans.
*   **View Entry Monitor:** All authenticated users can access the entry monitor to see a live feed of access events as they happen.

**3. Receptionist:**
*   **Manage Guests:**
    *   **Register Guest:** A receptionist can register a new guest by entering their details.
    *   **Assign RFID Card:** They can assign a temporary RFID card to a registered guest.
    *   **Sign Out Guest:** A receptionist can sign out a guest, which deactivates their temporary RFID card.

**4. Security:**
*   *Inherits all use cases from the Receptionist.*
*   **Monitor System:**
    *   **View RFID Logs:** Security can view detailed logs of all RFID scans.
*   **Control Gate:**
    *   **Manually Open/Close Gate:** Security has the ability to manually open or close the gate through the control panel.

**5. Admin:**
*   *Inherits all use cases from Security.*
*   **User Management:**
    *   **Create/Edit/Deactivate User Accounts:** The admin can manage user accounts for all roles.
*   **System Auditing:**
    *   **View User Activity Logs:** The admin can view logs of actions performed by all users.
*   **Data Management:**
    *   **Manage Records:** The admin can view and search the database of students, employees, and guests.
    *   **Import Records:** The admin can import records from a CSV file.
    *   **Export Logs:** The admin has the ability to export RFID and user activity logs.
        *   **<<extend>> Generate PDF Report:** The admin can choose to generate a PDF report instead of a CSV file.

**Diagram Requirements:**
*   The diagram should clearly show the relationships between the actors and the use cases.
*   Use generalization to show the relationship between the authenticated user roles (Admin, Security, Receptionist).
*   Use <<include>> and <<extend>> relationships where appropriate to simplify the diagram. For example, viewing the dashboard might be part of the general workflow for all authenticated users.
*   Group related use cases under packages if it helps with clarity (e.g., "User Management," "Guest Management").
