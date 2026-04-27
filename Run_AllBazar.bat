@echo off
setlocal
title AllBazar Launcher

cd /d "%~dp0"

set "APP_URL=http://127.0.0.1:8000"
set "DB_NAME=allbazar"
set "DB_PORT=3006"

set "XAMPP_DIR=D:\Softweres\XAMPP"
if not exist "%XAMPP_DIR%\php\php.exe" set "XAMPP_DIR=C:\xampp"

set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
set "MYSQLD_EXE=%XAMPP_DIR%\mysql\bin\mysqld.exe"
set "MYSQL_INI=%XAMPP_DIR%\mysql\bin\my.ini"

if not exist "%PHP_EXE%" (
    echo PHP was not found. Please install/start XAMPP or update XAMPP_DIR in this file.
    pause
    exit /b 1
)

if not exist "%MYSQL_EXE%" (
    echo MySQL client was not found. Please install/start XAMPP or update XAMPP_DIR in this file.
    pause
    exit /b 1
)

if not exist ".env" (
    copy ".env.example" ".env" >nul
)

if not exist "bootstrap\cache" mkdir "bootstrap\cache"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\logs" mkdir "storage\logs"

echo Checking MySQL on port %DB_PORT%...
"%MYSQL_EXE%" -h 127.0.0.1 -P %DB_PORT% -u root -e "SELECT 1" >nul 2>nul
if errorlevel 1 (
    echo Starting XAMPP MySQL...
    if exist "%MYSQLD_EXE%" (
        start "AllBazar MySQL" /min "%MYSQLD_EXE%" --defaults-file="%MYSQL_INI%" --standalone
        timeout /t 5 /nobreak >nul
    )
)

"%MYSQL_EXE%" -h 127.0.0.1 -P %DB_PORT% -u root -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>nul
if errorlevel 1 (
    echo Could not connect to MySQL on port %DB_PORT%.
    echo Open XAMPP Control Panel, start MySQL, then run this file again.
    pause
    exit /b 1
)

if not exist "vendor\autoload.php" (
    if exist "composer.phar" (
        echo Installing Laravel dependencies...
        "%PHP_EXE%" composer.phar install
    ) else (
        echo vendor folder is missing and composer.phar was not found.
        pause
        exit /b 1
    )
)

echo Preparing Laravel...
"%PHP_EXE%" artisan config:clear
findstr /C:"APP_KEY=base64:" ".env" >nul 2>nul
if errorlevel 1 (
    "%PHP_EXE%" artisan key:generate --force
)
"%PHP_EXE%" artisan migrate --force
"%PHP_EXE%" artisan storage:link >nul 2>nul

echo Opening %APP_URL% ...
start "" "%APP_URL%"

netstat -ano | findstr ":8000" | findstr "LISTENING" >nul 2>nul
if not errorlevel 1 (
    echo Laravel already appears to be running on port 8000.
    echo Browser opened at %APP_URL%.
    pause
    exit /b 0
)

echo.
echo AllBazar is running at %APP_URL%
echo Keep this window open while using the website.
echo Press CTRL+C to stop the Laravel server.
echo.

"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000

endlocal
