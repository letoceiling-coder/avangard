# Настройка Git на сервере

## Проблема

Сервер сообщает: "Директория не является git репозиторием" в пути `/home/d/dsc23ytp/trendagent.siteaccess.ru/public_html`

## Решение

### Вариант 1: Клонировать репозиторий (рекомендуется)

Если на сервере еще нет проекта, клонируйте его:

```bash
# На сервере
cd /home/d/dsc23ytp/trendagent.siteaccess.ru
# Удалите или переименуйте public_html если он существует
mv public_html public_html.backup  # или удалите: rm -rf public_html

# Клонируйте репозиторий
git clone https://github.com/letoceiling-coder/avangard.git public_html

# Перейдите в директорию
cd public_html

# Установите зависимости
composer install --no-dev --optimize-autoloader
npm install
cd frontend && npm install && cd ..

# Настройте .env
cp .env.example .env
# Отредактируйте .env с правильными настройками БД и сервера

# Создайте симлинки для storage
php artisan storage:link

# Запустите миграции
php artisan migrate --force
```

### Вариант 2: Инициализировать git в существующей директории

Если проект уже есть на сервере:

```bash
# На сервере
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# Инициализируйте git
git init

# Добавьте remote
git remote add origin https://github.com/letoceiling-coder/avangard.git

# Получите все ветки
git fetch origin

# Переключитесь на main и сбросьте на origin/main
git checkout -b main
git reset --hard origin/main

# Установите зависимости
composer install --no-dev --optimize-autoloader
npm install
cd frontend && npm install && cd ..
```

### Вариант 3: Проверить путь к проекту

Возможно, путь к проекту на сервере отличается. Проверьте в `.env` на сервере:

```env
# Убедитесь, что путь правильный
# Laravel автоматически определяет base_path() как директорию с artisan
```

## После настройки

После того как git репозиторий настроен на сервере, деплой будет работать автоматически:

```bash
# Локально
php artisan deploy --insecure --message "Описание изменений"
```

Команда автоматически:
1. Соберет фронтенд
2. Закоммитит изменения
3. Отправит в GitHub
4. Отправит запрос на сервер для `git pull` и обновления

## Проверка

После настройки проверьте на сервере:

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html
git status
git remote -v
```

Должно показать:
- Статус git репозитория
- Remote: `origin https://github.com/letoceiling-coder/avangard.git`

