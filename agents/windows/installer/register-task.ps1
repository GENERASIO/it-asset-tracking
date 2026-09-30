# Dijalankan oleh installer (dengan hak admin) setelah checkin.ps1 & config.json
# disalin ke %ProgramData%\ITAssetAgent. Mendaftarkan Scheduled Task yang berjalan
# sebagai SYSTEM, supaya check-in berikutnya tidak lagi butuh hak admin dari user
# yang login di laptop ini.

$ErrorActionPreference = 'Stop'

$TargetDir = Join-Path $env:ProgramData 'ITAssetAgent'
$TaskName = 'ITAssetAgentCheckin'
$ScriptPath = Join-Path $TargetDir 'checkin.ps1'

$action = New-ScheduledTaskAction -Execute 'powershell.exe' `
    -Argument "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$ScriptPath`""

$triggerLogon = New-ScheduledTaskTrigger -AtLogOn
$triggerDaily = New-ScheduledTaskTrigger -Daily -At 9am
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger @($triggerLogon, $triggerDaily) -Principal $principal -Force | Out-Null
