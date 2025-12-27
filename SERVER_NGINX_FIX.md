# Исправление проблемы с MIME типами для /assets/

## Проблема
JavaScript файлы отдаются с `content-type: text/html` вместо `application/javascript`.

## Возможные причины

1. **Nginx обрабатывает `/assets/` как статические файлы** до того, как запрос дойдет до Laravel
2. **Файл не найден** и возвращается index.html
3. **Роут не срабатывает** из-за кеша

## Диагностика на сервере

```bash
# 1. Проверьте, существует ли файл
ls -la public/frontend/assets/index-REDgvNva.js

# 2. Проверьте, что файл читается
head -c 100 public/frontend/assets/index-REDgvNva.js

# 3. Проверьте логи Laravel при запросе
tail -f storage/logs/laravel.log
# В другом терминале:
curl https://trendagent.siteaccess.ru/assets/index-REDgvNva.js > /dev/null

# 4. Проверьте, доходит ли запрос до Laravel
# Если в логах нет записей - Nginx обрабатывает запрос напрямую

# 5. Проверьте конфигурацию Nginx (если есть доступ)
# Обычно находится в /etc/nginx/sites-enabled/ или через панель хостинга
```

## Решение 1: Проверка файла и прямого доступа

```bash
# Проверьте прямой доступ к файлу через public/frontend/assets/
curl -I https://trendagent.siteaccess.ru/frontend/assets/index-REDgvNva.js 2>&1 | grep -i "content-type"
```

## Решение 2: Добавление логирования в роут

Если файл существует, но роут не срабатывает, нужно добавить логирование.

## Решение 3: Настройка Nginx

Если Nginx обрабатывает `/assets/` напрямую, нужно настроить его так, чтобы запросы к `/assets/` передавались в Laravel.

Обычно в конфигурации Nginx есть что-то вроде:
```nginx
location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

Нужно исключить `/assets/` из этой обработки или настроить правильные MIME типы.

## Решение 4: Использование прямого пути

Если проблема в роутинге, можно использовать прямой путь `/frontend/assets/` вместо `/assets/`.

