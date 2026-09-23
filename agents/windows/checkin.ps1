# Mengumpulkan info hardware laptop ini dan mengirim (check-in) ke IT Asset Tracking.
# Dijalankan otomatis oleh Scheduled Task "ITAssetAgentCheckin" (lihat install.ps1).

$ErrorActionPreference = 'Stop'

$InstallDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ConfigPath = Join-Path $InstallDir 'config.json'
$LogPath = Join-Path $InstallDir 'checkin.log'

function Write-Log($message) {
    $line = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $message
    Add-Content -Path $LogPath -Value $line
}

try {
    if (-not (Test-Path $ConfigPath)) {
        throw "config.json tidak ditemukan di $InstallDir. Salin dari config.example.json dan isi ApiUrl/ApiToken."
    }

    $config = Get-Content $ConfigPath -Raw | ConvertFrom-Json

    $bios = Get-CimInstance Win32_BIOS
    $computerSystem = Get-CimInstance Win32_ComputerSystem
    $os = Get-CimInstance Win32_OperatingSystem
    $disk = Get-CimInstance Win32_DiskDrive | Measure-Object -Property Size -Sum
    $nic = Get-CimInstance Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled } | Select-Object -First 1

    $payload = @{
        serial_number = $bios.SerialNumber
        hostname      = $env:COMPUTERNAME
        os_name       = 'Windows'
        os_version    = $os.Caption
        brand         = $computerSystem.Manufacturer
        model         = $computerSystem.Model
        ram_gb        = [math]::Round($computerSystem.TotalPhysicalMemory / 1GB)
        storage_gb    = [math]::Round($disk.Sum / 1GB)
        mac_address   = $nic.MACAddress
        username      = $env:USERNAME
    }

    $body = $payload | ConvertTo-Json
    $headers = @{ Authorization = "Bearer $($config.ApiToken)" }

    $response = Invoke-RestMethod -Uri $config.ApiUrl -Method Post -Body $body -ContentType 'application/json' -Headers $headers

    Write-Log "OK - asset_code=$($response.asset_code) created=$($response.created)"
}
catch {
    Write-Log "GAGAL - $($_.Exception.Message)"
}
