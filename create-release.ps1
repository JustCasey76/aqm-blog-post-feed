param (
    [Parameter(Mandatory=$true)]
    [string]$Version,
    
    [Parameter(Mandatory=$false)]
    [string]$Message = "Release version $Version"
)

# Remove 'v' prefix if provided
$Version = $Version -replace '^v', ''

# Paths
$pluginDir = $PSScriptRoot
$mainFile = Join-Path $pluginDir "aqm-blog-post-feed.php"
$updateInfoFile = Join-Path $pluginDir "update-info.json"
$outputZip = Join-Path $pluginDir "..\aqm-blog-post-feed.zip"

# 1. Update version in main plugin file
Write-Host "Updating version in plugin file to $Version..."
$content = Get-Content $mainFile -Raw
$content = $content -replace 'Plugin Name: AQM Blog Post Feed V[0-9.]+', "Plugin Name: AQM Blog Post Feed V$Version"
$content = $content -replace 'Version: [0-9.]+', "Version: $Version"
$content = $content -replace "define\('AQM_BLOG_POST_FEED_VERSION', '[0-9.]+'\);", "define('AQM_BLOG_POST_FEED_VERSION', '$Version');"
Set-Content -Path $mainFile -Value $content

# 2. Update version in update-info.json
Write-Host "Updating version in update-info.json..."
$updateInfo = Get-Content $updateInfoFile | ConvertFrom-Json
$updateInfo.new_version = $Version
$updateInfo.package = "https://github.com/JustCasey76/aqm-blog-post-feed/releases/download/v$Version/aqm-blog-post-feed.zip"
$updateInfo | ConvertTo-Json | Set-Content $updateInfoFile

# 3. Create ZIP file
Write-Host "Creating ZIP file..."
if (Test-Path $outputZip) {
    Remove-Item $outputZip -Force
}
Compress-Archive -Path "$pluginDir\*" -DestinationPath $outputZip -Force

# 4. Commit changes
Write-Host "Committing changes..."
Set-Location $pluginDir
git add .
git commit -m "Update version to $Version"

# 5. Create and push tag
Write-Host "Creating and pushing tag v$Version..."
git tag -a "v$Version" -m $Message
git push origin master:main
git push origin "v$Version"

Write-Host "`nRelease v$Version created successfully!"
Write-Host "ZIP file created at: $outputZip"
Write-Host "`nTo complete the process:"
Write-Host "1. Wait for GitHub Actions to create the release (check https://github.com/JustCasey76/aqm-blog-post-feed/actions)"
Write-Host "2. Verify the release at https://github.com/JustCasey76/aqm-blog-post-feed/releases"
Write-Host "3. Test the update on your WordPress site"
