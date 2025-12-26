# Настройка проекта Laravel + React + Vue

Проект настроен для работы с двумя фронтенд-приложениями:
- **React** (в `/frontend`) - основное приложение для пользователей (корневой путь `/`)
- **Vue** (в `/resources/js`) - админ-панель (путь `/admin/*`)

## Структура

- React приложение находится в папке `frontend/`
- Vue админка находится в `resources/js/` с точкой входа `admin.js`
- Точка входа сервера: `public/`

## Запуск проекта

### 1. Установка зависимостей

```bash
# Установка зависимостей Laravel
composer install

# Установка зависимостей для Vue (Laravel)
npm install

# Установка зависимостей для React
cd frontend
npm install
cd ..
```

### 2. Настройка окружения

Убедитесь, что файл `.env` настроен правильно (база данных и т.д.)

### 3. Сборка проектов

**ВАЖНО:** Проект настроен для работы с собранными файлами (без dev серверов).

**Соберите React приложение:**
```bash
cd frontend
npm run build
cd ..
```

**Соберите Vue админку:**
```bash
npm run build
```

После сборки все будет работать через Laravel без необходимости запускать dev серверы.

### Альтернатива: режим разработки (не обязательно)

Если нужен режим разработки с hot reload:

**Терминал 1 - Laravel сервер:**
```bash
php artisan serve
```

**Терминал 2 - Vite для Vue админки:**
```bash
npm run dev
```

**Терминал 3 - Vite для React приложения (только если хотите dev режим):**
```bash
cd frontend
npm run dev
```

**Примечание:** По умолчанию проект настроен на использование собранных файлов. Для dev режима нужно изменить `resources/views/react.blade.php`

Или используйте `concurrently` (уже установлен):
```bash
# В корне проекта можно создать скрипт для запуска всех серверов
```

### 4. Сборка для production

**Сборка Vue админки:**
```bash
npm run build
```

**Сборка React приложения:**
```bash
cd frontend
npm run build
cd ..
```

После сборки React файлы будут в `public/frontend/`.

## Маршрутизация

- **Корневой путь (`/`)** → React приложение
- **`/admin/*`** → Vue админ-панель
- **`/api/*`** → Laravel API маршруты
- **`/storage/*`, `/build/*`, `/frontend/*`** → статические файлы

## Важные файлы

- `routes/web.php` - маршруты Laravel
- `resources/views/react.blade.php` - шаблон для React
- `resources/views/admin.blade.php` - шаблон для Vue админки
- `resources/js/admin.js` - точка входа Vue админки
- `frontend/vite.config.ts` - конфигурация Vite для React
- `vite.config.js` - конфигурация Vite для Laravel/Vue

## Примечания

- В dev режиме React запускается на порту 8080 и подключается через blade шаблон
- В production React собирается в `public/frontend/` и подключается статически
- Vue админка использует базовый путь `/admin` для роутера

