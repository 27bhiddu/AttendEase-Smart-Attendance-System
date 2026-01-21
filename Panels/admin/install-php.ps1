# PHP Installation Script for Windows
# Run this script as Administrator

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PHP Installation Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

# PHP Configuration
$phpVersion = "8.2.13"
$phpDir = "C:\php"
$downloadUrl = "https://windows.php.net/downloads/releases/php-8.2.13-Win32-vs16-x64.zip"
$downloadPath = "$env:TEMP\php.zip"

# Step 1: Check if PHP already exists
if (Test-Path "$phpDir\php.exe") {
    Write-Host "PHP already exists at $phpDir" -ForegroundColor Yellow
    $response = Read-Host "Do you want to reinstall? (y/n)"
    if ($response -ne "y") {
        Write-Host "Installation cancelled." -ForegroundColor Yellow
        exit 0
    }
    Remove-Item -Path $phpDir -Recurse -Force -ErrorAction SilentlyContinue
}

# Step 2: Create PHP directory
Write-Host "Creating PHP directory..." -ForegroundColor Green
New-Item -ItemType Directory -Path $phpDir -Force | Out-Null

# Step 3: Download PHP
Write-Host "Downloading PHP $phpVersion..." -ForegroundColor Green
Write-Host "This may take a few minutes..." -ForegroundColor Yellow

try {
    # Try multiple download sources
    $sources = @(
        "https://windows.php.net/downloads/releases/php-8.2.13-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/archives/php-8.2.13-Win32-vs16-x64.zip",
        "https://github.com/shivammathur/php-builder-windows/releases/download/8.2/php-8.2.13-Win32-vs16-x64.zip"
    )
    
    $downloaded = $false
    foreach ($url in $sources) {
        try {
            Write-Host "Trying: $url" -ForegroundColor Gray
            Invoke-WebRequest -Uri $url -OutFile $downloadPath -UseBasicParsing -TimeoutSec 300
            $downloaded = $true
            Write-Host "Download successful!" -ForegroundColor Green
            break
        } catch {
            Write-Host "Failed: $_" -ForegroundColor Gray
            continue
        }
    }
    
    if (-not $downloaded) {
        Write-Host "ERROR: Could not download PHP automatically." -ForegroundColor Red
        Write-Host "Please download manually from: https://windows.php.net/download/" -ForegroundColor Yellow
        Write-Host "Extract to: $phpDir" -ForegroundColor Yellow
        pause
        exit 1
    }
} catch {
    Write-Host "ERROR: Download failed - $_" -ForegroundColor Red
    Write-Host "Please download PHP manually from: https://windows.php.net/download/" -ForegroundColor Yellow
    pause
    exit 1
}

# Step 4: Extract PHP
Write-Host "Extracting PHP..." -ForegroundColor Green
try {
    Expand-Archive -Path $downloadPath -DestinationPath $phpDir -Force
    Write-Host "Extraction complete!" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Extraction failed - $_" -ForegroundColor Red
    pause
    exit 1
}

# Step 5: Configure php.ini
Write-Host "Configuring php.ini..." -ForegroundColor Green
$phpIniSource = Join-Path $phpDir "php.ini-development"
$phpIni = Join-Path $phpDir "php.ini"

if (Test-Path $phpIniSource) {
    Copy-Item $phpIniSource $phpIni -Force
    
    # Enable required extensions
    $extensions = @("curl", "mbstring", "openssl")
    foreach ($ext in $extensions) {
        $content = Get-Content $phpIni
        $content = $content -replace ";extension=$ext", "extension=$ext"
        Set-Content $phpIni $content
        Write-Host "  Enabled: $ext" -ForegroundColor Gray
    }
    Write-Host "php.ini configured!" -ForegroundColor Green
} else {
    Write-Host "WARNING: php.ini-development not found. Please configure manually." -ForegroundColor Yellow
}

# Step 6: Add to PATH
Write-Host "Adding PHP to system PATH..." -ForegroundColor Green
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
if ($currentPath -notlike "*$phpDir*") {
    $newPath = $currentPath + ";$phpDir"
    [Environment]::SetEnvironmentVariable("Path", $newPath, "Machine")
    Write-Host "PHP added to PATH!" -ForegroundColor Green
} else {
    Write-Host "PHP already in PATH." -ForegroundColor Yellow
}

# Step 7: Cleanup
Write-Host "Cleaning up..." -ForegroundColor Green
Remove-Item $downloadPath -Force -ErrorAction SilentlyContinue

# Step 8: Verify installation
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Installation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Please close and reopen PowerShell, then run:" -ForegroundColor Yellow
Write-Host "  php -v" -ForegroundColor White
Write-Host ""
Write-Host "To start your admin panel server:" -ForegroundColor Yellow
Write-Host "  cd C:\project_attendease\admin-panel" -ForegroundColor White
Write-Host "  php -S localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "Then open: http://localhost:8000/login.php" -ForegroundColor Cyan
Write-Host ""

pause





