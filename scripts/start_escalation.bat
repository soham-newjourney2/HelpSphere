@echo off
rem Runs the immediate-donation escalation loop (keep this window open).
rem Uses XAMPP's PHP; falls back to a portable tools\php or php on PATH.
set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=%~dp0..\tools\php\php.exe"
if not exist "%PHP%" set "PHP=php"
"%PHP%" "%~dp0run_escalation_loop.php"
pause
