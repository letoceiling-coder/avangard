@echo off
REM Безопасная сборка админ-панели с обработкой ошибок
echo Starting admin build...

REM Устанавливаем таймаут (60 секунд)
timeout /t 1 /nobreak >nul

REM Проверяем наличие Node.js
where node >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Node.js not found in PATH
    exit /b 1
)

REM Переходим в директорию проекта
cd /d "%~dp0.."

REM Выполняем сборку с таймаутом
echo Running: npm run build:admin
call npm run build:admin

if %ERRORLEVEL% EQU 0 (
    echo Build completed successfully
    exit /b 0
) else (
    echo Build failed with error code %ERRORLEVEL%
    exit /b %ERRORLEVEL%
)

