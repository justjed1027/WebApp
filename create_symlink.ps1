# Run this script as Administrator to create the uploads symlink
# Right-click and select "Run with PowerShell" or open PowerShell as Admin and run: powershell -ExecutionPolicy Bypass -File create_symlink.ps1

cd "C:\xampp\htdocs\WebApp\BPA"

# Remove old uploads folder if it exists (non-symlink)
if ((Test-Path "uploads") -and -not ((Get-Item "uploads").Attributes -band [IO.FileAttributes]::ReparsePoint)) {
  Write-Host "Removing old uploads folder..."
  Remove-Item -Recurse -Force "uploads"
}

# Create symlink
Write-Host "Creating symlink from BPA/uploads to parent /uploads..."
New-Item -ItemType SymbolicLink -Path "uploads" -Target "..\..\uploads" -Force

Write-Host "Done! Your uploads symlink is ready."
Write-Host "You can now run the post.php page and upload images."
