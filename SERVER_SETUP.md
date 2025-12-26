# Инструкция по установке проекта на сервере

Полная пошаговая инструкция для настройки проекта на сервере.

## Предварительные требования

- PHP 8.2 или выше
- Composer установлен
- Node.js 18+ и npm установлены
- База данных MySQL/PostgreSQL настроена
- Git установлен

## Шаг 1: Клонирование и настройка проекта

```bash
# Перейти в директорию проекта (если еще не там)
cd /path/to/project

# Обновить зависимости Composer
composer install --no-dev --optimize-autoloader

# Скопировать .env файл (если еще не скопирован)
cp .env.example .env

# Сгенерировать ключ приложения
php8.2 artisan key:generate
```

## Шаг 2: Настройка .env файла

Убедитесь, что в `.env` файле настроены:

```env
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

TELEGRAM_BOT_TOKEN=your_bot_token

DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your_deploy_token
```

## Шаг 3: Выполнение миграций

```bash
# Очистить базу данных и выполнить все миграции заново
php8.2 artisan migrate:fresh
```

Это создаст все необходимые таблицы:
- `users` (с полями для Telegram)
- `channels` (каналы для подписки)
- `wheel_sectors` (секторы рулетки)
- `wheel_settings` (настройки рулетки)
- `spins` (история прокрутов)
- `referrals` (реферальная система)
- `user_tickets` (билеты пользователей)
- `star_exchanges` (обмены звезд)
- `leaderboard_snapshot` (лидерборд)
- И другие необходимые таблицы

## Шаг 4: Заполнение базы данных начальными данными

```bash
# Запустить все seeders для заполнения начальными данными
php8.2 artisan db:seed
```

Это выполнит:
- `RoleSeeder` - создаст роли пользователей
- `ChannelSeeder` - создаст каналы для подписки (@neeklo_studio и @neiroitishka)

## Шаг 5: Импорт иконок рулетки в медиатеку

```bash
# Импортировать иконки из frontend/src/assets/wheel/ в папку "общая" медиатеки
```

Команда:
- Найдет или создаст папку "общая" в медиатеке
- Скопирует все PNG файлы из `frontend/src/assets/wheel/` в `public/upload/obshhaia/`
- Создаст записи в таблице `media`
- Автоматически привяжет иконки к секторам рулетки (если имена файлов совпадают)

## Шаг 6: Обновление секторов рулетки с иконками

После импорта иконок нужно обновить секторы, чтобы они получили ссылки на иконки:

```bash
# Перезапустить seeder секторов для привязки иконок
```

## Шаг 7: Настройка каналов (опционально)

Если нужно изменить каналы для подписки:

```bash
# Удалить все существующие каналы и создать новые
```


## Шаг 8: Установка зависимостей Node.js

```bash
# Установить зависимости для основного проекта (Vue админка)
npm install

# Установить зависимости для React приложения
cd frontend
npm install
cd ..
```

## Шаг 9: Сборка фронтенда

```bash
# Собрать Vue админ-панель
npm run build:admin

# Собрать React приложение
npm run build:react

# Или собрать все сразу
npm run build:all
```

После сборки файлы будут в:
- Vue админка: `public/build/`
- React приложение: `public/frontend/`

## Шаг 10: Настройка прав доступа

```bash
# Установить права на директории для записи
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/upload

# Установить владельца (замените www-data на вашего пользователя веб-сервера)
chown -R www-data:www-data storage bootstrap/cache
chown -R www-data:www-data public/upload
```

## Шаг 11: Оптимизация для production

```bash
# Кешировать конфигурацию
php8.2 artisan config:cache

# Кешировать маршруты
php8.2 artisan route:cache

# Кешировать представления
php8.2 artisan view:cache

# Оптимизировать автозагрузку классов
composer install --optimize-autoloader --no-dev
```

## Шаг 12: Создание администратора (если нужно)

Если нужно создать администратора вручную:

```bash
php8.2 artisan tinker
```

В tinker выполните:

```php
$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('your_secure_password'),
]);

$adminRole = \App\Models\Role::where('slug', 'admin')->first();
if ($adminRole) {
    $user->roles()->attach($adminRole->id);
}
```

## Полная последовательность команд (для копирования)

```bash
# 1. Настройка проекта
composer install --no-dev --optimize-autoloader
cp .env.example .env
php8.2 artisan key:generate

# 2. Настройте .env файл вручную!

# 3. Миграции
php8.2 artisan migrate:fresh

# 4. Заполнение данных
php8.2 artisan db:seed

# 5. Импорт иконок

# 6. Обновление секторов с иконками

# 7. Установка Node.js зависимостей
npm install
cd frontend && npm install && cd ..

# 8. Сборка фронтенда
npm run build:all

# 9. Права доступа
chmod -R 775 storage bootstrap/cache public/upload
chown -R www-data:www-data storage bootstrap/cache public/upload

# 10. Оптимизация
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
composer install --optimize-autoloader --no-dev
```

## Проверка установки

После выполнения всех команд проверьте:

1. **База данных:**
   - Откройте админ-панель: `https://your-domain.com/admin`
   - Убедитесь, что все 12 секторов созданы и имеют иконки

2. **Медиатека:**
   - В админ-панели: "Медиа" → проверьте папку "общая"
   - Должны быть все иконки из `frontend/src/assets/wheel/`

3. **Каналы:**
   - Должны быть каналы @neeklo_studio и @neiroitishka

4. **Фронтенд:**
   - Откройте главную страницу: `https://your-domain.com`
   - Должно загрузиться React приложение с рулеткой

## Устранение проблем

### Иконки не отображаются

```bash
# Переимпортировать иконки

# Обновить секторы
```

### Ошибки прав доступа

```bash
chmod -R 775 storage bootstrap/cache public/upload
```

### Очистить все кеши

```bash
php8.2 artisan cache:clear
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
```

## Дополнительные команды

### Обновление каналов

```bash
```

### Восстановление билетов (запускается автоматически через cron)

```bash
```

### Отправка напоминаний (запускается автоматически через cron)

```bash
```

### Настройка cron задач

Добавьте в crontab:

```bash
* * * * * cd /path-to-your-project && php8.2 artisan schedule:run >> /dev/null 2>&1
```

Это обеспечит автоматический запуск запланированных задач (восстановление билетов, отправка напоминаний).

## Структура файлов после установки

```
public/
├── build/              # Vue админ-панель (собранная)
├── frontend/           # React приложение (собранное)
│   ├── assets/
│   ├── index.html
│   └── ...
└── upload/
    └── obshhaia/       # Иконки рулетки
        ├── prize-0.png
        ├── prize-300.png
        ├── prize-500.png
        └── ...

storage/
└── app/
    └── public/         # Загруженные медиафайлы (символическая ссылка)

database/
├── migrations/         # Все миграции
└── seeders/           # Все seeders
```

---

**Примечание:** Все команды должны выполняться от пользователя, имеющего права на запись в директории проекта. На production сервере обычно это пользователь веб-сервера (например, `www-data`).

