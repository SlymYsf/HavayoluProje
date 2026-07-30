@echo off
cd /d C:\laragon\www\HavayoluProje
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan queue:work --tries=3 --timeout=120 --sleep=3 >> storage\logs\queue.log 2>&1
