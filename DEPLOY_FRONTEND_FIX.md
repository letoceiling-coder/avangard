# Исправление проблемы с деплоем фронтенда

## Проблема
После деплоя изменения в React компонентах не видны на сайте.

## Причина
На сервере не была выполнена пересборка фронтенда после `git pull`.

## Решение

### Вариант 1: Ручная пересборка на сервере

Выполните на сервере следующие команды:

```bash
# Перейти в директорию проекта
cd ~/trendagent.siteaccess.ru/public_html

# Обновить код из git
git pull origin main

# Перейти в директорию frontend
cd frontend

# Установить зависимости (если нужно)
npm install

# Пересобрать React приложение
npm run build

# Вернуться в корень
cd ..

# Очистить кэш Laravel (опционально, но рекомендуется)
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Вариант 2: Полный деплой через скрипт

Если используете скрипт деплоя, выполните:

```bash
cd ~/trendagent.siteaccess.ru/public_html && \
git pull origin main && \
composer install --no-dev --optimize-autoloader && \
npm install && \
cd frontend && npm install && npm run build && cd .. && \
npm run build && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php artisan cache:clear && \
echo "✅ Deployment completed!"
```

## Проверка после деплоя

1. **Проверьте наличие собранных файлов:**
   ```bash
   ls -la public/frontend/assets/
   ```
   Должны быть файлы `index-*.js` и `index-*.css` с актуальными хешами.

2. **Проверьте дату модификации файлов:**
   ```bash
   ls -lh public/frontend/assets/ | head -5
   ```
   Дата должна быть свежей (сегодняшней).

3. **Очистите кэш браузера:**
   - Откройте сайт в режиме инкогнито
   - Или используйте Ctrl+Shift+R (Windows/Linux) / Cmd+Shift+R (Mac) для жесткой перезагрузки

4. **Проверьте консоль браузера:**
   - Откройте DevTools (F12)
   - Перейдите на вкладку Console
   - Проверьте наличие ошибок загрузки файлов

## Автоматизация деплоя

Чтобы избежать этой проблемы в будущем, добавьте пересборку фронтенда в ваш деплой-скрипт или git hook.

### Git Hook (post-receive)

Создайте файл `.git/hooks/post-receive` на сервере:

```bash
#!/bin/bash
cd ~/trendagent.siteaccess.ru/public_html
git pull origin main
cd frontend && npm install && npm run build && cd ..
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Сделайте его исполняемым:
```bash
chmod +x .git/hooks/post-receive
```

## Частые проблемы

### 1. Node.js не найден
```bash
# Проверьте версию Node.js
node --version

# Если не установлен, установите через nvm или используйте полный путь
/usr/bin/node --version
```

### 2. npm не найден
```bash
# Проверьте версию npm
npm --version

# Если не установлен, используйте полный путь
/usr/bin/npm --version
```

### 3. Ошибки при сборке
```bash
# Очистите node_modules и переустановите
cd frontend
rm -rf node_modules package-lock.json
npm install
npm run build
```

### 4. Файлы не обновляются
```bash
# Удалите старые файлы и пересоберите
rm -rf public/frontend/*
cd frontend
npm run build
```

## Проверка конкретного компонента

Если проблема только с `PropertyHeroBlock`, проверьте:

1. **Файл существует на сервере:**
   ```bash
   cat frontend/src/components/PropertyHeroBlock.tsx | head -20
   ```

2. **Файл содержит изменения:**
   ```bash
   grep -n "grid-cols-\[60%_40%\]" frontend/src/components/PropertyHeroBlock.tsx
   ```
   Должна быть строка с `grid-cols-[60%_40%]`.

3. **Файл собран в bundle:**
   ```bash
   grep -r "PropertyHeroBlock" public/frontend/assets/*.js | head -1
   ```

## Быстрая проверка

Выполните эту команду для быстрой проверки и исправления:

```bash
cd ~/trendagent.siteaccess.ru/public_html && \
cd frontend && \
npm run build && \
cd .. && \
php artisan cache:clear && \
echo "✅ Frontend rebuilt!"
```

