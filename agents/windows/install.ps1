# Memasang IT Asset Agent: menyalin script ke ProgramData dan mendaftarkan Scheduled Task
# yang menjalankan checkin.ps1 saat login dan berulang setiap hari.
#
# Cara pakai:
#   1. Isi config.json (salin dari config.example.json) di folder ini dengan ApiUrl + ApiToken.
#   2. Jalankan file ini (klik kanan > Run with PowerShell), tidak wajib sebagai Administrator.

$ErrorActionPreference = 'Stop'

$SourceDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$TargetDir = Join-Path $env:ProgramData 'ITAssetAgent'
$TaskName = 'ITAssetAgentCheckin'

if (-not (Test-Path (Join-Path $SourceDir 'config.json'))) {
    Write-Error "config.json belum ada. Salin config.example.json jadi config.json lalu isi ApiUrl dan ApiToken sebelum install."
    exit 1
}

New-Item -ItemType Directory -Force -Path $TargetDir | Out-Null
Copy-Item -Path (Join-Path $SourceDir 'checkin.ps1') -Destination $TargetDir -Force
Copy-Item -Path (Join-Path $SourceDir 'config.json') -Destination $TargetDir -Force

# Nyalakan Windows Location Services (butuh admin; dilewati dengan peringatan kalau tidak).
$enableLocation = Join-Path $SourceDir 'enable-location.ps1'
if (Test-Path $enableLocation) {
    Copy-Item -Path $enableLocation -Destination $TargetDir -Force
    $isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltinRole]::Administrator)
    if ($isAdmin) {
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $TargetDir 'enable-location.ps1')
    } else {
        Write-Host "Lewati: aktivasi Location Services butuh Administrator. Jalankan ulang sebagai admin, atau nyalakan manual di Settings > Privacy > Location." -ForegroundColor Yellow
    }
}

$action = New-ScheduledTaskAction -Execute 'powershell.exe' `
    -Argument "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$TargetDir\checkin.ps1`""

$triggerLogon = New-ScheduledTaskTrigger -AtLogOn
$triggerDaily = New-ScheduledTaskTrigger -Daily -At 9am

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger @($triggerLogon, $triggerDaily) -Force | Out-Null

Write-Host "Berhasil dipasang. Task '$TaskName' akan check-in saat login dan tiap hari jam 09:00."

# Jalankan sekali sekarang supaya asetnya langsung muncul.
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $TargetDir 'checkin.ps1')
Write-Host "Check-in pertama sudah dijalankan. Cek log di $TargetDir\checkin.log"
