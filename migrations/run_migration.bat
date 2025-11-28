@echo off
echo ============================================
echo   Reschedule Migration Quick Setup
echo ============================================
echo.
echo This will apply the reschedule tracking migration.
echo.
pause
echo.

PowerShell.exe -ExecutionPolicy Bypass -File migrations\setup_migration.ps1

pause
