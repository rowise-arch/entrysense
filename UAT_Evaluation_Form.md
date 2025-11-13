# EntrySense System: User Acceptance Testing (UAT) and Evaluation Form

---

## 1. Introduction

**Purpose:** This document is for conducting User Acceptance Testing (UAT) on the EntrySense RFID Access Control System. The goal is to verify that the system meets the business requirements and functions as expected from an end-user perspective before it is deployed.

**Instructions for the Tester:**
- Please execute each test case as described.
- For each step, record the actual result you observed.
- Mark the test as 'Pass' if the actual result matches the expected result, and 'Fail' otherwise.
- Provide any relevant comments, details, or screenshots, especially for failed tests.
- Complete the overall evaluation and sign-off sections at the end.

---

## 2. General Information

| **Item** | **Details** |
| :--- | :--- |
| **System/Application Name** | EntrySense RFID Access Control System |
| **System Version** | 1.0.0 |
| **Test Environment** | Staging / Pre-production |
| **Tester Name** | |
| **Date of Testing** | |
| **Browser(s) Used** | |

---

## 3. Test Cases

### 3.1. Authentication

| **Test Case ID** | **Test Scenario** | **Steps to Reproduce** | **Expected Result** | **Actual Result** | **Status (Pass/Fail)** | **Comments** |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **AUTH-01** | Successful Login | 1. Navigate to the login page.<br>2. Enter valid admin credentials.<br>3. Click "Login". | User is successfully authenticated and redirected to the main dashboard. | | | |
| **AUTH-02** | Failed Login (Invalid Password) | 1. Navigate to the login page.<br>2. Enter a valid username and an invalid password.<br>3. Click "Login". | User is not authenticated. An appropriate error message like "Invalid username or password" is displayed. | | | |
| **AUTH-03** | Failed Login (Invalid Username) | 1. Navigate to the login page.<br>2. Enter an invalid username and any password.<br>3. Click "Login". | User is not authenticated. An appropriate error message like "Invalid username or password" is displayed. | | | |

### 3.2. User Management

| **Test Case ID** | **Test Scenario** | **Steps to Reproduce** | **Expected Result** | **Actual Result** | **Status (Pass/Fail)** | **Comments** |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **USER-01** | View Users List | 1. Log in to the system.<br>2. Navigate to the "Users" or "Manage Users" section. | A list/table of all registered users is displayed with relevant details (e.g., Name, Email, Role). | | | |
| **USER-02** | Add a New User | 1. Navigate to the "Users" section.<br>2. Click "Add New User".<br>3. Fill in the required user details.<br>4. Click "Save". | The new user is created successfully and appears in the users list. A success message is shown. | | | |
| **USER-03** | Edit an Existing User | 1. Navigate to the "Users" section.<br>2. Select a user and click "Edit".<br>3. Modify some details (e.g., phone number).<br>4. Click "Update". | The user's details are updated successfully. The changes are reflected in the users list. | | | |
| **USER-04** | Add a User with Missing Fields | 1. Navigate to "Add New User".<br>2. Leave a mandatory field (e.g., Name) blank.<br>3. Click "Save". | The system prevents the form submission and displays a clear error message indicating the missing field. | | | |

### 3.3. RFID Card and Access Control

| **Test Case ID** | **Test Scenario** | **Steps to Reproduce** | **Expected Result** | **Actual Result** | **Status (Pass/Fail)** | **Comments** |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **RFID-01** | Assign RFID Card to User | 1. Navigate to a user's profile.<br>2. Go to the RFID card management section.<br>3. Enter a new RFID UID.<br>4. Click "Assign Card". | The RFID card is successfully linked to the user. The UID appears in the user's assigned cards list. | | | |
| **RFID-02** | Grant Access with Valid Card | 1. Scan a valid, assigned RFID card at the reader. | The system grants access (e.g., unlocks the door). The access event is logged as "Access Granted" with the correct user details and timestamp. | | | |
| **RFID-03** | Deny Access with Invalid Card | 1. Scan an unassigned RFID card at the reader. | The system denies access. The access event is logged as "Access Denied" with the invalid UID and timestamp. | | | |
| **RFID-04** | Deny Access with Revoked Card | 1. Un-assign/revoke an RFID card from a user.<br>2. Scan the now-revoked card at the reader. | The system denies access. The event is logged as "Access Denied". | | | |

### 3.4. Real-time Monitoring and Logs

| **Test Case ID** | **Test Scenario** | **Steps to Reproduce** | **Expected Result** | **Actual Result** | **Status (Pass/Fail)** | **Comments** |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **LOG-01** | View Access Logs | 1. Log in to the system.<br>2. Navigate to the "Access Logs" or "Live Feed" section. | A list of all historical access events (granted and denied) is displayed in chronological order. | | | |
| **LOG-02** | Real-time Update on Granted Access | 1. Have the "Live Feed" page open.<br>2. Scan a valid RFID card at the reader. | A new "Access Granted" event appears at the top of the feed instantly, without requiring a page refresh. | | | |
| **LOG-03** | Real-time Update on Denied Access | 1. Have the "Live Feed" page open.<br>2. Scan an invalid RFID card at the reader. | A new "Access Denied" event appears at the top of the feed instantly, without requiring a page refresh. | | | |

---

## 4. Overall Evaluation & Feedback

*Please provide your overall feedback on the system's usability, design, performance, and functionality.*

**4.1. What did you like about the system?**
<br>
<br>
<br>

**4.2. What did you dislike, or what could be improved?**
<br>
<br>
<br>

**4.3. Did you encounter any bugs or errors not covered in the test cases? If so, please describe them.**
<br>
<br>
<br>

---

## 5. Sign-off

*By signing below, you confirm that you have completed the UAT for the EntrySense system and that the results recorded are accurate.*

| **Role** | **Name** | **Signature** | **Date** |
| :--- | :--- | :--- | :--- |
| **Lead Tester / Product Owner** | | | |
| **Project Manager** | | | |
