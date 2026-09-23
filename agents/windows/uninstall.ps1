# Mencabut IT Asset Agent dari laptop ini: hapus Scheduled Task dan file terpasang.

$ErrorActionPreference = 'SilentlyContinue'

$TargetDir = Join-Path $env:ProgramData 'ITAssetAgent'
$TaskName = 'ITAssetAgentCheckin'

Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
Remove-Item -Recurse -Force $TargetDir

Write-Host "IT Asset Agent sudah dicabut dari laptop ini."
