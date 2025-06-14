# ChronoForge Plugin Status Checker
# PowerShell script to check the status of ChronoForge plugin files

Write-Host "=== ChronoForge Plugin Status Checker ===" -ForegroundColor Cyan
Write-Host ""

# Get the plugin directory
$pluginDir = Join-Path $PSScriptRoot "..\chrono-forge"

if (-not (Test-Path $pluginDir)) {
    Write-Host "❌ ChronoForge plugin directory not found at: $pluginDir" -ForegroundColor Red
    exit 1
}

Write-Host "📁 Checking plugin directory: $pluginDir" -ForegroundColor Green
Write-Host ""

# Function to check file existence and size
function Check-File {
    param(
        [string]$FilePath,
        [string]$Description
    )
    
    if (Test-Path $FilePath) {
        $size = (Get-Item $FilePath).Length
        $sizeKB = [math]::Round($size / 1024, 2)
        Write-Host "   ✅ $Description ($sizeKB KB)" -ForegroundColor Green
        return $true
    } else {
        Write-Host "   ❌ $Description - NOT FOUND" -ForegroundColor Red
        return $false
    }
}

# Function to check directory existence
function Check-Directory {
    param(
        [string]$DirPath,
        [string]$Description
    )
    
    if (Test-Path $DirPath -PathType Container) {
        $itemCount = (Get-ChildItem $DirPath -Force).Count
        Write-Host "   ✅ $Description ($itemCount items)" -ForegroundColor Green
        return $true
    } else {
        Write-Host "   ❌ $Description - NOT FOUND" -ForegroundColor Red
        return $false
    }
}

# Check core files
Write-Host "1. Core Files Check:" -ForegroundColor Yellow
$coreFiles = @{
    "chrono-forge.php" = "Main plugin file"
    "composer.json" = "Composer configuration"
    "vendor\autoload.php" = "Autoloader"
    "src\functions.php" = "Global functions"
}

$coreFilesOK = $true
foreach ($file in $coreFiles.GetEnumerator()) {
    $filePath = Join-Path $pluginDir $file.Key
    if (-not (Check-File $filePath $file.Value)) {
        $coreFilesOK = $false
    }
}

Write-Host ""

# Check core classes
Write-Host "2. Core Classes Check:" -ForegroundColor Yellow
$coreClasses = @{
    "src\Core\Plugin.php" = "Main plugin class"
    "src\Core\Container.php" = "DI Container"
    "src\Core\ServiceProvider.php" = "Service provider base"
    "src\Core\Activator.php" = "Plugin activator"
    "src\Core\Deactivator.php" = "Plugin deactivator"
}

$coreClassesOK = $true
foreach ($class in $coreClasses.GetEnumerator()) {
    $classPath = Join-Path $pluginDir $class.Key
    if (-not (Check-File $classPath $class.Value)) {
        $coreClassesOK = $false
    }
}

Write-Host ""

# Check directories
Write-Host "3. Directory Structure Check:" -ForegroundColor Yellow
$directories = @{
    "src" = "Source directory"
    "src\Core" = "Core classes directory"
    "vendor" = "Vendor directory"
    "assets" = "Assets directory"
    "admin" = "Admin directory"
    "includes" = "Legacy includes directory"
    "languages" = "Languages directory"
}

$directoriesOK = $true
foreach ($dir in $directories.GetEnumerator()) {
    $dirPath = Join-Path $pluginDir $dir.Key
    if (-not (Check-Directory $dirPath $dir.Value)) {
        $directoriesOK = $false
    }
}

Write-Host ""

# Check legacy files
Write-Host "4. Legacy Files Check:" -ForegroundColor Yellow
$legacyFiles = @{
    "includes\utils\functions.php" = "Legacy utility functions"
    "includes\class-chrono-forge-core.php" = "Legacy core class"
    "includes\class-chrono-forge-db-manager.php" = "Legacy DB manager"
    "admin\class-chrono-forge-admin-menu.php" = "Legacy admin menu"
}

$legacyFilesCount = 0
foreach ($file in $legacyFiles.GetEnumerator()) {
    $filePath = Join-Path $pluginDir $file.Key
    if (Check-File $filePath $file.Value) {
        $legacyFilesCount++
    }
}

Write-Host ""

# PHP syntax check (if PHP is available)
Write-Host "5. PHP Syntax Check:" -ForegroundColor Yellow
$phpPath = Get-Command php -ErrorAction SilentlyContinue

if ($phpPath) {
    $mainFile = Join-Path $pluginDir "chrono-forge.php"
    if (Test-Path $mainFile) {
        $syntaxCheck = & php -l $mainFile 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   ✅ Main plugin file syntax OK" -ForegroundColor Green
        } else {
            Write-Host "   ❌ Main plugin file syntax ERROR: $syntaxCheck" -ForegroundColor Red
        }
    }
} else {
    Write-Host "   ⚠️  PHP not found in PATH - skipping syntax check" -ForegroundColor Yellow
}

Write-Host ""

# Summary
Write-Host "=== Status Summary ===" -ForegroundColor Cyan
Write-Host "Core Files: $(if ($coreFilesOK) { '✅ OK' } else { '❌ ISSUES' })" -ForegroundColor $(if ($coreFilesOK) { 'Green' } else { 'Red' })
Write-Host "Core Classes: $(if ($coreClassesOK) { '✅ OK' } else { '❌ ISSUES' })" -ForegroundColor $(if ($coreClassesOK) { 'Green' } else { 'Red' })
Write-Host "Directories: $(if ($directoriesOK) { '✅ OK' } else { '❌ ISSUES' })" -ForegroundColor $(if ($directoriesOK) { 'Green' } else { 'Red' })
Write-Host "Legacy Files: $legacyFilesCount found" -ForegroundColor $(if ($legacyFilesCount -gt 0) { 'Green' } else { 'Yellow' })

Write-Host ""

# Overall status
if ($coreFilesOK -and $coreClassesOK -and $directoriesOK) {
    Write-Host "🎉 Plugin structure looks good!" -ForegroundColor Green
    Write-Host "Ready for testing and deployment." -ForegroundColor Green
    exit 0
} else {
    Write-Host "⚠️  Some issues found with plugin structure." -ForegroundColor Yellow
    Write-Host "Please review the issues above before testing." -ForegroundColor Yellow
    exit 1
}
