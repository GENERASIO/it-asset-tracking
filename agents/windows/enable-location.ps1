# Menyalakan Windows Location Services di laptop kantor ini.
#
# Dijalankan terang-terangan oleh IT (dengan hak admin) sebagai bagian dari pemasangan /
# update agent pada perangkat MILIK PERUSAHAAN - bukan diam-diam dari dalam aplikasi.
# Lokasi dipakai agent untuk melacak aset yang hilang/berpindah. Pastikan karyawan sudah
# diberi tahu sesuai kebijakan perusahaan (UU PDP). Setiap langkah dicatat di setup.log.

$ErrorActionPreference = 'Stop'

$TargetDir = Join-Path $env:ProgramData 'ITAssetAgent'
$SetupLog = Join-Path $TargetDir 'setup.log'

function Write-SetupLog($message) {
    $line = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $message
    New-Item -ItemType Directory -Force -Path $TargetDir | Out-Null
    Add-Content -Path $SetupLog -Value $line -ErrorAction SilentlyContinue
}

try {
    # 1. Lepas kebijakan yang mematikan lokasi (kalau pernah di-set lewat GPO/registry).
    $policyKey = 'HKLM:\SOFTWARE\Policies\Microsoft\Windows\LocationAndSensors'
    if (Test-Path $policyKey) {
        foreach ($name in 'DisableLocation', 'DisableLocationScripting', 'DisableWindowsLocationProvider') {
            if ($null -ne (Get-ItemProperty -Path $policyKey -Name $name -ErrorAction SilentlyContinue).$name) {
                Set-ItemProperty -Path $policyKey -Name $name -Value 0
            }
        }
    }

    # 2. Izinkan aplikasi desktop mengakses lokasi (toggle "Let desktop apps access your location").
    $consent = 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\CapabilityAccessManager\ConsentStore\location'
    New-Item -Path $consent -Force | Out-Null
    Set-ItemProperty -Path $consent -Name 'Value' -Value 'Allow'

    # 3. Pastikan layanan Geolocation (lfsvc) hidup dan otomatis berjalan.
    Set-Service -Name lfsvc -StartupType Automatic -ErrorAction SilentlyContinue
    Start-Service -Name lfsvc -ErrorAction SilentlyContinue

    Write-SetupLog 'OK - Windows Location Services diaktifkan oleh IT saat pemasangan/update agent.'
}
catch {
    Write-SetupLog "GAGAL mengaktifkan Location Services - $($_.Exception.Message)"
}
