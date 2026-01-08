# Команды для очистки всех кешей

## Быстрая очистка (все команды подряд)

### Windows (PowerShell/CMD):
```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan event:clear && php artisan optimize:clear && npm cache clean --force
```

### Linux/Mac:
```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan event:clear && php artisan optimize:clear && npm cache clean --force
```

## Пошаговая очистка

### 1. Laravel кеши (обязательно):
```bash
# Очистка кеша конфигурации
php artisan config:clear

# Очистка кеша приложения
php artisan cache:clear

# Очистка кеша маршрутов
php artisan route:clear

# Очистка кеша представлений (Blade)
php artisan view:clear

# Очистка кеша событий (если доступно)
php artisan event:clear

# Полная очистка всех оптимизационных кешей
php artisan optimize:clear
```

### 2. NPM кеш:
```bash
npm cache clean --force
```

### 3. Кеш Vite (если используется):
```bash
# Windows
rmdir /s /q node_modules\.vite

# Linux/Mac
rm -rf node_modules/.vite
```

### 4. Кеш React (frontend):
```bash
# Windows
cd frontend && rmdir /s /q node_modules\.cache && cd ..

# Linux/Mac
cd frontend && rm -rf node_modules/.cache && cd ..
```

### 5. Composer кеш (опционально):
```bash
composer clear-cache
```

## Использование готовых скриптов

### Windows:
```bash
clear-all-cache.bat
```

### Linux/Mac:
```bash
chmod +x clear-all-cache.sh
./clear-all-cache.sh
```

## После очистки кешей (для продакшена)

После очистки кешей рекомендуется пересоздать их для оптимизации:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Важно

- **В разработке**: можно не кешировать (быстрее обновления)
- **В продакшене**: обязательно кешировать для производительности
- После изменения `.env` файла: обязательно выполнить `php artisan config:clear`
- После изменения маршрутов: выполнить `php artisan route:clear` или `php artisan route:cache`

