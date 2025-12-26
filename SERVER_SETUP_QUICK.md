# Быстрая инструкция по установке на сервере

## Полная последовательность команд

```bash
# 1. Настройка проекта
composer install --no-dev --optimize-autoloader
cp .env.example .env
php8.2 artisan key:generate

# ⚠️ НАСТРОЙТЕ .env ФАЙЛ ВРУЧНУЮ! (база данных, токены и т.д.)

# 2. Миграции
php8.2 artisan migrate:fresh

# 3. Заполнение данных
php8.2 artisan db:seed

# 4. Импорт иконок рулетки

# 5. Обновление секторов с иконками

# 6. Установка Node.js зависимостей
npm install
cd frontend && npm install && cd ..

# 7. Сборка фронтенда
npm run build:all

# 8. Права доступа
chmod -R 775 storage bootstrap/cache public/upload
chown -R www-data:www-data storage bootstrap/cache public/upload

# 9. Оптимизация для production
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
composer install --optimize-autoloader --no-dev
```

## Важно!

1. **Обязательно настройте `.env` файл** перед выполнением миграций:
   - `DB_*` параметры базы данных
   - `TELEGRAM_BOT_TOKEN`
   - `DEPLOY_SERVER_URL` и `DEPLOY_TOKEN`

2. **Проверьте после установки:**
   - Медиатека: "Медиа" → папка "общая" (должны быть иконки)
   - Фронтенд: главная страница должна загружаться

3. **Если иконки не появились:**
   ```bash
   ```

4. **Для обновления каналов:**
   ```bash
   ```

## Настройка cron для автоматических задач

Добавьте в crontab:

```bash
* * * * * cd /path-to-project && php8.2 artisan schedule:run >> /dev/null 2>&1
```

---

**Полная документация:** см. `SERVER_SETUP.md`

