# Исправление ошибки Composer на сервере

## Проблема

Ошибка: `Concurrent process failed with exit code [255]` при выполнении `composer install`

## Решение

### Вариант 1: Выполнить без параллельных процессов

```bash
# Отключите параллельные процессы
composer install --no-dev --optimize-autoloader --no-scripts

# Затем выполните скрипты отдельно
php artisan config:clear
php artisan cache:clear
```

### Вариант 2: Обновить composer и повторить

```bash
# Обновите composer
composer self-update

# Попробуйте снова
composer install --no-dev --optimize-autoloader --no-interaction
```

### Вариант 3: Использовать локальный composer.phar

```bash
# Если composer глобальный не работает, используйте локальный
php bin/composer install --no-dev --optimize-autoloader --no-interaction
```

### Вариант 4: Пропустить проблемные пакеты (если это dev зависимости)

```bash
# Установите только production зависимости
composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs
```

## Продолжение настройки после исправления composer

```bash
# 1. Настройте .env (если еще не настроен)
cp .env.example .env
nano .env  # Отредактируйте с правильными настройками

# 2. Сгенерируйте ключ приложения
php artisan key:generate

# 3. Создайте симлинк для storage
php artisan storage:link

# 4. Запустите миграции
php artisan migrate --force

# 5. Очистите кеши
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 6. Настройте безопасную директорию для git
git config --global --add safe.directory /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# 7. Проверьте настройку
git status
git remote -v
```

## Проверка после настройки

```bash
# Проверьте, что все работает
php artisan --version
php artisan route:list | head -5
```

## Если composer все еще не работает

Можно пропустить установку зависимостей, если они уже установлены:

```bash
# Просто обновите autoloader
composer dump-autoload --optimize --no-dev
```


