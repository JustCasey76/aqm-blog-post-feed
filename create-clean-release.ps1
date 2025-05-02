# PowerShell script to create a clean WordPress plugin release
# This script creates a ZIP file without development files and with the correct directory structure

# Configuration
$pluginName = "aqm-blog-post-feed"
$pluginVersion = "3.1.10" # Ensure version is correct
$sourceDir = $PSScriptRoot
$tempDir = Join-Path $env:TEMP "temp-$pluginName-release"
$pluginDir = Join-Path $tempDir $pluginName
$outputFile = Join-Path $sourceDir "$pluginName.zip"

# Files and directories to exclude from the release
$excludeList = @(
    ".git",
    ".github",
    ".vscode",
    ".DS_Store",
    "node_modules",
    "package.json",
    "package-lock.json",
    "*.zip", # Exclude existing zip files
    "create-clean-release.ps1", # Exclude the script itself
    "README.md" # Exclude README if not needed in release
)

# Clean up previous temporary directory if it exists
if (Test-Path $tempDir) {
    Write-Host "Removing existing temporary directory: $tempDir"
    Remove-Item -Path $tempDir -Recurse -Force
}

# Create the temporary directory
Write-Host "Creating temporary directory..."
New-Item -Path $tempDir -ItemType Directory | Out-Null
Write-Host "Created temporary directory: $tempDir"

# Create the plugin directory structure first
Write-Host "Creating plugin directory structure..."
New-Item -Path $pluginDir -ItemType Directory -Force | Out-Null

# Copy files to the plugin directory, excluding development files
Write-Host "Copying files to plugin directory..."
Get-ChildItem -Path $sourceDir -Recurse | Where-Object {
    # Filter out excluded items early
    $currentItemRelativePath = $_.FullName.Substring($sourceDir.Length + 1)
    $isExcluded = $false
    foreach ($excludedItem in $excludeList) {
        if ($currentItemRelativePath -eq $excludedItem -or $currentItemRelativePath.StartsWith("$excludedItem\")) {
            $isExcluded = $true
            break
        }
    }
    -not $isExcluded
} | ForEach-Object {
    $relativePath = $_.FullName.Substring($sourceDir.Length + 1)
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

# Create the ZIP file
Write-Host "Creating ZIP file: $outputFile"
if (Test-Path $outputFile) {
    Remove-Item -Path $outputFile -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $outputFile)

# Clean up temporary directory
Write-Host "Cleaning up temporary directory..."
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "Clean release created successfully: $outputFile"
