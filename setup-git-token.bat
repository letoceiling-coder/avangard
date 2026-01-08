@echo off
chcp 65001 >nul
echo 🔑 Настройка Personal Access Token для GitHub
echo.

echo 📝 Инструкция по созданию токена:
echo    1. Откройте: https://github.com/settings/tokens
echo    2. Нажмите 'Generate new token (classic)'
echo    3. Название: 'Avangard Deploy'
echo    4. Права: выберите 'repo' (полный доступ к репозиториям)
echo    5. Нажмите 'Generate token'
echo    6. СКОПИРУЙТЕ ТОКЕН (он показывается только один раз!)
echo.

set /p username="Введите ваш GitHub username: "
if "%username%"=="" (
    echo ❌ Username не может быть пустым
    exit /b 1
)

set /p token="Введите Personal Access Token: "
if "%token%"=="" (
    echo ❌ Token не может быть пустым
    exit /b 1
)

echo.
echo 🔧 Обновление remote URL...
git remote set-url origin "https://%username%:%token%@github.com/letoceiling-coder/avangard.git"

if %errorlevel% equ 0 (
    echo ✅ Remote URL обновлен
    echo.
    echo 📋 Текущая конфигурация:
    git remote -v
    echo.
    echo ✅ Настройка завершена!
    echo.
    echo 📝 Теперь вы можете выполнить:
    echo    git push
    echo    php artisan deploy --insecure
) else (
    echo ❌ Ошибка обновления remote URL
    exit /b 1
)

