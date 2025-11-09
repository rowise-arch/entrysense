@echo off
chcp 65001 >nul
title RFID Listener

:: Change to the correct directory
cd /d "C:\wamp64\www\entrysense\JavaListener"

echo [%date% %time%] Starting RFID Listener... > rfid_log.txt
echo Current directory: %CD% >> rfid_log.txt

:: Test Java
java -version >> rfid_log.txt 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Java not found! >> rfid_log.txt
    echo ERROR: Java not found!
    pause
    exit /b 1
)

:: List files for debugging
echo Files in directory: >> rfid_log.txt
dir /b >> rfid_log.txt

echo Starting RFID Listener with command: >> rfid_log.txt
echo java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener >> rfid_log.txt

:: Run the exact command that works manually
java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener >> rfid_log.txt 2>&1

set EXIT_CODE=%errorlevel%
echo [%date% %time%] RFID Listener stopped with exit code: %EXIT_CODE% >> rfid_log.txt
echo RFID Listener stopped with exit code: %EXIT_CODE%
pause