#Requires -Version 5.0
<#
.SYNOPSIS
Creates a clean, production-ready zip file for the AQM Blog Post Feed WordPress plugin.

.DESCRIPTION
This script copies the necessary plugin files to a temporary directory,
excludes development files/folders (like .git, .github, node_modules, etc.),
and then compresses the clean files into a zip archive named 'aqm-blog-post-feed.zip'
suitable for distribution or updates. The final zip contains a single root folder
'aqm-blog-post-feed' with the plugin files inside.

.NOTES
Author: AQ Marketing
Date: 2024-05-02 
Version: 1.1 - Modified to ensure correct zip structure.
#>

# --- Configuration ---
$ErrorActionPreference = 'Stop' # Exit script on error

# Define the source directory (where the script is located)
$sourceDir = $PSScriptRoot

# Define the plugin slug (this will be the root folder name in the zip)
$pluginSlug = "aqm-blog-post-feed"

# Define the name for the final zip file
$zipFileName = "${pluginSlug}.zip"
$zipFilePath = Join-Path -Path $sourceDir -ChildPath $zipFileName

# Define items to exclude (files or folders)
$excludeItems = @(
    '.git',
    '.github',
    '.vscode',
    '.idea',
    'node_modules',
    'temp_release', # Exclude potential leftover temp directories
    '_temp_release', # Exclude potential leftover temp directories
    $zipFileName, # Don't include the zip file itself
    'create-clean-release.ps1', # Exclude this script
    'update-info.json', # Exclude old update mechanism file
    'phpcs.xml',
    '*.log', # Exclude log files
    '.gitignore',
    '.gitattributes',
    'README.md', # Exclude README if desired
    '.DS_Store'
)

# --- Preparation ---
# Create a unique temporary directory
$tempDirNameBase = "_temp_release"
$tempDir = Join-Path -Path $sourceDir -ChildPath "${tempDirNameBase}_$(Get-Random)"
if (Test-Path $tempDir) {
    Write-Host "Removing existing temporary directory: $tempDir"
    Remove-Item -Path $tempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $tempDir | Out-Null
Write-Host "Created temporary directory: $tempDir"

# --- Copy Files (Excluding specified items) ---
Write-Host "Copying plugin files to temporary directory..."
Get-ChildItem -Path $sourceDir -Recurse -Force | ForEach-Object {
    $relativePath = $_.FullName.Substring($sourceDir.Length).TrimStart('\')

    # Check if the item or any part of its path should be excluded
    $isExcluded = $false
    $pathSegments = $relativePath -split '[\/]'
    foreach ($itemToExclude in $excludeItems) {
        if ($itemToExclude.Contains('*')) { # Simple wildcard matching
             if (($_.Name -like $itemToExclude) -or ($relativePath -like $itemToExclude.Replace('\','/'))) {
                $isExcluded = $true
                break
            }
        } else { # Exact match on name or path segment
            if (($_.Name -eq $itemToExclude) -or ($pathSegments -contains $itemToExclude)) {
                $isExcluded = $true
                break
            }
        }
    }

    if (-not $isExcluded) {
        $destinationPath = Join-Path -Path $tempDir -ChildPath $relativePath
        $destinationDir = Split-Path -Path $destinationPath -Parent

        if (-not (Test-Path $destinationDir)) {
            New-Item -ItemType Directory -Path $destinationDir | Out-Null
        }

        if ($_.PSIsContainer) {
            if (-not (Test-Path $destinationPath)) {
                 New-Item -ItemType Directory -Path $destinationPath | Out-Null
            }
        } else {
            Copy-Item -Path $_.FullName -Destination $destinationPath -Force
        }
    }
}

# --- Rename Temp Directory to Plugin Slug ---
$finalSourcePath = Join-Path -Path $sourceDir -ChildPath $pluginSlug
# Clean up destination if it exists from a previous failed run
if (Test-Path $finalSourcePath) {
    Write-Host "Removing existing destination directory before renaming: $finalSourcePath"
    Remove-Item -Path $finalSourcePath -Recurse -Force
}
Write-Host "Renaming $tempDir to $finalSourcePath"
Rename-Item -Path $tempDir -NewName $pluginSlug

# --- Create the ZIP ---
# Remove existing zip file if it exists
if (Test-Path $zipFilePath) {
    Write-Host "Removing existing ZIP file: $zipFilePath"
    Remove-Item -Path $zipFilePath -Force
}

Write-Host "Creating ZIP file: $zipFilePath from folder $finalSourcePath"
# Zip the entire renamed directory
Compress-Archive -Path $finalSourcePath -DestinationPath $zipFilePath -Force

# --- Cleanup ---
Write-Host "Removing temporary source directory: $finalSourcePath"
Remove-Item -Path $finalSourcePath -Recurse -Force

Write-Host "Clean release ZIP created successfully at $zipFilePath"
