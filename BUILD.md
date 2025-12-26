# Инструкция по сборке проекта

## Быстрая сборка всего проекта

В корне проекта выполните:

```bash
npm run build:all
```

Эта команда соберет и Vue админку, и React приложение.

## Отдельная сборка

### Сборка React приложения

```bash
npm run build:react
```

или

```bash
cd frontend
npm run build
```

После сборки файлы будут находиться в `public/frontend/` и будут автоматически подключаться через Laravel.

### Сборка Vue админки

```bash
npm run build:admin
```

или

```bash
npm run build
```

## Полная сборка с оптимизацией Laravel

```bash
# 1. Собрать все фронтенд приложения
npm run build:all

# 2. Оптимизировать Laravel (опционально)
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## После сборки

- React приложение будет доступно на корневом пути `/`
- Vue админка будет доступна на `/admin/*`
- Не требуется запускать dev серверы - все работает из собранных файлов

