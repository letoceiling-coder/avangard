# Развертывание проекта на Beget

## Команды для выполнения на сервере

**Репозиторий:** https://github.com/letoceiling-coder/avangard  
**Директория:** ~/trendagent.siteaccess.ru/public_html

### Вариант 1: Стандартная структура Laravel (рекомендуется)

На Beget нужно настроить DocumentRoot на `public_html/public` через панель управления. Если это невозможно, используйте Вариант 2.

### 1. Клонирование/обновление проекта
```bash
# Если проект еще не склонирован:
git clone https://github.com/letoceiling-coder/avangard.git .

# Если проект уже склонирован:
git pull origin main
```

### 3. Установка зависимостей
```bash
# PHP зависимости
composer install --no-dev --optimize-autoloader

# Node.js зависимости для Laravel (Vue админка)
# ВАЖНО: Для сборки нужны dev зависимости (vite)
npm install

# Node.js зависимости для React приложения
cd frontend
npm install
npm run build
cd ..
```

### 4. Сборка фронтенда
```bash
# Сборка Vue админки (Laravel assets)
npm run build
```

### 5. Настройка окружения
```bash
# Копирование .env файла (если его нет)
cp .env.example .env

# Редактирование .env (укажите свои данные БД и настройки)
nano .env
# Установите:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://trendagent.siteaccess.ru
# DB_DATABASE, DB_USERNAME, DB_PASSWORD - данные от Beget
```

### 6. Настройка Laravel
```bash
# Генерация ключа приложения
php artisan key:generate

# Запуск миграций
php artisan migrate --force

# Очистка и кеширование (оптимизация)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Настройка прав доступа
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 755 public
```

---

### Вариант 2: Если DocumentRoot нельзя изменить (альтернативный)

Если Beget не позволяет настроить DocumentRoot на `public_html/public`, используйте этот вариант:

### 1-3. Выполните шаги 1-3 из Варианта 1

### 4. Перемещение файлов из public в корень
```bash
# Скопировать содержимое public в корень (сохраняем структуру)
cp -r public/* .
cp public/.htaccess . 2>/dev/null || true

# Обновить пути в index.php (путь к vendor и bootstrap изменится на ../)
sed -i "s|__DIR__.'/../|__DIR__.'/../|g" index.php
```

### 5-7. Выполните шаги 5-7 из Варианта 1

---

## Полный скрипт развертывания (Вариант 1 - стандартный)

```bash
cd ~/trendagent.siteaccess.ru/public_html && \
[ -d .git ] && git pull origin main || git clone https://github.com/letoceiling-coder/avangard.git . && \
composer install --no-dev --optimize-autoloader && \
npm install && \
cd frontend && npm install && npm run build && cd .. && \
npm run build && \
[ -f .env ] || cp .env.example .env && \
php artisan key:generate --force && \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
chmod -R 775 storage bootstrap/cache && \
chmod -R 755 public && \
echo "✅ Deployment completed!"
```

## Полный скрипт развертывания (Вариант 2 - если DocumentRoot на корень)

```bash
cd ~/trendagent.siteaccess.ru/public_html && \
[ -d .git ] && git pull origin main || git clone https://github.com/letoceiling-coder/avangard.git . && \
composer install --no-dev --optimize-autoloader && \
npm install && \
cd frontend && npm install && npm run build && cd .. && \
npm run build && \
cp -r public/* . && \
cp public/.htaccess . 2>/dev/null || true && \
sed -i "s|__DIR__.'/../|__DIR__.'/../|g" index.php && \
[ -f .env ] || cp .env.example .env && \
php artisan key:generate --force && \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
chmod -R 775 storage bootstrap/cache && \
chmod -R 755 . && \
echo "✅ Deployment completed!"
```

## Настройка .env файла

Обязательно настройте следующие параметры в `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trendagent.siteaccess.ru

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ваша_база
DB_USERNAME=ваш_пользователь
DB_PASSWORD=ваш_пароль

# Остальные настройки...
```

## Проверка после развертывания

1. Проверьте доступность сайта: `https://trendagent.siteaccess.ru`
2. Проверьте логи Laravel: `tail -f storage/logs/laravel.log`
3. Проверьте права доступа: `ls -la storage bootstrap/cache`

## Важные замечания

- На Beget обычно PHP версия 8.1-8.2, убедитесь что она совместима
- На Beget может быть ограничение на время выполнения скриптов
- Если есть проблемы с правами, используйте файловый менеджер в панели Beget
- Node.js обычно доступен через `node` или нужно использовать полный путь `/usr/bin/node`
- npm может быть доступен как `npm` или через полный путь

