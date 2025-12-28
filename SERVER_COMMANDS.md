# Команды для настройки Git на сервере

## Шаг 1: Подключитесь к серверу по SSH

```bash
ssh ваш-пользователь@trendagent.siteaccess.ru
```

## Шаг 2: Перейдите в директорию проекта

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html
```

## Шаг 3: Проверьте текущее состояние

```bash
# Проверьте, есть ли уже .git
ls -la | grep .git

# Проверьте структуру проекта
ls -la
```

## Шаг 4: Инициализируйте Git репозиторий

```bash
# Инициализируйте git (если еще не инициализирован)
git init

# Добавьте remote репозиторий
git remote add origin https://github.com/letoceiling-coder/avangard.git

# Или если remote уже существует, обновите его
git remote set-url origin https://github.com/letoceiling-coder/avangard.git

# Проверьте remote
git remote -v
```

## Шаг 5: Получите код из репозитория

```bash
# Получите все ветки
git fetch origin

# Переключитесь на ветку main
git checkout -b main

# Сбросьте локальную ветку на origin/main (это перезапишет локальные файлы!)
# ВНИМАНИЕ: Это удалит все локальные изменения, которых нет в git!
git reset --hard origin/main
```

## Шаг 6: Если нужно сохранить локальные изменения

Если на сервере есть важные локальные изменения (например, .env файл):

```bash
# Сначала сохраните важные файлы
cp .env .env.backup
cp public/.htaccess public/.htaccess.backup

# Затем выполните reset
git reset --hard origin/main

# Восстановите важные файлы
cp .env.backup .env
cp public/.htaccess.backup public/.htaccess
```

## Шаг 7: Установите зависимости

```bash
# Composer зависимости
composer install --no-dev --optimize-autoloader

# NPM зависимости (если нужно)
npm install
cd frontend && npm install && cd ..
```

## Шаг 8: Настройте .env файл

```bash
# Если .env не существует, скопируйте из примера
cp .env.example .env

# Отредактируйте .env (используйте nano или vi)
nano .env
# Или
vi .env

# Убедитесь, что в .env есть:
# - DEPLOY_TOKEN (должен совпадать с локальным)
# - Правильные настройки БД
# - APP_URL
```

## Шаг 9: Настройте Laravel

```bash
# Сгенерируйте ключ приложения (если нужно)
php artisan key:generate

# Создайте симлинк для storage
php artisan storage:link

# Запустите миграции
php artisan migrate --force

# Очистите кеши
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Шаг 10: Проверьте настройку

```bash
# Проверьте git статус
git status

# Проверьте remote
git remote -v

# Проверьте текущую ветку и коммит
git branch
git log --oneline -1
```

## Шаг 11: Настройте безопасную директорию для Git (важно!)

```bash
# Добавьте директорию в безопасные для git
git config --global --add safe.directory /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# Или локально
git config --local --add safe.directory /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html
```

## Шаг 12: Настройте UTF-8 для Git (для правильного отображения русских символов)

```bash
# Устанавливаем локаль UTF-8 для текущей сессии
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Настройка Git для UTF-8
git config --global i18n.commitencoding utf-8
git config --global i18n.logoutputencoding utf-8
git config --global core.quotepath false
git config --global core.autocrlf false

# Для постоянной работы добавляем в ~/.bashrc
echo "" >> ~/.bashrc
echo "# Git UTF-8 настройки" >> ~/.bashrc
echo "export LANG=en_US.UTF-8" >> ~/.bashrc
echo "export LC_ALL=en_US.UTF-8" >> ~/.bashrc

# Применяем изменения
source ~/.bashrc

# Проверка настроек
git config --global --get i18n.commitencoding
git config --global --get i18n.logoutputencoding
git config --global --get core.quotepath
```

Или используйте готовый скрипт:

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html
bash SERVER_GIT_UTF8_SETUP.sh
source ~/.bashrc
```

## Готово!

После выполнения этих команд деплой будет работать. Проверьте локально:

```bash
php artisan deploy --insecure --message "Тестовый деплой"
```

## Важные замечания

1. **Не коммитьте .env файл** - он должен быть в .gitignore
2. **Проверьте права доступа** на файлы и директории
3. **Убедитесь, что DEPLOY_TOKEN** в .env на сервере совпадает с локальным
4. **Проверьте права на выполнение** git команд для веб-сервера

## Если возникли проблемы

### Проблема: "Permission denied"
```bash
# Проверьте владельца файлов
ls -la

# Если нужно, измените владельца
chown -R ваш-пользователь:ваша-группа /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html
```

### Проблема: "Git pull не работает"
```bash
# Проверьте права на .git директорию
chmod -R 755 .git

# Проверьте настройки safe.directory
git config --list | grep safe.directory
```

### Проблема: Русские символы в коммитах отображаются как кракозябры
```bash
# Установите UTF-8 для git (см. Шаг 12 выше)
# Или используйте скрипт
bash SERVER_GIT_UTF8_SETUP.sh
source ~/.bashrc

# При выполнении команд вручную добавляйте настройки:
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8
git -c i18n.logoutputencoding=utf-8 -c core.quotepath=false reset --hard $COMMIT
```

**См. также:** [SERVER_GIT_FIX_COMMANDS.md](./SERVER_GIT_FIX_COMMANDS.md) - подробное руководство по исправлению проблем с кодировкой

### Проблема: "Composer не найден"
```bash
# Установите composer или используйте локальный
php bin/composer install --no-dev --optimize-autoloader
```

