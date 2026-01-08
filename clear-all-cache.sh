#!/bin/bash

echo "============================================"
echo "Очистка всех кешей проекта"
echo "============================================"
echo ""

echo "[1/8] Очистка кеша конфигурации Laravel..."
php artisan config:clear || {
    echo "ОШИБКА: Не удалось очистить кеш конфигурации"
    exit 1
}

echo "[2/8] Очистка кеша приложения Laravel..."
php artisan cache:clear || {
    echo "ОШИБКА: Не удалось очистить кеш приложения"
    exit 1
}

echo "[3/8] Очистка кеша маршрутов Laravel..."
php artisan route:clear || {
    echo "ОШИБКА: Не удалось очистить кеш маршрутов"
    exit 1
}

echo "[4/8] Очистка кеша представлений Laravel..."
php artisan view:clear || {
    echo "ОШИБКА: Не удалось очистить кеш представлений"
    exit 1
}

echo "[5/8] Очистка кеша событий Laravel..."
php artisan event:clear 2>/dev/null || {
    echo "ПРЕДУПРЕЖДЕНИЕ: Команда event:clear может быть недоступна в этой версии Laravel"
}

echo "[6/8] Полная очистка оптимизационных кешей Laravel..."
php artisan optimize:clear || {
    echo "ОШИБКА: Не удалось выполнить optimize:clear"
    exit 1
}

echo "[7/8] Очистка кеша NPM..."
npm cache clean --force || {
    echo "ПРЕДУПРЕЖДЕНИЕ: Не удалось очистить кеш NPM"
}

echo "[8/8] Очистка кеша Vite (если есть)..."
if [ -d "node_modules/.vite" ]; then
    rm -rf node_modules/.vite
    echo "Кеш Vite очищен"
else
    echo "Кеш Vite не найден (пропущено)"
fi

echo ""
echo "============================================"
echo "Очистка кешей завершена!"
echo "============================================"
echo ""
echo "Дополнительные команды (опционально):"
echo "  - Очистка кеша Composer: composer clear-cache"
echo "  - Очистка кеша React (frontend): cd frontend && rm -rf node_modules/.cache"
echo ""

