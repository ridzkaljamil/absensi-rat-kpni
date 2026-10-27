@echo off
title Absensi RAT KPNI - JANGAN DITUTUP selama aplikasi dipakai
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" (
    echo.
    echo  PHP tidak ditemukan di %PHP%
    echo  Pastikan XAMPP terpasang di C:\xampp
    echo.
    pause
    exit /b
)

cls
echo  ============================================================
echo    SISTEM ABSENSI RAT KPNI
echo  ============================================================
echo.
echo   Pastikan MySQL sudah START di XAMPP Control Panel.
echo.
echo   Buka di laptop ini      : http://localhost:8080
echo   Buka dari perangkat lain (WiFi yang sama):
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    for /f "tokens=* delims= " %%b in ("%%a") do echo                             http://%%b:8080
)
echo.
echo   Biarkan jendela ini tetap terbuka. Tutup jendela ini
echo   untuk mematikan aplikasi.
echo  ============================================================
echo.

REM Mode development menampilkan detail teknis ke semua perangkat di WiFi
findstr /R /C:"^ *CI_ENVIRONMENT *= *development" .env >nul 2>&1
if not errorlevel 1 (
    echo   [!] PERHATIAN: .env masih CI_ENVIRONMENT = development.
    echo       Untuk acara RAT ubah menjadi: CI_ENVIRONMENT = production
    echo.
)

REM Perbarui struktur database kalau ada versi aplikasi baru (aman dijalankan berulang)
set "LOGMIG=%TEMP%\absensi_migrate.txt"
"%PHP%" spark migrate > "%LOGMIG%" 2>&1
findstr /I /C:"failed" /C:"error" /C:"exception" "%LOGMIG%" >nul
if not errorlevel 1 goto gagal_migrate
findstr /C:"Migrations complete" "%LOGMIG%" >nul
if errorlevel 1 goto gagal_migrate
goto lanjut

:gagal_migrate
echo   [!] Database belum bisa disiapkan. Pastikan MySQL sudah START di
echo       XAMPP Control Panel. Pesan dari aplikasi:
echo.
type "%LOGMIG%"
echo.
pause
exit /b

:lanjut

start "" cmd /c "timeout /t 3 >nul & start http://localhost:8080"
"%PHP%" spark serve --host 0.0.0.0 --port 8080
pause
