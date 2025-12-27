# Исправление проблемы с /assets/ через символическую ссылку

## Проблема
Nginx обрабатывает статические файлы напрямую и не находит файлы в `public/assets/`, поэтому возвращает `index.html` вместо JavaScript файлов.

## Решение: Создать символическую ссылку

Выполните на сервере:

```bash
# 1. Перейдите в директорию проекта
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# 2. Удалите старую директорию assets (если есть и пустая)
# ВНИМАНИЕ: Не удаляйте, если там есть важные файлы!
# Сначала проверьте содержимое:
ls -la public/assets/

# Если там только старые файлы, которые не нужны, можно удалить:
# rm -rf public/assets/

# 3. Создайте символическую ссылку
ln -sfn frontend/assets public/assets

# 4. Проверьте, что ссылка создана
ls -la public/ | grep assets

# Должно быть что-то вроде:
# lrwxrwxrwx ... assets -> frontend/assets

# 5. Проверьте доступность файла
ls -la public/assets/index-REDgvNva.js

# 6. Проверьте MIME тип
curl -I https://trendagent.siteaccess.ru/assets/index-REDgvNva.js 2>&1 | grep -i "content-type"

# Должно вернуть:
# content-type: application/javascript; charset=utf-8
```

## Альтернативное решение: Изменить base в vite.config.ts

Если символическая ссылка не работает, можно изменить базовый путь в Vite:

1. Изменить `frontend/vite.config.ts`:
```typescript
export default defineConfig(({ mode }) => ({
  base: '/frontend/',
  // ... остальная конфигурация
}));
```

2. Пересобрать фронтенд:
```bash
cd frontend
npm run build
```

3. Обновить пути в `index.html` на `/frontend/assets/...`

Но это потребует изменений в коде и пересборки.

## Рекомендация

Используйте символическую ссылку - это самое простое решение, которое не требует изменений в коде.

