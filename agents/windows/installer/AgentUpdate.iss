; IT Asset Agent - updater Windows (Inno Setup)
;
; Untuk laptop yang SUDAH terpasang agent versi lama (sebelum ada fitur update otomatis):
; cukup menimpa checkin.ps1 di %ProgramData%\ITAssetAgent dengan versi terbaru, lalu
; check-in sekali. Tidak butuh config.json/token - token lama di laptop tetap dipakai.
; Setelah ini, update berikutnya terjadi otomatis lewat check-in.
;
; Build: "C:\Users\Yay\AppData\Local\Programs\Inno Setup 6\ISCC.exe" AgentUpdate.iss

#define MyAppName "IT Asset Agent Update"
#define MyAppVersion "1.1"
#define MyAppPublisher "PT. YAY Enak Semua"

[Setup]
AppId={{7A1E3D52-2C4B-4F1A-9B6E-ITASSETUPDATE}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={commonappdata}\ITAssetAgent
DisableDirPage=yes
DisableProgramGroupPage=yes
DisableReadyPage=yes
PrivilegesRequired=admin
Uninstallable=no
CreateUninstallRegKey=no
OutputDir=output
OutputBaseFilename=ITAssetAgentUpdate
Compression=lzma
SolidCompression=yes
WizardStyle=modern
SetupLogging=yes

[Files]
Source: "..\checkin.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\enable-location.ps1"; DestDir: "{app}"; Flags: ignoreversion

[Run]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\enable-location.ps1"""; StatusMsg: "Mengaktifkan layanan lokasi..."; Flags: runhidden
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\checkin.ps1"""; StatusMsg: "Menjalankan check-in..."; Flags: runhidden

[Code]
function InitializeSetup(): Boolean;
begin
  if not FileExists(ExpandConstant('{commonappdata}\ITAssetAgent\config.json')) then
  begin
    MsgBox('IT Asset Agent belum terpasang di laptop ini.' + #13#10 +
           'Pakai installer lengkap (ITAssetAgentSetup.exe dari menu Agent di website IT Asset Tracking), bukan updater ini.',
           mbError, MB_OK);
    Result := False;
  end else
    Result := True;
end;
