# Настройка .env на сервере

## Текущие настройки

Ваш .env на сервере уже содержит базовые настройки. Нужно добавить настройки для деплоя.

## Добавьте в .env на сервере

Откройте .env на сервере:

```bash
nano .env
```

Добавьте в конец файла:

```env
# Deploy settings
DEPLOY_TOKEN=ваш-секретный-токен-здесь
DEPLOY_SERVER_URL=https://trendagent.siteaccess.ru
```

## Где взять DEPLOY_TOKEN?

1. **Проверьте локальный .env** - там должен быть DEPLOY_TOKEN
2. **Или создайте новый токен** - это может быть любая случайная строка

Важно: DEPLOY_TOKEN на сервере и локально должен быть ОДИНАКОВЫМ!

## Пример полного .env для сервера

```env
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:9NW14cfNL6E6R/zaro6nFy5zuzJcLOmE55gCCylzuFw=
APP_DEBUG=false
APP_URL=https://trendagent.siteaccess.ru

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dsc23ytp_tragent
DB_USERNAME=dsc23ytp_tragent
DB_PASSWORD=esfmcU*!8Mmn

# Deploy settings
DEPLOY_TOKEN=ваш-секретный-токен
DEPLOY_SERVER_URL=https://trendagent.siteaccess.ru

# Остальные настройки...
```

## После добавления настроек

```bash
# Очистите кеш конфигурации
php artisan config:clear
php artisan config:cache
```

## Проверка

После настройки проверьте локально:

```bash
php artisan deploy --insecure --message "Тестовый деплой"
```

