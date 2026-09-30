; IT Asset Agent - installer Windows (Inno Setup)
;
; Dikompilasi sekali (generic, tanpa token) jadi ITAssetAgentSetup.exe. Token per-laptop
; dikirim terpisah lewat file config.json yang disalin BERSEBELAHAN dengan exe ini di
; dalam paket zip yang didownload dari halaman Kelola Agent Token (lihat flag "external"
; di [Files] di bawah - config.json TIDAK dikompilasi ke dalam exe, tapi dibaca dari
; folder yang sama saat instalasi berjalan).
;
; Build: "C:\Users\Yay\AppData\Local\Programs\Inno Setup 6\ISCC.exe" AgentSetup.iss

#define MyAppName "IT Asset Agent"
#define MyAppVersion "1.0"
#define MyAppPublisher "PT. YAY Enak Semua"

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
Source: "register-task.ps1"; DestDir: "{app}"; Flags: ignoreversion
; config.json BERISI TOKEN, beda tiap download - diambil dari folder sebelah exe
; saat instalasi (tidak ikut dikompilasi ke dalam exe).
Source: "{code:GetSrcExeDir}config.json"; DestDir: "{app}"; Flags: external ignoreversion

[Run]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\register-task.ps1"""; StatusMsg: "Mendaftarkan jadwal check-in otomatis..."; Flags: runhidden
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\checkin.ps1"""; StatusMsg: "Menjalankan check-in pertama..."; Flags: runhidden

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""Unregister-ScheduledTask -TaskName 'ITAssetAgentCheckin' -Confirm:$false -ErrorAction SilentlyContinue"""; Flags: runhidden; RunOnceId: "RemoveScheduledTask"

[Code]
function GetSrcExeDir(Param: String): String;
begin
  Result := ExtractFilePath(ExpandConstant('{srcexe}'));
end;

function InitializeSetup(): Boolean;
begin
  if not FileExists(GetSrcExeDir('') + 'config.json') then
  begin
    MsgBox('config.json tidak ditemukan di folder yang sama dengan installer ini.' + #13#10 +
           'Pastikan Anda mengekstrak SEMUA isi zip yang didownload dari halaman Kelola Agent Token, ' +
           'lalu jalankan Setup.exe dari dalam folder hasil ekstrak tersebut.',
           mbError, MB_OK);
    Result := False;
  end else
    Result := True;
end;
