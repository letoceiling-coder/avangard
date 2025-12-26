# Руководство по откату поддержки на сервере

## Текущая ситуация

- **Локально:** Откат выполнен к коммиту `cf9ef8f` (до создания поддержки)
- **На сервере:** Код обновлен через `php artisan deploy --force --insecure`
- **Миграция:** `2025_12_13_000745_drop_support_tables.php` создана и отправлена
- **Статус:** Деплой выполнен успешно, но миграция может не выполниться автоматически

## Процесс деплоя

### 1. Подготовка локально

```bash
# Убедитесь, что вы на правильном коммите
git log --oneline -1
# Должен быть: cf9ef8f Deploy: 2025-12-12 11:52:33

# Проверьте статус
git status
# Должно быть: working tree clean
```

### 2. Отправка на сервер

```bash
# Если нужно принудительно обновить удаленный репозиторий
git push origin main --force

# ИЛИ если хотите создать новую ветку для безопасного деплоя
git checkout -b rollback-support
git push origin rollback-support
```

### 3. Деплой на сервере

После деплоя на сервере будет:
- ✅ Код без поддержки (как локально)
- ⚠️ Таблицы поддержки останутся в БД (если были созданы)

### 4. Удаление таблиц на сервере

**ВАЖНО:** После деплоя проверьте, выполнилась ли миграция автоматически.

Выполните миграцию для удаления таблиц (если не выполнилась автоматически):

```bash
php artisan migrate
```

Или принудительно выполните конкретную миграцию:

```bash
php artisan migrate --path=database/migrations/2025_12_13_000745_drop_support_tables.php
```

Это выполнит миграцию `2025_12_13_000745_drop_support_tables.php`, которая:
- Удалит все foreign keys из таблиц поддержки
- Удалит `message_sync_logs`
- Удалит `support_ticket_messages`
- Удалит `support_tickets`
- Удалит `ticket_chats`

## Проверка перед деплоем

### Локально проверьте:

```bash
# 1. Убедитесь, что нет файлов поддержки
Test-Path app\Http\Controllers\Api\Admin\SupportController.php
# Должно вернуть: False

Test-Path app\Models\SupportTicket.php
# Должно вернуть: False

# 2. Проверьте меню
Select-String -Path app\Services\AdminMenu.php -Pattern "Поддержка"
# Не должно найти совпадений

# 3. Проверьте маршруты
Select-String -Path resources\js\admin.js -Pattern "admin.support"
# Не должно найти совпадений
```

## Важно!

### ⚠️ ВНИМАНИЕ: Потеря данных

Если на сервере есть данные в таблицах поддержки (тикеты, сообщения), они будут **безвозвратно удалены** при выполнении миграции `drop_support_tables`.

### Рекомендации:

1. **Сделайте резервную копию БД перед деплоем:**
   ```bash
   mysqldump -u user -p database_name > backup_before_rollback.sql
   ```

2. **Проверьте, есть ли данные:**
   ```sql
   SELECT COUNT(*) FROM support_tickets;
   SELECT COUNT(*) FROM support_ticket_messages;
   ```

3. **Если данные важны, экспортируйте их:**
   ```sql
   SELECT * FROM support_tickets INTO OUTFILE '/tmp/support_tickets_backup.csv';
   SELECT * FROM support_ticket_messages INTO OUTFILE '/tmp/support_messages_backup.csv';
   ```

## Последовательность действий на сервере

```bash
# 1. Сделать резервную копию БД
mysqldump -u user -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Получить обновления из git
git fetch origin
git reset --hard origin/main  # или origin/rollback-support

# 3. Выполнить миграции (удалит таблицы поддержки)
php artisan migrate

# 4. Очистить кеш
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Пересобрать фронтенд (если нужно)
npm run build
# или
npm run dev
```

## Проверка после деплоя

```bash
# Проверить, что таблицы удалены
php artisan tinker
>>> Schema::hasTable('support_tickets')
# Должно вернуть: false

>>> Schema::hasTable('support_ticket_messages')
# Должно вернуть: false

>>> Schema::hasTable('ticket_chats')
# Должно вернуть: false
```

## Откат миграции (если нужно)

Если по какой-то причине нужно откатить миграцию удаления:

```bash
# НО: метод down() в миграции пустой, так как функциональность удалена
# Для восстановления нужно:
# 1. Восстановить миграции из git
# 2. Выполнить их заново
```

## Альтернатива: Мягкое удаление

Если хотите сохранить данные, но скрыть функциональность:

1. Не выполняйте миграцию `drop_support_tables`
2. Просто удалите код (что уже сделано)
3. Таблицы останутся в БД, но не будут использоваться

Это безопаснее, если данные могут понадобиться в будущем.

