
# Prompt for Generating an Integrated Hardware-Software System Architecture

## Role and Goal

You are a senior systems architect. Your task is to generate a comprehensive architecture document for an integrated hardware-software system based on the specifications provided below. The final output should be a well-structured technical document that is clear, concise, and detailed enough for engineering teams (hardware, firmware, backend, and frontend) to begin development.

---

## 1. System Overview

Please fill in the details for the system you want to design.

*   **Project Name:** `[Specify the project name, e.g., "Project Sentinel: Smart Home Security System"]`
*   **Elevator Pitch:** `[Provide a one or two-sentence summary of the system. What is its primary purpose and for whom?]`
*   **Key Goals & Objectives:** `[List the top 3-5 business or user goals the system must achieve. e.g., "1. Detect unauthorized entry in real-time. 2. Allow remote monitoring via a mobile app. 3. Minimize false alarms."]`

## 2. Core Use Cases & Functional Requirements

Describe the primary interactions and functionalities.

*   **Primary User Stories:**
    *   As a `[User Type]`, I want to `[Action]` so that `[Benefit]`.
    *   As a `[User Type]`, I want to `[Action]` so that `[Benefit]`.
    *   `[Add more as needed]`
*   **System Functions:**
    *   **Data Acquisition:** `[Describe what data the hardware needs to collect. e.g., "Capture video footage, read temperature and humidity, detect motion."]`
    *   **Data Processing:** `[Describe what the system does with the data. e.g., "Analyze video for human presence, check temperature against a threshold, aggregate sensor data."]`
    *   **Control & Actuation:** `[Describe the physical actions the system can perform. e.g., "Sound an alarm, lock a door, send a push notification."]`
    *   **User Interface (UI):** `[Describe how users interact with the system. e.g., "A mobile app to view live video, a web dashboard for historical data analysis."]`

## 3. Non-Functional Requirements & Constraints

Define the quality attributes and limitations of the system.

*   **Performance:**
    *   **Latency:** `[e.g., "Time from motion detection to user notification must be < 2 seconds."]`
    *   **Data Throughput:** `[e.g., "System must support 100 sensor data points per minute per device."]`
*   **Reliability:**
    *   **Uptime:** `[e.g., "Cloud services must have 99.9% uptime."]`
    *   **Fault Tolerance:** `[e.g., "The device must be able to cache data locally if internet connectivity is lost and sync when reconnected."]`
*   **Security:**
    *   **Data Encryption:** `[e.g., "All communication between the device and the cloud must be encrypted using TLS 1.2+. Data at rest must be encrypted."]`
    *   **Authentication:** `[e.g., "Secure authentication for all user access and device provisioning."]`
*   **Scalability:**
    *   `[e.g., "The system must be able to support 10,000 active devices concurrently within the first year."]`
*   **Physical & Environmental Constraints:**
    *   **Power:** `[e.g., "Must be battery-powered with a minimum life of 1 year, or operate on a 5V USB power supply."]`
    *   **Size/Form Factor:** `[e.g., "The device enclosure must not exceed 5cm x 5cm x 3cm."]`
    *   **Operating Conditions:** `[e.g., "Must operate between -20°C and 60°C."]`
*   **Cost:**
    *   **Bill of Materials (BOM) Target:** `[e.g., "Target hardware cost per unit is < $50."]`

## 4. Requested Architecture Details

Based on the above, generate the following architectural components.

### I. High-Level Architecture Diagram

*   Create a **System Context Diagram** (using Mermaid syntax) that shows the system as a single box, its users, and its interactions with external systems (e.g., third-party services, other hardware).

### II. Component Breakdown

Provide a detailed description of each major component in the system.

*   **Hardware Layer:**
    *   **Microcontroller/Processor (MCU/CPU):** Recommend a specific MCU (e.g., ESP32, STM32, Raspberry Pi Pico) and justify the choice based on the requirements.
    *   **Sensors:** List the necessary sensors (e.g., PIR motion sensor, DHT22 temperature/humidity sensor, Camera module).
    *   **Actuators:** List the necessary actuators (e.g., Siren, LED indicators, Servo for a lock).
    *   **Communication Module:** Specify the communication technology (e.g., Wi-Fi, Bluetooth LE, LoRaWAN, Cellular) and justify the choice.
    *   **Power Source:** Specify the power system (e.g., Li-Po battery with charging circuit, direct AC/DC adapter).

*   **Firmware/Embedded Software Layer:**
    *   **Operating System:** Recommend an OS or bare-metal approach (e.g., FreeRTOS, Zephyr, Arduino Core).
    *   **Key Responsibilities:** Describe the main tasks of the firmware (e.g., "Reading sensor data every 5s, managing connectivity, handling over-the-air (OTA) updates, executing commands from the cloud").

*   **Communications Layer:**
    *   **Protocol:** Define the primary communication protocol between the device and the cloud (e.g., MQTT, HTTP/S, CoAP, WebSockets). Justify your choice.
    *   **Data Format:** Specify the data serialization format (e.g., JSON, Protocol Buffers). Provide a sample message payload.

*   **Backend / Cloud Layer:**
    *   **Architecture Style:** (e.g., Microservices, Monolithic).
    *   **Key Services:**
        *   **Ingestion Service:** Receives data from devices.
        *   **Database:** Recommend a database type (e.g., Time-series like InfluxDB for sensor data, Relational like PostgreSQL for user data).
        *   **API Server:** Exposes a REST or GraphQL API for the frontend clients.
        *   **Authentication Service:** Manages user and device identity.
        *   **Rules/Alerting Engine:** Processes data and triggers actions.
    *   **Deployment Environment:** Suggest a cloud provider or on-premise solution (e.g., AWS, Azure, GCP).

*   **Frontend / Client Layer:**
    *   **Application Type:** (e.g., Mobile App (iOS/Android), Web Dashboard).
    *   **Key Features:** (e.g., "Device registration, real-time data visualization, historical data charts, user profile management").

### III. Data Flow Diagram

*   Illustrate the end-to-end data flow for a primary use case (e.g., "Motion is Detected").
*   Describe the sequence of events step-by-step, from the sensor firing to the user receiving a notification.
*   Optionally, create a **Sequence Diagram** using Mermaid syntax for this flow.

### IV. Final Recommendations

*   Provide a summary of the key design decisions and trade-offs made.
*   Identify the top 3 potential risks or challenges for this architecture and suggest mitigation strategies.
