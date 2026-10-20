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

# Update otomatis: kalau hash checkin.ps1 di server beda dengan file ini, unduh versi
# server lalu timpa file ini. Versi baru dipakai mulai check-in berikutnya. File hanya
# ditimpa kalau hash hasil unduhan cocok dengan yang dilaporkan server.
function Update-AgentScript($ExpectedHash, $Config, $Headers) {
    if (-not $ExpectedHash) { return }

    $selfPath = $PSCommandPath

    $currentHash = (Get-FileHash -Path $selfPath -Algorithm SHA256).Hash
    if ($currentHash -ieq $ExpectedHash) { return }

    $tempPath = Join-Path $InstallDir 'checkin.ps1.download'
    try {
        $scriptUrl = $Config.ApiUrl -replace '/checkin/?$', '/script'
        Invoke-WebRequest -Uri $scriptUrl -Headers $Headers -OutFile $tempPath -UseBasicParsing

        $downloadedHash = (Get-FileHash -Path $tempPath -Algorithm SHA256).Hash
        if ($downloadedHash -ine $ExpectedHash) {
            throw "hash unduhan ($downloadedHash) tidak cocok dengan server ($ExpectedHash)"
        }

        Move-Item -Path $tempPath -Destination $selfPath -Force
        Write-Log "UPDATE - checkin.ps1 diperbarui ke versi $($ExpectedHash.Substring(0, 12)), berlaku mulai check-in berikutnya."
    }
    catch {
        Write-Log "UPDATE GAGAL - $($_.Exception.Message)"
    }
    finally {
        if (Test-Path $tempPath) { Remove-Item $tempPath -Force -ErrorAction SilentlyContinue }
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

    Update-AgentScript -ExpectedHash $response.script_sha256 -Config $config -Headers $headers
}
catch {
    Write-Log "GAGAL - $($_.Exception.Message)"
}
