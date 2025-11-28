# Reschedule Migration Setup Script
# This PowerShell script helps you apply the reschedule tracking migration to your database

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Reschedule Tracking Migration Setup" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$dbName = "u112535700_u112535700_"
$migrationFile = "migrations\add_reschedule_fields.sql"
$rollbackFile = "migrations\rollback_reschedule_fields.sql"

# Check if MySQL executable exists
if (-not (Test-Path $mysqlPath)) {
    Write-Host "ERROR: MySQL executable not found at: $mysqlPath" -ForegroundColor Red
    Write-Host "Please update the `$mysqlPath variable in this script." -ForegroundColor Yellow
    exit 1
}

# Check if migration file exists
if (-not (Test-Path $migrationFile)) {
    Write-Host "ERROR: Migration file not found: $migrationFile" -ForegroundColor Red
    exit 1
}

Write-Host "MySQL Path: $mysqlPath" -ForegroundColor Green
Write-Host "Database: $dbName" -ForegroundColor Green
Write-Host "Migration File: $migrationFile" -ForegroundColor Green
Write-Host ""

# Menu
Write-Host "Please select an option:" -ForegroundColor Yellow
Write-Host "1. Apply Migration (Add reschedule fields)" -ForegroundColor White
Write-Host "2. Rollback Migration (Remove reschedule fields)" -ForegroundColor White
Write-Host "3. Test Connection Only" -ForegroundColor White
Write-Host "4. Exit" -ForegroundColor White
Write-Host ""

$choice = Read-Host "Enter your choice (1-4)"

switch ($choice) {
    "1" {
        Write-Host ""
        Write-Host "Applying migration..." -ForegroundColor Yellow
        Write-Host ""
        
        $password = Read-Host "Enter MySQL root password (press Enter if no password)" -AsSecureString
        $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($password)
        $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
        
        if ($plainPassword -eq "") {
            & $mysqlPath -u root $dbName < $migrationFile
        } else {
            & $mysqlPath -u root -p"$plainPassword" $dbName < $migrationFile
        }
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "✓ Migration applied successfully!" -ForegroundColor Green
            Write-Host ""
            Write-Host "Next Steps:" -ForegroundColor Cyan
            Write-Host "1. Verify the changes in phpMyAdmin" -ForegroundColor White
            Write-Host "2. Run the test queries in migrations/README_RESCHEDULE_MIGRATION.md" -ForegroundColor White
            Write-Host "3. Proceed to Phase 2 (Backend API implementation)" -ForegroundColor White
        } else {
            Write-Host ""
            Write-Host "✗ Migration failed! Check error messages above." -ForegroundColor Red
        }
    }
    
    "2" {
        Write-Host ""
        Write-Host "WARNING: This will remove all reschedule tracking fields!" -ForegroundColor Red
        $confirm = Read-Host "Are you sure? (yes/no)"
        
        if ($confirm -eq "yes") {
            Write-Host ""
            Write-Host "Rolling back migration..." -ForegroundColor Yellow
            Write-Host ""
            
            $password = Read-Host "Enter MySQL root password (press Enter if no password)" -AsSecureString
            $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($password)
            $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
            
            if ($plainPassword -eq "") {
                & $mysqlPath -u root $dbName < $rollbackFile
            } else {
                & $mysqlPath -u root -p"$plainPassword" $dbName < $rollbackFile
            }
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host ""
                Write-Host "✓ Rollback completed successfully!" -ForegroundColor Green
            } else {
                Write-Host ""
                Write-Host "✗ Rollback failed! Check error messages above." -ForegroundColor Red
            }
        } else {
            Write-Host "Rollback cancelled." -ForegroundColor Yellow
        }
    }
    
    "3" {
        Write-Host ""
        Write-Host "Testing MySQL connection..." -ForegroundColor Yellow
        Write-Host ""
        
        $password = Read-Host "Enter MySQL root password (press Enter if no password)" -AsSecureString
        $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($password)
        $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
        
        if ($plainPassword -eq "") {
            & $mysqlPath -u root -e "SELECT 'Connection successful!' AS status;"
        } else {
            & $mysqlPath -u root -p"$plainPassword" -e "SELECT 'Connection successful!' AS status;"
        }
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "✓ MySQL connection successful!" -ForegroundColor Green
        } else {
            Write-Host ""
            Write-Host "✗ Connection failed! Check your MySQL installation." -ForegroundColor Red
        }
    }
    
    "4" {
        Write-Host "Exiting..." -ForegroundColor Yellow
        exit 0
    }
    
    default {
        Write-Host "Invalid choice!" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
