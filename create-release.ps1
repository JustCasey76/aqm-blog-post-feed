param (
    [Parameter(Mandatory=$true)]
    [string]$Version,
    
    [Parameter(Mandatory=$false)]
    [string]$Message = "Release version $Version",
    
    [Parameter(Mandatory=$false)]
    [string]$FtpHost = "",
    
    [Parameter(Mandatory=$false)]
    [string]$FtpUser = "",
    
    [Parameter(Mandatory=$false)]
    [string]$FtpPassword = "",
    
    [Parameter(Mandatory=$false)]
    [string]$FtpPath = "/wp-content/plugins/aqm-blog-post-feed/updates/"
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

# Use website URL for package if FTP credentials are provided
if ($FtpHost -and $FtpUser -and $FtpPassword) {
    $updateInfo.package = "https://stevesservicesllc.com/wp-content/plugins/aqm-blog-post-feed/updates/aqm-blog-post-feed.zip"
} else {
    $updateInfo.package = "https://github.com/JustCasey76/aqm-blog-post-feed/releases/download/v$Version/aqm-blog-post-feed.zip"
}

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

# 6. Upload ZIP file to website via FTP if credentials are provided
if ($FtpHost -and $FtpUser -and $FtpPassword) {
    Write-Host "Uploading ZIP file to website via FTP..."
    try {
        # Create FTP request
        $ftpUrl = "ftp://$FtpHost$FtpPath"
        $ftpRequest = [System.Net.FtpWebRequest]::Create("$ftpUrl/aqm-blog-post-feed.zip")
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPassword)
        $ftpRequest.UseBinary = $true
        $ftpRequest.UsePassive = $true
        
        # Read file content
        $fileContent = [System.IO.File]::ReadAllBytes($outputZip)
        $ftpRequest.ContentLength = $fileContent.Length
        
        # Get request stream and write file content
        $requestStream = $ftpRequest.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        # Get response
        $response = $ftpRequest.GetResponse()
        Write-Host "Upload Status: $($response.StatusDescription)"
        $response.Close()
        
        Write-Host "ZIP file uploaded successfully to website!"
    } catch {
        Write-Host "Error uploading file: $_"
        Write-Host "You'll need to manually upload the ZIP file to your website."
    }
}

Write-Host "`nRelease v$Version created successfully!"
Write-Host "ZIP file created at: $outputZip"

Write-Host "`nTo complete the process:"
if (-not ($FtpHost -and $FtpUser -and $FtpPassword)) {
    Write-Host "1. Upload the ZIP file to your website at /wp-content/plugins/aqm-blog-post-feed/updates/"
    Write-Host "   OR"
    Write-Host "1. Upload the ZIP file to the GitHub release at https://github.com/JustCasey76/aqm-blog-post-feed/releases/tag/v$Version"
}
Write-Host "2. Test the update on your WordPress site"

Write-Host "`nTo run this script with FTP upload in the future, use:"
Write-Host "  .\create-release.ps1 -Version X.X -FtpHost ftp.yoursite.com -FtpUser username -FtpPassword password"
