; IT Asset Agent - installer Windows (Inno Setup)
;
; Dikompilasi sekali (generic, tanpa token) jadi ITAssetAgentSetup.exe. Token per-laptop
; dikirim terpisah lewat file config.json yang disalin BERSEBELAHAN dengan exe ini di
; dalam paket zip yang didownload dari menu Agent di website IT Asset Tracking (lihat flag "external"
; di [Files] di bawah - config.json TIDAK dikompilasi ke dalam exe, tapi dibaca dari
; folder yang sama saat instalasi berjalan).
;
; Build: "C:\Users\Yay\AppData\Local\Programs\Inno Setup 6\ISCC.exe" AgentSetup.iss

#define MyAppName "IT Asset Agent"
#define MyAppVersion "1.2"
#define MyAppPublisher "PT. YAY Enak Semua"
; Satu installer untuk semua kasus:
;  - Laptop baru  : ada config.json (berisi token) di folder yang sama -> install penuh.
;  - Laptop yang agennya SUDAH terpasang: tanpa config.json di folder pun bisa, installer
;    akan menyegarkan script + menyalakan lokasi + check-in (token lama tetap dipakai).
; Jadi tidak perlu lagi exe updater terpisah.

[Setup]
AppId={{5C3B9C2B-6B2A-4E8A-9E9E-ITASSETAGENT1}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={commonappdata}\ITAssetAgent
DisableDirPage=yes
DisableProgramGroupPage=yes
DisableReadyPage=yes
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64compatible
OutputDir=output
OutputBaseFilename=ITAssetAgentSetup
Compression=lzma
SolidCompression=yes
WizardStyle=modern
UninstallDisplayName={#MyAppName}
SetupLogging=yes

[Files]
; Script check-in generik - sama untuk semua laptop, dikompilasi ke dalam exe.
Source: "..\checkin.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\enable-location.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "register-task.ps1"; DestDir: "{app}"; Flags: ignoreversion
; config.json BERISI TOKEN, beda tiap download - diambil dari folder sebelah exe
; saat instalasi (tidak ikut dikompilasi ke dalam exe). Hanya disalin saat install
; penuh; di mode update (agent sudah terpasang) config.json lama tetap dipakai.
Source: "{code:GetSrcExeDir}config.json"; DestDir: "{app}"; Flags: external ignoreversion; Check: NeedConfig

[Run]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\register-task.ps1"""; StatusMsg: "Mendaftarkan jadwal check-in otomatis..."; Flags: runhidden
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\checkin.ps1"""; StatusMsg: "Menjalankan check-in pertama..."; Flags: runhidden

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""Unregister-ScheduledTask -TaskName 'ITAssetAgentCheckin' -Confirm:$false -ErrorAction SilentlyContinue"""; Flags: runhidden; RunOnceId: "RemoveScheduledTask"

[Code]
var
  UpdateMode: Boolean;

function GetSrcExeDir(Param: String): String;
begin
  Result := ExtractFilePath(ExpandConstant('{srcexe}'));
end;

function HasBesideConfig(): Boolean;
begin
  Result := FileExists(GetSrcExeDir('') + 'config.json');
end;

function AgentAlreadyInstalled(): Boolean;
begin
  Result := FileExists(ExpandConstant('{commonappdata}\ITAssetAgent\config.json'));
end;

{ Salin config.json (token) hanya saat install penuh, bukan saat update. }
function NeedConfig(): Boolean;
begin
  Result := not UpdateMode;
end;

function InitializeSetup(): Boolean;
begin
  { Mode update: tidak ada config.json di sebelah exe, tapi agent sudah terpasang. }
  UpdateMode := (not HasBesideConfig()) and AgentAlreadyInstalled();

  if (not HasBesideConfig()) and (not AgentAlreadyInstalled()) then
  begin
    MsgBox('config.json tidak ditemukan di folder yang sama dengan installer ini.' + #13#10 +
           'Untuk laptop BARU: ekstrak SEMUA isi zip dari menu Agent di website IT Asset Tracking, ' +
           'lalu jalankan Setup.exe dari dalam folder hasil ekstrak tersebut.' + #13#10 +
           '(Di laptop yang agennya sudah terpasang, Setup.exe ini bisa dijalankan sendiri untuk memperbarui.)',
           mbError, MB_OK);
    Result := False;
  end else
    Result := True;
end;
