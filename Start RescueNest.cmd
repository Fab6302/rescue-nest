@echo off
setlocal
cd /d "%~dp0"
set "PHP_EXE=php"
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
"%PHP_EXE%" -v >nul 2>&1
if errorlevel 1 (
    echo Install PHP 8.2 or newer, or use XAMPP, then try again.
    pause
    exit /b 1
)
if not exist vendor\autoload.php (
    echo Laravel dependencies are missing. See README.md.
    pause
    exit /b 1
)
if not exist .env (
    copy /y .env.demo .env >nul
    "%PHP_EXE%" artisan key:generate || goto failed
)
findstr /b /i "DB_CONNECTION=sqlite" .env >nul
if not errorlevel 1 if not exist database\database.sqlite type nul > database\database.sqlite
"%PHP_EXE%" artisan migrate --force || goto failed
if not exist storage\directory-v2-seeded.flag (
    "%PHP_EXE%" artisan rescue:demo || goto failed
    type nul > storage\directory-v2-seeded.flag
)
echo Open http://127.0.0.1:8000/ in your browser. Keep this window open.
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000
goto :eof
:failed
echo Setup failed. See the message above and README.md.
pause
exit /b 1