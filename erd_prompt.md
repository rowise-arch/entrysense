## Entity-Relationship Diagram (ERD) Generation Prompt for EntrySense System

**Objective:** Create a detailed Entity-Relationship Diagram (ERD) for the EntrySense system's database using Crow's Foot notation. The diagram should clearly define all entities, their attributes (including primary and foreign keys), and the relationships between them with correct cardinality.

**Entities and Attributes:**

1.  **users**
    *   `user_id` (PK)
    *   `username`
    *   `password`
    *   `role`
    *   `full_name`
    *   `is_active`

2.  **user_activity_logs**
    *   `log_id` (PK)
    *   `user_id` (FK to users)
    *   `action`
    *   `description`
    *   `timestamp`

3.  **rfid_info**
    *   `rfid_id` (PK)
    *   `rfid_uid` (Unique)
    *   `role`

4.  **student**
    *   `student_id` (PK)
    *   `first_name`
    *   `last_name`
    *   `department`
    *   `course`
    *   `photo`

5.  **employee**
    *   `employee_id` (PK)
    *   `first_name`
    *   `last_name`
    *   `department`
    *   `position`
    *   `photo`

6.  **guest**
    *   `guest_id` (PK)
    *   `first_name`
    *   `last_name`
    *   `person_to_visit`
    *   `status`

7.  **rfid_student_info** (Junction Table)
    *   `rfid_id` (PK, FK to rfid_info)
    *   `student_id` (PK, FK to student)
    *   *(Note: This table uses a composite primary key)*

8.  **rfid_employee_info** (Junction Table)
    *   `rfid_id` (PK, FK to rfid_info)
    *   `employee_id` (PK, FK to employee)
    *   *(Note: This table uses a composite primary key)*

9.  **rfid_assignments** (Junction Table for Guests)
    *   `assignment_id` (PK)
    *   `rfid_id` (FK to rfid_info)
    *   `guest_id` (FK to guest)
    *   `assigned_at`
    *   `released_at`
    *   `is_active`

10. **logs** (Historical Log Table)
    *   `log_id` (PK)
    *   `rfid_uid`
    *   `status`
    *   `scan_time`

**Relationships and Cardinality:**

*   A **user** has one-to-many **user_activity_logs**.
    *   `users` (one) -> `user_activity_logs` (many)
*   A **student** has a one-to-one relationship with an **rfid_info** card, implemented via the **rfid_student_info** junction table.
    *   `student` (one) -- `rfid_student_info` -- (one) `rfid_info`
*   An **employee** has a one-to-one relationship with an **rfid_info** card, implemented via the **rfid_employee_info** junction table.
    *   `employee` (one) -- `rfid_employee_info` -- (one) `rfid_info`
*   A **guest** can have many temporary **rfid_assignments**, and an **rfid_info** card (designated for guests) can be in many assignments over time.
    *   `guest` (one) -> `rfid_assignments` (many)
    *   `rfid_info` (one) -> `rfid_assignments` (many)

**Diagram Requirements:**
*   Use Crow's Foot notation.
*   Clearly label all entities and their attributes.
*   Mark Primary Keys (PK) and Foreign Keys (FK).
*   Show the correct cardinality for each relationship.
*   The `logs` table should be included but can be shown as a separate, denormalized entity without direct relationships, as it is used for historical reporting.
