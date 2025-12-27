#!/bin/bash
# Команды для развертывания проекта на Beget
# Репозиторий: https://github.com/letoceiling-coder/avangard
# Директория: ~/trendagent.siteaccess.ru/public_html

# Переход в директорию проекта
cd ~/trendagent.siteaccess.ru/public_html

# Клонирование или обновление проекта
if [ -d .git ]; then
    echo "Обновление репозитория..."
    git pull origin main
else
    echo "Клонирование репозитория..."
    git clone https://github.com/letoceiling-coder/avangard.git .
fi

# Установка PHP зависимостей
echo "Установка Composer зависимостей..."
composer install --no-dev --optimize-autoloader

# Установка Node.js зависимостей для Laravel
# ВАЖНО: Для сборки нужны dev зависимости (vite)
echo "Установка npm зависимостей для Laravel..."
npm install

# Установка зависимостей и сборка React приложения
echo "Установка зависимостей и сборка React приложения..."
cd frontend
npm install
npm run build
cd ..

# Сборка Laravel assets (Vue админка)
echo "Сборка Laravel assets..."
npm run build

# Настройка .env файла
if [ ! -f .env ]; then
    echo "Создание .env файла из .env.example..."
    cp .env.example .env
    echo "⚠️  ВАЖНО: Отредактируйте .env файл (nano .env) и настройте:"
    echo "   - DB_DATABASE, DB_USERNAME, DB_PASSWORD (данные от Beget)"
    echo "   - APP_ENV=production"
    echo "   - APP_DEBUG=false"
    echo "   - APP_URL=https://trendagent.siteaccess.ru"
fi

# Генерация ключа приложения
echo "Генерация ключа приложения..."
php artisan key:generate --force

# Запуск миграций
echo "Запуск миграций..."
php artisan migrate --force

# Кеширование для оптимизации
echo "Кеширование конфигурации..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Настройка прав доступа
echo "Настройка прав доступа..."
chmod -R 775 storage bootstrap/cache
chmod -R 755 public

echo "✅ Развертывание завершено!"
echo ""
echo "⚠️  НЕ ЗАБУДЬТЕ:"
echo "   1. Отредактировать .env файл (если создан новый)"
echo "   2. Настроить DocumentRoot на public_html/public через панель Beget"
echo "   3. Проверить доступность сайта: https://trendagent.siteaccess.ru"

