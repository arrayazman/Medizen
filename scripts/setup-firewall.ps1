# PANDUAN KEAMANAN SETUP PACS VIEWER PROXY
# Jalankan script ini sebagai Administrator di PowerShell

# ===========================================
# LANGKAH 1: Tambah Firewall Rules
# ===========================================

# Hapus rule lama kalau ada
Remove-NetFirewallRule -DisplayName "PACS Proxy*" -ErrorAction SilentlyContinue

# Izinkan akses ke port 8043 HANYA dari localhost
New-NetFirewallRule -DisplayName "PACS Proxy - Allow Localhost 8043" `
    -Direction Inbound `
    -Protocol TCP `
    -LocalPort 8043 `
    -RemoteAddress "127.0.0.1" `
    -Action Allow `
    -Profile Any

# Blokir semua IP lain ke port 8043
New-NetFirewallRule -DisplayName "PACS Proxy - Block All External 8043" `
    -Direction Inbound `
    -Protocol TCP `
    -LocalPort 8043 `
    -Action Block `
    -Profile Any

Write-Host "✅ Firewall rules berhasil ditambahkan!" -ForegroundColor Green
Write-Host "   Port 8043 sekarang hanya bisa diakses dari localhost (127.0.0.1)" -ForegroundColor Cyan
