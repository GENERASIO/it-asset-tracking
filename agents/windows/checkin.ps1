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

# Mengambil posisi dari Windows Location API (GPS / Wi-Fi / IP, tergantung yang tersedia).
# Mengembalikan $null kalau Location Services mati, izin ditolak, atau timeout - check-in
# tetap jalan tanpa koordinat.
function Get-GeoLocation([int]$TimeoutSeconds = 20) {
    $watcher = $null
    try {
        Add-Type -AssemblyName System.Device
        $watcher = New-Object System.Device.Location.GeoCoordinateWatcher([System.Device.Location.GeoPositionAccuracy]::High)
        $watcher.Start()

        $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
        while ($watcher.Status -ne 'Ready' -and $watcher.Permission -ne 'Denied' -and (Get-Date) -lt $deadline) {
            Start-Sleep -Milliseconds 500
        }

        if ($watcher.Permission -eq 'Denied') {
            Write-Log "Lokasi tidak diambil - izin lokasi ditolak (cek Settings > Privacy > Location)."
            return $null
        }

        $loc = $watcher.Position.Location
        if ($loc.IsUnknown) {
            Write-Log "Lokasi tidak diambil - status=$($watcher.Status), posisi belum tersedia dalam $TimeoutSeconds detik."
            return $null
        }

        return @{
            latitude            = [math]::Round($loc.Latitude, 7)
            longitude           = [math]::Round($loc.Longitude, 7)
            location_accuracy_m = [int][math]::Round($loc.HorizontalAccuracy)
        }
    }
    catch {
        Write-Log "Lokasi tidak diambil - $($_.Exception.Message)"
        return $null
    }
    finally {
        if ($watcher) { $watcher.Stop(); $watcher.Dispose() }
    }
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

    $geo = Get-GeoLocation
    if ($geo) {
        $payload += $geo
    }

    $body = $payload | ConvertTo-Json
    $headers = @{ Authorization = "Bearer $($config.ApiToken)" }

    $response = Invoke-RestMethod -Uri $config.ApiUrl -Method Post -Body $body -ContentType 'application/json' -Headers $headers

    $geoInfo = if ($geo) { "lokasi=$($geo.latitude),$($geo.longitude) (+/-$($geo.location_accuracy_m)m)" } else { 'lokasi=-' }
    Write-Log "OK - asset_code=$($response.asset_code) created=$($response.created) $geoInfo"
}
catch {
    Write-Log "GAGAL - $($_.Exception.Message)"
}
