@echo off
setlocal
REM Safe one-way database backup. This script does not restore or alter the database.
set DB_NAME=barangay_san_antonio_portal_launch_demo
set BACKUP_DIR=%~dp0..\backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
set STAMP=%DATE:~-4%%DATE:~4,2%%DATE:~7,2%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%
set STAMP=%STAMP: =0%
"C:\xampp\mysql\bin\mysqldump.exe" -h 127.0.0.1 -P 3306 -u root -p --single-transaction --routines --triggers %DB_NAME% > "%BACKUP_DIR%\%DB_NAME%_%STAMP%.sql"
if errorlevel 1 (
  echo Backup failed. Check XAMPP MariaDB and credentials.
  exit /b 1
)
echo Backup created in %BACKUP_DIR%
endlocal
