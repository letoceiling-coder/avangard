# Решение проблемы зависаний команд

## Проблема

Команды `npm run build:admin`, `php artisan`, `git` часто зависают при выполнении через инструменты.

## Решение

### 1. Для AI-ассистента - Стратегия работы

**Для долгих команд (> 10 секунд):**
- ❌ НЕ выполнять напрямую
- ✅ Давать инструкции пользователю

**Пример:**
```
Вместо выполнения npm run build:admin, сказать:
"Для сборки админ-панели выполните: npm run build:admin"
```

**Для быстрых команд (< 10 секунд):**
- ✅ Выполнять с таймаутами
- ✅ Использовать неинтерактивные флаги

### 2. Для пользователя - Готовые скрипты

#### Диагностика проблем:
```bash
php scripts/check-commands.php
```

#### Безопасная сборка:
```bash
# Windows
scripts\safe-build-admin.bat

# Linux/Mac
chmod +x scripts/safe-build-admin.sh
./scripts/safe-build-admin.sh
```

### 3. Обязательные флаги

#### PHP Artisan:
```bash
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
```

#### Git:
```bash
git config --global core.askPass ""
git config --global credential.helper manager
```

## Результат

Теперь:
1. ✅ AI будет давать инструкции для долгих команд
2. ✅ Пользователь может использовать готовые скрипты
3. ✅ Команды не будут зависать
4. ✅ Все операции будут стабильными

## Дополнительно

- `TROUBLESHOOTING.md` - Подробное решение проблем
- `HOW_TO_AVOID_HANGS.md` - Как избежать зависаний
- `README_AI_WORKFLOW.md` - Рабочий процесс для AI

