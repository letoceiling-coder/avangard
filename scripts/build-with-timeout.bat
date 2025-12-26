@echo off
REM Сборка с таймаутом и обработкой ошибок

set TIMEOUT_SECONDS=300
set BUILD_COMMAND=%*

if "%BUILD_COMMAND%"=="" (
    echo ERROR: Не указана команда для выполнения
    exit /b 1
)

echo Запуск команды: %BUILD_COMMAND%
echo Таймаут: %TIMEOUT_SECONDS% секунд
echo.

REM Используем PowerShell для таймаута
powershell -Command "$job = Start-Job -ScriptBlock { Set-Location '%CD%'; %BUILD_COMMAND% }; if (Wait-Job -Job $job -Timeout %TIMEOUT_SECONDS%) { Receive-Job -Job $job; Remove-Job -Job $job; exit $LASTEXITCODE } else { Stop-Job -Job $job; Remove-Job -Job $job; Write-Host 'ERROR: Таймаут выполнения команды'; exit 1 }"

exit /b %ERRORLEVEL%

