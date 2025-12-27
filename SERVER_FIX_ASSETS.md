# Исправление проблемы с assets на сервере

## Проблема
В `public/assets/` существует обычная директория (не символическая ссылка), которая содержит старые файлы. Это мешает созданию символической ссылки.

## Решение

Выполните на сервере:

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# 1. Проверьте содержимое старой директории (для безопасности)
ls -la public/assets/

# 2. Удалите старую директорию assets
# ВНИМАНИЕ: Убедитесь, что там нет важных файлов!
rm -rf public/assets/

# 3. Создайте символическую ссылку
ln -sfn frontend/assets public/assets

# 4. Проверьте, что ссылка создана правильно
ls -la public/ | grep assets

# Должно быть:
# lrwxrwxrwx ... assets -> frontend/assets

# 5. Проверьте доступность файла через ссылку
ls -la public/assets/index-REDgvNva.js

# 6. Проверьте MIME тип
curl -I https://trendagent.siteaccess.ru/assets/index-REDgvNva.js 2>&1 | grep -i "content-type"

# Должно вернуть:
# content-type: application/javascript; charset=utf-8

# 7. Если все еще text/html, очистите кеш Nginx (если есть доступ)
# Или просто подождите несколько секунд - Nginx может кешировать ответы
```

## Альтернатива: Проверка через прямой путь

Если символическая ссылка не работает, можно временно проверить прямой доступ:

```bash
# Проверьте прямой доступ к файлу
curl -I https://trendagent.siteaccess.ru/frontend/assets/index-REDgvNva.js 2>&1 | grep -i "content-type"
```

Но это не решит проблему, так как в `index.html` указаны пути `/assets/...`, а не `/frontend/assets/...`.

## После исправления

1. Откройте https://trendagent.siteaccess.ru в браузере
2. Откройте консоль разработчика (F12)
3. Проверьте, что нет ошибок загрузки модулей
4. Проверьте Network tab - файлы должны загружаться с правильным Content-Type

