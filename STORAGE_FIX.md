# Исправление проблемы с доступом к файлам Storage

## Текущий статус
✅ Символическая ссылка создана: `public/storage -> /home/k/kamivoan/wows/public_html/storage/app/public`
✅ Файлы существуют в `storage/app/public/support/attachments/`
✅ Роут зарегистрирован: `GET|HEAD storage/{path} ... storage.local`
✅ Файлы доступны через символическую ссылку: `ls -la public/storage/support/attachments/` показывает все файлы
❌ Доступ через веб возвращает 403 Forbidden

**Проблема:** Веб-сервер (nginx/apache) блокирует доступ к `/storage` или обрабатывает запросы раньше, чем они доходят до Laravel.

## Решение

### Вариант 1: Проверьте доступ через символическую ссылку

```bash
cd ~/wows/public_html
ls -la public/storage/support/attachments/
```

Если файлы видны - символическая ссылка работает, проблема в веб-сервере.
Если файлы не видны - проблема в символической ссылке.

### Вариант 2: Используйте Laravel роут (уже настроен)

Роут `/storage/{path}` уже настроен в `routes/web.php` и должен обрабатывать запросы, даже если символическая ссылка не работает.

**Проверьте, что роут зарегистрирован:**
```bash
cd ~/wows/public_html
php8.2 artisan route:list | grep storage
```

Должен быть роут: `GET /storage/{path}`

**Проверьте логи при попытке доступа:**
```bash
tail -f storage/logs/laravel.log | grep -i "Storage file"
```

### Вариант 3: Если используется Nginx (РЕКОМЕНДУЕТСЯ)

Проблема скорее всего в конфигурации nginx. **Обязательно** добавьте в конфигурацию сервера:

```nginx
# Передача запросов к /storage в Laravel
# Это гарантирует, что роут Laravel обработает запрос, даже если символическая ссылка не работает
location /storage {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Важно:** Этот блок должен быть ДО основного `location /`, чтобы обрабатываться первым.

Полный пример конфигурации:
```nginx
server {
    listen 80;
    server_name wows-in.ru;
    root /home/k/kamivoan/wows/public_html/public;
    index index.php;

    # Обработка /storage через Laravel (ПЕРВЫМ!)
    location /storage {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Основной location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

После изменения конфигурации nginx:
```bash
sudo nginx -t  # Проверка конфигурации
sudo systemctl reload nginx  # Перезагрузка
```

### Вариант 4: Проверьте права доступа

```bash
cd ~/wows/public_html
chmod -R 755 storage/app/public
chmod 755 public/storage
ls -la public/storage
```

### Тест доступа

```bash
# Проверка через curl
curl -I https://wows-in.ru/storage/support/attachments/WGiIBB3OwL9PS5101jRedPnuQbLh2NrGqBjVXaF1.png

# Должен вернуть:
# HTTP/1.1 200 OK
# Content-Type: image/png
```

Если возвращает 403 - проблема в веб-сервере или правах.
Если возвращает 404 - файл не найден или роут не работает.
Если возвращает 200 - все работает!

## Отладка

1. **Проверьте логи Laravel:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i storage
   ```

2. **Проверьте логи веб-сервера:**
   ```bash
   # Nginx
   sudo tail -f /var/log/nginx/error.log
   
   # Apache
   sudo tail -f /var/log/apache2/error.log
   ```

3. **Проверьте, что файлы доступны:**
   ```bash
   ls -la storage/app/public/support/attachments/
   ls -la public/storage/support/attachments/  # Через символическую ссылку
   ```

## Рекомендация

Если символическая ссылка не работает из-за настроек веб-сервера, **используйте Laravel роут** - он уже настроен и должен работать. Просто убедитесь, что роут зарегистрирован и обрабатывает запросы.
