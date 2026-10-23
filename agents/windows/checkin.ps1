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

# Daftar SSID Wi-Fi kantor. Lokasi HANYA dicatat saat laptop tersambung ke salah satu SSID ini,
# supaya laptop yang dibawa pulang (Wi-Fi rumah/hotspot) tidak ikut terlacak. Bisa ditimpa lewat
# config.json ("LocationSsids": ["...", "..."]).
$DefaultLocationSsids = @(
    'SUSHI', 'FreeWifi-SUSHI', 'yaygroup_lt1', 'Yaygroup LT2', 'yaygroup', 'TrainingCenter', 'TrainingCenter_Guest'
)

# Nama Wi-Fi (SSID) yang sedang tersambung, atau $null kalau tidak ada / bukan Wi-Fi.
function Get-CurrentSsid {
    try {
        $line = (netsh wlan show interfaces 2>$null | Select-String -Pattern '^\s*SSID\s*:\s*(.+)$' | Select-Object -First 1)
        if ($line) { return $line.Matches[0].Groups[1].Value.Trim() }
    }
    catch { }
    return $null
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

# Daftar aplikasi terpasang dari registry "Apps & features" (64-bit, 32-bit, dan per-user
# untuk user yang sedang login). Update/hotfix Windows dan komponen sistem dilewati.
# Mengembalikan $null kalau gagal, supaya server tidak menghapus data yang sudah ada.
function Get-InstalledSoftware {
    try {
        $paths = @(
            'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*',
            'HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*'
        )
        Get-ChildItem Registry::HKEY_USERS -ErrorAction SilentlyContinue |
            Where-Object { $_.PSChildName -match '^S-1-5-21-[\d-]+$' } |
            ForEach-Object { $paths += "Registry::HKEY_USERS\$($_.PSChildName)\Software\Microsoft\Windows\CurrentVersion\Uninstall\*" }

        $apps = Get-ItemProperty -Path $paths -ErrorAction SilentlyContinue |
            Where-Object {
                $_.DisplayName -and $_.SystemComponent -ne 1 -and -not $_.ParentKeyName -and
                $_.ReleaseType -notin @('Update', 'Hotfix', 'Security Update')
            } |
            Sort-Object DisplayName -Unique |
            ForEach-Object {
                @{
                    name         = ([string]$_.DisplayName).Trim()
                    version      = [string]$_.DisplayVersion
                    publisher    = [string]$_.Publisher
                    installed_on = [string]$_.InstallDate
                }
            }

        return , @($apps)
    }
    catch {
        Write-Log "Daftar aplikasi tidak diambil - $($_.Exception.Message)"
        return $null
    }
}

# Status lisensi Office & Windows dari layanan lisensi Windows (SPP), plus Microsoft 365
# (langganan) dari konfigurasi Click-to-Run. Product key lengkap TIDAK dikirim, hanya 5
# karakter terakhir yang memang ditampilkan Windows.
function Get-LicenseInfo {
    try {
        $officeAppId = '0ff1ce15-a989-479d-af46-f275c6370663'
        $windowsAppId = '55c92734-d682-4d71-983e-d6ec3f16059f'
        $licenses = @()

        $products = @(Get-CimInstance SoftwareLicensingProduct -Filter 'PartialProductKey IS NOT NULL' -ErrorAction Stop)
        # Office 2010 memakai layanan lisensi sendiri.
        $products += @(Get-CimInstance OfficeSoftwareProtectionProduct -Filter 'PartialProductKey IS NOT NULL' -ErrorAction SilentlyContinue)

        foreach ($p in $products) {
            $product = if ($p.ApplicationID -eq $windowsAppId) { 'windows' } elseif ($p.ApplicationID -eq $officeAppId -or $p.Name -like 'Office*') { 'office' } else { $null }
            if (-not $product) { continue }

            $kmsHost = if ($p.KeyManagementServiceMachine) { $p.KeyManagementServiceMachine } else { $p.DiscoveredKeyManagementServiceMachineName }
            if ($kmsHost -and $p.KeyManagementServicePort) { $kmsHost = "${kmsHost}:$($p.KeyManagementServicePort)" }

            $licenses += @{
                product       = $product
                name          = [string]$p.Name
                description   = [string]$p.Description
                channel       = [string]$p.ProductKeyChannel
                status_code   = [int]$p.LicenseStatus
                grace_minutes = [int]$p.GracePeriodRemaining
                partial_key   = [string]$p.PartialProductKey
                kms_host      = [string]$kmsHost
            }
        }

        $c2r = Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\Office\ClickToRun\Configuration' -ErrorAction SilentlyContinue
        if ($c2r -and $c2r.ProductReleaseIds) {
            foreach ($releaseId in ([string]$c2r.ProductReleaseIds -split ',')) {
                $releaseId = $releaseId.Trim()
                if ($releaseId -like 'O365*') {
                    $licenses += @{ product = 'office'; name = "Microsoft 365 Apps ($releaseId)"; channel = 'Subscription'; status = 'subscription' }
                }
                elseif ($releaseId -and -not ($licenses | Where-Object { $_.product -eq 'office' })) {
                    # Office terpasang tapi tidak ada lisensi sama sekali di SPP = belum diaktivasi.
                    $licenses += @{ product = 'office'; name = $releaseId; channel = 'Click-to-Run'; status_code = 0 }
                }
            }
        }

        return , @($licenses)
    }
    catch {
        Write-Log "Status lisensi tidak diambil - $($_.Exception.Message)"
        return $null
    }
}

# Jejak tool aktivasi ilegal yang umum (KMSpico, KMSAuto, AAct, KMS_VL_ALL, dll).
function Get-CrackIndicators {
    $found = @()
    try {
        $paths = @(
            "$env:ProgramFiles\KMSpico", "${env:ProgramFiles(x86)}\KMSpico",
            "$env:ProgramData\KMSAutoS", "$env:SystemRoot\KMSAutoS",
            "$env:SystemRoot\AAct_Tools", "$env:ProgramData\AAct_Tools",
            "$env:SystemRoot\System32\SECOH-QAD.exe", "$env:SystemRoot\System32\SECOH-QAD.dll",
            "$env:SystemRoot\System32\SppExtComObjHook.dll"
        )
        foreach ($path in $paths) {
            if (Test-Path -LiteralPath $path) { $found += "file: $path" }
        }

        Get-ScheduledTask -ErrorAction SilentlyContinue |
            Where-Object { $_.TaskName -match 'AutoPico|KMSAuto|AutoKMS|KMS_VL_ALL|AAct|Activation-Renewal|Activation-Run_Once' } |
            ForEach-Object { $found += "task: $($_.TaskPath)$($_.TaskName)" }

        Get-Service -Name 'KMSELDI', 'Service_KMS' -ErrorAction SilentlyContinue |
            ForEach-Object { $found += "service: $($_.Name)" }

        $ifeo = 'HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Image File Execution Options\SppExtComObj.exe'
        if ((Get-ItemProperty $ifeo -ErrorAction SilentlyContinue).VerifierDlls) { $found += 'registry: SppExtComObj.exe VerifierDlls' }
    }
    catch {
        Write-Log "Pengecekan jejak aktivasi ilegal tidak lengkap - $($_.Exception.Message)"
    }

    return , @($found)
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
    $cpu = Get-CimInstance Win32_Processor | Select-Object -First 1

    $payload = @{
        serial_number = $bios.SerialNumber
        hostname      = $env:COMPUTERNAME
        os_name       = 'Windows'
        os_version    = $os.Caption
        cpu           = ([string]$cpu.Name).Trim()
        brand         = $computerSystem.Manufacturer
        model         = $computerSystem.Model
        ram_gb        = [math]::Round($computerSystem.TotalPhysicalMemory / 1GB)
        storage_gb    = [math]::Round($disk.Sum / 1GB)
        mac_address   = $nic.MACAddress
        username      = $env:USERNAME
    }

    # Normalnya lokasi hanya dicatat saat tersambung Wi-Fi kantor (lihat $DefaultLocationSsids).
    # Kalau aset ditandai HILANG di server, batasan ini diabaikan - tapi status itu baru diketahui
    # dari respons check-in, jadi lokasi untuk aset hilang dikirim di fase kedua (lihat bawah).
    $allowedSsids = if ($config.LocationSsids) { @($config.LocationSsids) } else { $DefaultLocationSsids }
    $currentSsid = Get-CurrentSsid
    $onOfficeWifi = [bool]($currentSsid -and ($allowedSsids -contains $currentSsid))
    $geo = $null

    if ($onOfficeWifi) {
        $geo = Get-GeoLocation
        if ($geo) { $payload += $geo }
        # Status lokasi untuk ditampilkan di web, biar IT tahu kenapa map ada/tidak.
        $payload.location_status = if ($geo) { 'captured' } else { 'unavailable' }
    }
    else {
        $payload.location_status = 'skipped_not_office_wifi'
    }

    # Key hanya dikirim kalau pengambilannya berhasil; kalau tidak dikirim, server
    # membiarkan data inventaris sebelumnya.
    $software = Get-InstalledSoftware
    if ($null -ne $software) { $payload.software = $software }

    $licenses = Get-LicenseInfo
    if ($null -ne $licenses) { $payload.licenses = $licenses }

    $payload.crack_indicators = Get-CrackIndicators

    $body = $payload | ConvertTo-Json -Depth 5 -Compress
    $headers = @{ Authorization = "Bearer $($config.ApiToken)" }

    # Dikirim sebagai byte UTF-8: PowerShell 5.1 secara default mengirim string body dengan
    # encoding lain, yang merusak karakter seperti (R)/(TM) di nama aplikasi.
    $response = Invoke-RestMethod -Uri $config.ApiUrl -Method Post -Body ([System.Text.Encoding]::UTF8.GetBytes($body)) -ContentType 'application/json; charset=utf-8' -Headers $headers

    $geoInfo = if ($geo) { "lokasi=$($geo.latitude),$($geo.longitude) (+/-$($geo.location_accuracy_m)m)" } else { 'lokasi=-' }
    $invInfo = "aplikasi=$(if ($null -ne $software) { $software.Count } else { '-' }) lisensi=$(if ($null -ne $licenses) { $licenses.Count } else { '-' })"
    Write-Log "OK - asset_code=$($response.asset_code) created=$($response.created) $geoInfo $invInfo"

    # Fase kedua: aset ditandai HILANG tapi tidak di Wi-Fi kantor -> ambil lokasi di mana pun
    # dan kirim sekali lagi, supaya laptop hilang tetap bisa dilacak.
    if (-not $onOfficeWifi -and $response.track_location_always) {
        $lostGeo = Get-GeoLocation
        if ($lostGeo) {
            $lostBody = @{
                serial_number = $bios.SerialNumber
                hostname      = $env:COMPUTERNAME
                os_name       = 'Windows'
            } + $lostGeo
            $lostJson = $lostBody | ConvertTo-Json -Compress
            Invoke-RestMethod -Uri $config.ApiUrl -Method Post -Body ([System.Text.Encoding]::UTF8.GetBytes($lostJson)) -ContentType 'application/json; charset=utf-8' -Headers $headers | Out-Null
            Write-Log "MODE HILANG - lokasi dikirim dari luar kantor: $($lostGeo.latitude),$($lostGeo.longitude) (+/-$($lostGeo.location_accuracy_m)m)"
        }
        else {
            Write-Log "MODE HILANG - lokasi tidak bisa diambil (Location Services mati / belum tersedia)."
        }
    }
    elseif (-not $onOfficeWifi) {
        Write-Log "Lokasi dilewati - bukan Wi-Fi kantor (SSID: $(if ($currentSsid) { $currentSsid } else { 'tidak tersambung' }))."
    }

    Update-AgentScript -ExpectedHash $response.script_sha256 -Config $config -Headers $headers
}
catch {
    Write-Log "GAGAL - $($_.Exception.Message)"
}
