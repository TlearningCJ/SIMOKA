@echo off
REM ==== SIMOKA: setup database + jalankan aplikasi (butuh XAMPP) ====
setlocal
cd /d "%~dp0"
set XAMPP=C:\xampp
if not exist "%XAMPP%\php\php.exe" (
  echo [!] XAMPP tidak ditemukan di %XAMPP%. Ubah baris "set XAMPP=" di setup.bat.
  pause & exit /b 1
)

echo [1/3] Menyalakan MySQL...
"%XAMPP%\mysql\bin\mysqladmin.exe" -u root ping >nul 2>&1
if errorlevel 1 (
  start "" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
  timeout /t 6 /nobreak >nul
)

echo [2/3] Membuat database simoka...
if /i "%1"=="skipdb" goto run
"%XAMPP%\mysql\bin\mysql.exe" -u root < database\simoka.sql
if errorlevel 1 ( echo [!] Gagal import database. Pastikan MySQL menyala. & pause & exit /b 1 )

:run
echo [3/3] Aplikasi berjalan di http://localhost:8080  (tutup jendela ini untuk berhenti)
start "" http://localhost:8080
"%XAMPP%\php\php.exe" -S localhost:8080
