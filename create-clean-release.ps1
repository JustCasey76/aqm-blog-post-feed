# PowerShell script to create a clean WordPress plugin release
# This script creates a ZIP file without development files and with the correct directory structure

# Configuration
$pluginName = "aqm-blog-post-feed"
$pluginVersion = "3.1.10"
$sourceDir = $PSScriptRoot
$tempDir = Join-Path $env:TEMP "temp-$pluginName-release"
$pluginDir = Join-Path $tempDir $pluginName
$outputFile = Join-Path $sourceDir "$pluginName.zip"

# Files and directories to exclude from the release
$excludeList = @(
    ".git",
    ".github",
    ".gitignore",
    "create-release.ps1",
    "create-clean-release.ps1",
    "README.md",
    "LICENSE",
    ".DS_Store",
    "Thumbs.db"
)

Write-Host "Creating clean release for $pluginName version $pluginVersion"

# Create a temporary directory
if (Test-Path $tempDir) {
    Remove-Item -Path $tempDir -Recurse -Force
}
New-Item -Path $tempDir -ItemType Directory | Out-Null
Write-Host "Created temporary directory: $tempDir"

# Create the plugin directory structure first
Write-Host "Creating plugin directory structure..."
New-Item -Path $pluginDir -ItemType Directory -Force | Out-Null

# Copy files to the plugin directory, excluding development files
Write-Host "Copying files to plugin directory..."
Get-ChildItem -Path $sourceDir -Recurse | ForEach-Object {
    $relativePath = $_.FullName.Substring($sourceDir.Length + 1)
    
    # Check if the file/directory should be excluded
    $exclude = $false
    foreach ($item in $excludeList) {
        if ($relativePath -eq $item -or $relativePath.StartsWith("$item\")) {
            $exclude = $true
            break
        }
    }
    
    if (-not $exclude) {
        if ($_.PSIsContainer) {
            # It's a directory
            $targetDir = Join-Path $pluginDir $relativePath
            if (-not (Test-Path $targetDir)) {
                New-Item -Path $targetDir -ItemType Directory -Force | Out-Null
            }
        } else {
            # It's a file
            $targetFile = Join-Path $pluginDir $relativePath
            $targetDir = Split-Path -Path $targetFile -Parent
            
            if (-not (Test-Path $targetDir)) {
                New-Item -Path $targetDir -ItemType Directory -Force | Out-Null
            }
            
            Copy-Item -Path $_.FullName -Destination $targetFile -Force
        }
    }
}

# Create the ZIP file
Write-Host "Creating ZIP file: $outputFile"
if (Test-Path $outputFile) {
    Remove-Item -Path $outputFile -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $outputFile)

# Clean up
Write-Host "Cleaning up temporary directory..."
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "Release created successfully: $outputFile"
Write-Host "You can now upload this ZIP file to your WordPress site or distribution platform."
