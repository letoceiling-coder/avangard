# Исправление .env на сервере

## Проблемы в текущем .env

1. `APP_URL=http://localhost` - должно быть `https://trendagent.siteaccess.ru`
2. `APP_ENV=local` - должно быть `production`
3. `APP_DEBUG=true` - должно быть `false`

## Команды для исправления на сервере

```bash
# Откройте .env
nano .env

# Измените следующие строки:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trendagent.siteaccess.ru

# Сохраните (Ctrl+O, Enter, Ctrl+X)

# Очистите и обновите кеш
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Исправление MIME типов

Проблема с MIME type может быть из-за конфигурации веб-сервера. Проверьте `.htaccess` в `public/`:

```bash
# На сервере
cat public/.htaccess
```

Убедитесь, что есть строки для JavaScript модулей:

```apache
<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType application/javascript .mjs
    AddType text/css .css
</IfModule>
```

