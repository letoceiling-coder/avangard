@echo off
echo ============================================
echo Очистка всех кешей проекта
echo ============================================
echo.

echo [1/7] Очистка кеша конфигурации Laravel...
php artisan config:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш конфигурации
    pause
    exit /b 1
)

echo [2/7] Очистка кеша приложения Laravel...
php artisan cache:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш приложения
    pause
    exit /b 1
)

echo [3/7] Очистка кеша маршрутов Laravel...
php artisan route:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш маршрутов
    pause
    exit /b 1
)

echo [4/7] Очистка кеша представлений Laravel...
php artisan view:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш представлений
    pause
    exit /b 1
)

echo [5/7] Очистка кеша событий Laravel...
php artisan event:clear
if %errorlevel% neq 0 (
    echo ПРЕДУПРЕЖДЕНИЕ: Команда event:clear может быть недоступна в этой версии Laravel
)

echo [6/7] Полная очистка оптимизационных кешей Laravel...
php artisan optimize:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось выполнить optimize:clear
    pause
    exit /b 1
)

echo [7/7] Очистка кеша NPM...
call npm cache clean --force
if %errorlevel% neq 0 (
    echo ПРЕДУПРЕЖДЕНИЕ: Не удалось очистить кеш NPM
)

echo.
echo ============================================
echo Очистка кешей завершена!
echo ============================================
echo.
echo Дополнительные команды (опционально):
echo   - Очистка кеша Composer: composer clear-cache
echo   - Очистка кеша Vite: удалить папку node_modules/.vite (если есть)
echo.
pause

