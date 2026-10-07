@echo off
cd /d "%~dp0"
title College Grievance Portal

echo ====================================================
echo   College Grievance Management System
echo ====================================================
echo.

:: Check if already running on port 5001
netstat -ano | findstr :5001 >nul
if %errorlevel% equ 0 (
    echo [INFO] Server is already running on port 5001!
    echo Opening web browser...
    start http://127.0.0.1:5001
    exit /b
)

:: Python environment check
if exist ".\.venv\Scripts\python.exe" (
    set "PY_CMD=.\.venv\Scripts\python.exe"
) else (
    set "PY_CMD=python"
)

echo [1/2] Opening browser at http://127.0.0.1:5001 ...
start http://127.0.0.1:5001

echo [2/2] Starting Flask server...
echo.
echo ----------------------------------------------------
echo Keep this window OPEN while using the application.
echo To stop the server, press Ctrl+C or close this window.
echo ----------------------------------------------------
echo.

%PY_CMD% app.py

pause
