# Инструкция по настройке деплоя на новом сервере

## 1. Найти путь к Composer на новом сервере

Выполните на сервере:

```bash
# Вариант 1: Через which (если composer в PATH)
which composer

# Вариант 2: Найти все возможные пути
find /home -name composer 2>/dev/null
find /usr -name composer 2>/dev/null

# Вариант 3: Проверить стандартные места
ls -la /usr/local/bin/composer
ls -la /usr/bin/composer
ls -la ~/.local/bin/composer
ls -la ~/.composer/vendor/bin/composer
```

**Результат:** Запишите полный путь, например:
- `/usr/local/bin/composer`
- `/home/username/.local/bin/composer`
- `/usr/bin/composer`

## 2. Найти путь к PHP на новом сервере

```bash
# Вариант 1: Через which
which php
which php8.2
which php8.3

# Вариант 2: Проверить версию
php --version
php8.2 --version
php8.3 --version
```

**Результат:** Запишите команду, которая работает, например:
- `php`
- `php8.2`
- `/usr/bin/php`

## 3. Настроить .env на сервере

Откройте `.env` файл на сервере и добавьте/обновите:

```env
# Путь к composer (используйте путь, найденный в шаге 1)
COMPOSER_PATH=/usr/local/bin/composer

# Или если composer локально в проекте:
# COMPOSER_PATH=/путь/к/проекту/bin/composer

# Путь к PHP (если нужен, обычно не требуется если php в PATH)
PHP_PATH=php8.2

# Токен деплоя (должен совпадать с локальным .env)
DEPLOY_TOKEN=ваш_токен_деплоя
```

## 4. Команды для выполнения на сервере

### Вариант A: Composer уже установлен глобально

Если `which composer` вернул путь, просто добавьте в `.env`:

```bash
cd /путь/к/проекту
nano .env  # или vim .env

# Добавьте:
COMPOSER_PATH=/путь/к/composer/из/which
```

### Вариант B: Установить composer локально в проекте (рекомендуется)

```bash
cd /путь/к/проекту

# Создать директорию для composer
mkdir -p bin

# Скачать и установить composer локально
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=bin --filename=composer
php -r "unlink('composer-setup.php');"

# Установить права
chmod +x bin/composer

# Проверить
./bin/composer --version

# Добавить в .env
echo "COMPOSER_PATH=$(pwd)/bin/composer" >> .env
```

### Вариант C: Composer в пользовательской директории

Если composer находится в `~/.local/bin/composer`:

```bash
cd /путь/к/проекту

# Найти полный путь
COMPOSER_FULL_PATH=$(readlink -f ~/.local/bin/composer || echo ~/.local/bin/composer)
echo "COMPOSER_PATH=$COMPOSER_FULL_PATH" >> .env
```

## 5. Проверка и очистка кешей

После настройки `.env`:

```bash
cd /путь/к/проекту

# Очистить кеш конфигурации Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Проверить что composer найден (если настроен COMPOSER_PATH)
php artisan tinker
# В tinker выполните:
# env('COMPOSER_PATH')
```

## 6. Проверка деплоя

Проверьте что все работает:

```bash
# Проверить что git настроен
cd /путь/к/проекту
git status

# Проверить что composer работает
# Если COMPOSER_PATH указан в .env:
php artisan tinker
# env('COMPOSER_PATH')

# Или напрямую:
/путь/к/composer --version
```

## 7. Тестовый деплой

После настройки выполните тестовый деплой с локальной машины:

```bash
php artisan deploy --insecure
```

Проверьте логи на сервере:

```bash
tail -f storage/logs/laravel.log
```

## Примеры для разных серверов

### Shared hosting (cPanel, ISPmanager)

```env
COMPOSER_PATH=/home/username/.local/bin/composer
PHP_PATH=php8.2
```

### VPS/Dedicated server

```env
COMPOSER_PATH=/usr/local/bin/composer
PHP_PATH=php
```

### Локальный composer в проекте

```env
COMPOSER_PATH=/var/www/project/bin/composer
PHP_PATH=php8.2
```

## Устранение проблем

### Composer не найден

1. Проверьте путь в `.env`:
   ```bash
   cat .env | grep COMPOSER_PATH
   ```

2. Проверьте что файл существует:
   ```bash
   ls -la $(grep COMPOSER_PATH .env | cut -d'=' -f2)
   ```

3. Очистите кеш:
   ```bash
   php artisan config:clear
   ```

### Permission denied

Если composer не выполняется:

```bash
# Использовать через PHP (уже реализовано в коде)
# Или установить права:
chmod +x /путь/к/composer
```

### PHP не найден

Добавьте в `.env`:
```env
PHP_PATH=/полный/путь/к/php
```

Или используйте алиас:
```env
PHP_PATH=php8.2
```

