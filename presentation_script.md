**(Slide 1: Title Slide - Logo and "EntrySense: Smart Access for a Secure Future")**

**(Presenter)**
"Good morning, everyone. In a world where security and efficiency are paramount, how can we modernize access control for a bustling campus? Today, we're excited to introduce EntrySense, a comprehensive security and access management system designed for the modern institution.

**(Slide 2: The Problem & The Solution - Side-by-side of manual logbook vs. student tapping RFID card)**

"EntrySense replaces outdated, insecure manual check-ins with a smart, RFID-based solution. No more long lines or cumbersome logbooks. Just a simple, secure tap."

**(Slide 3: How It Works - A diagram showing the 3 core components)**

"So, how does it work? Our system is built on three core components:

1.  **The RFID Hardware**: At every entry point, an Arduino-powered RFID reader scans user cards.
2.  **The Core Listener**: A powerful Java application running on the server is the brain of our system. It listens for scans, validates the RFID UID against our secure MySQL database, and decides whether to grant or deny access.
3.  **The Web Interface**: A dynamic web application, built with PHP and JavaScript, provides a real-time dashboard for monitoring and administration."

**(Slide 4: Live Demo / Key Features - Screen recording of the Entry Monitor)**

"Let’s see it in action. When a user scans their card, the Java listener instantly processes the request. If the card is valid, access is granted, and the entry is logged.

Simultaneously, this event is pushed to our web dashboard in real-time using a Node.js WebSocket server. As you can see on the Entry Monitor, the user's photo, name, and details appear instantly. If an unauthorized card is scanned, access is denied, and that event is also logged and displayed immediately, alerting security."

**(Slide 5: Powerful Admin Tools - Screenshot of the Database and Logs page)**

"EntrySense is also a powerful administrative tool. The web interface allows authorized staff to:

*   **Manage the Database**: View records for students, faculty, and guests.
*   **Import in Bulk**: Easily import thousands of users from a CSV file.
*   **View Detailed Logs**: Track every access attempt across campus for auditing and security analysis.
*   **And Control Gates Remotely**: Security can manually open or close entry points directly from the control panel."

**(Slide 6: Conclusion - "Secure. Efficient. Smart.")**

"In conclusion, EntrySense is more than just a gatekeeper. It’s a smart, data-driven solution that enhances security, streamlines access, and provides valuable insights. It’s the future of campus management.

Thank you. We’re now open for questions."
