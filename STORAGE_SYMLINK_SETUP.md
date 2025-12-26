# Настройка символической ссылки для Storage на Production

## Проблема
Файлы из `storage/app/public` не доступны через веб-интерфейс (ошибка 403 Forbidden).

## Важно: Выполнить на TMA проекте (wows-in.ru), а не на CRM!

## Решение 1: Создать символическую ссылку (рекомендуется)

### На Linux/Unix сервере (TMA проект):
```bash
# Подключитесь к серверу TMA (wows-in.ru)
ssh user@wows-in.ru
cd ~/wows-in.ru/public_html  # или путь к вашему проекту
php8.2 artisan storage:link --force
```

### На Windows сервере:
```bash
cd C:\path\to\project
php artisan storage:link --force
```

### Проверка символической ссылки:
```bash
ls -la public/storage
# Должна быть символическая ссылка: public/storage -> ../storage/app/public
```

### Проверка:
```bash
ls -la public/storage
# Должна быть символическая ссылка на storage/app/public
```

## Решение 2: Настройка веб-сервера

### Для Nginx:
Добавьте в конфигурацию сервера:

```nginx
location /storage {
    alias /path/to/project/storage/app/public;
    try_files $uri =404;
    
    # Разрешить доступ к файлам
    location ~ \.(jpg|jpeg|png|gif|webp|pdf|doc|docx|txt|zip|rar|7z)$ {
        access_log off;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### Для Apache:
Убедитесь, что в `.htaccess` в `public/storage/` есть:

```apache
Options -Indexes
<IfModule mod_rewrite.c>
    RewriteEngine Off
</IfModule>

<FilesMatch "\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|txt|zip|rar|7z)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

## Решение 3: Использование Laravel роута (уже настроено)

Если символическая ссылка не работает, файлы будут отдаваться через Laravel роут `/storage/{path}`.

**Проверка работы роута:**
- Откройте в браузере: `https://wows-in.ru/storage/support/attachments/test.png`
- Если файл существует, он должен загрузиться
- Если 404 - файл не найден
- Если 403 - проблема с правами доступа

## Права доступа

Убедитесь, что веб-сервер имеет права на чтение файлов:

```bash
# Linux/Unix (TMA проект)
cd ~/wows-in.ru/public_html  # или путь к вашему проекту
chmod -R 755 storage/app/public
# chown может не работать без sudo - это нормально, если файлы уже принадлежат правильному пользователю

# Проверка
ls -la storage/app/public/support/attachments/
ls -la public/storage  # Проверка символической ссылки
```

**Примечание:** Если `chown` выдает ошибку "Operation not permitted" - это нормально, если файлы уже принадлежат правильному пользователю или у вас нет прав sudo.

## Отладка

Проверьте логи Laravel:
```bash
# На TMA сервере
cd ~/wows-in.ru/public_html
tail -f storage/logs/laravel.log | grep -i storage
```

Проверьте, что файлы действительно сохраняются:
```bash
ls -la storage/app/public/support/attachments/
```

Проверьте, что символическая ссылка работает:
```bash
# Должна быть ссылка
ls -la public/storage

# Должен быть доступ к файлам через ссылку
ls -la public/storage/support/attachments/
```

Проверьте доступ через браузер:
- Откройте: `https://wows-in.ru/storage/support/attachments/имя_файла.png`
- Если 403 - проблема с правами или веб-сервером
- Если 404 - файл не найден или символическая ссылка не работает
- Если загружается - все работает!

## Автоматическое создание символической ссылки при деплое

Добавьте в скрипт деплоя:
```bash
php8.2 artisan storage:link --force
```

## Если символическая ссылка не работает

Если после создания символической ссылки файлы все равно не доступны (403), используйте Laravel роут:

1. Роут уже настроен в `routes/web.php` - он обрабатывает запросы к `/storage/{path}`
2. Файлы будут отдаваться через Laravel, даже если символическая ссылка не работает
3. Проверьте логи: `tail -f storage/logs/laravel.log | grep "Storage file"`

## Быстрая проверка на TMA сервере

```bash
# 1. Создать символическую ссылку
cd ~/wows-in.ru/public_html
php8.2 artisan storage:link --force

# 2. Проверить права
chmod -R 755 storage/app/public

# 3. Проверить, что файлы есть
ls -la storage/app/public/support/attachments/

# 4. Проверить символическую ссылку
ls -la public/storage

# 5. Очистить кеш
php8.2 artisan route:clear
php8.2 artisan config:clear
```

