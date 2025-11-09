@echo off
title RFID Listener
cd /d "C:\xampp\htdocs\entrysense\JavaListener"
echo Starting RFID Listener...

:: Use relative paths instead of absolute
java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener

pause