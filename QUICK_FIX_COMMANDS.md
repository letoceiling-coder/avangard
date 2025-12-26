# Быстрое решение проблем с зависанием

## Если команда зависла

### 1. Прервать выполнение
Нажмите `Ctrl+C` в терминале

### 2. Проверить зависшие процессы

**Windows:**
```cmd
tasklist | findstr node
tasklist | findstr php
```

**Linux/Mac:**
```bash
ps aux | grep node
ps aux | grep php
```

### 3. Завершить процессы при необходимости

**Windows:**
```cmd
taskkill /F /IM node.exe
taskkill /F /IM php.exe
```

**Linux/Mac:**
```bash
pkill node
pkill php
```

### 4. Очистить кеши и повторить

```bash
npm cache clean --force
php artisan cache:clear
```

## Предотвращение зависаний

### Для npm команд:

Используйте готовые скрипты:
```bash
# Windows
scripts\safe-build-admin.bat

# Linux/Mac  
chmod +x scripts/safe-build-admin.sh
./scripts/safe-build-admin.sh
```

### Для PHP Artisan:

Всегда используйте флаги:
```bash
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
```

### Для Git:

Настройте для неинтерактивного режима:
```bash
git config --global core.askPass ""
git config --global credential.helper manager
```

## Диагностика проблем

Запустите скрипт диагностики:
```bash
php scripts/check-commands.php
```

Он покажет:
- Версии инструментов
- Наличие блокировок
- Права доступа
- Запущенные процессы

