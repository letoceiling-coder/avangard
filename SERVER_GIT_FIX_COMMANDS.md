# Исправление проблем с Git и кодировкой на сервере

## Проблема

При выполнении `git reset --hard` или других git команд на сервере русские символы в сообщениях коммитов отображаются как кракозябры (например, `РЅР°СЃС‚СЂРѕР№РєР° SSH` вместо `настройка SSH`).

## Причина

На сервере не настроена кодировка UTF-8 для git и консоли.

## Решение

### Вариант 1: Автоматическая настройка (рекомендуется)

Выполните на сервере скрипт:

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html
bash SERVER_GIT_UTF8_SETUP.sh
source ~/.bashrc
```

### Вариант 2: Ручная настройка

Выполните команды на сервере:

```bash
# Переходим в директорию проекта
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# Устанавливаем локаль UTF-8 для текущей сессии
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Настройка Git для UTF-8
git config --global i18n.commitencoding utf-8
git config --global i18n.logoutputencoding utf-8
git config --global core.quotepath false
git config --global core.autocrlf false

# Проверяем настройки
git config --global --get i18n.commitencoding
git config --global --get i18n.logoutputencoding
git config --global --get core.quotepath

# Для постоянной работы добавляем в ~/.bashrc
echo "" >> ~/.bashrc
echo "# Git UTF-8 настройки" >> ~/.bashrc
echo "export LANG=en_US.UTF-8" >> ~/.bashrc
echo "export LC_ALL=en_US.UTF-8" >> ~/.bashrc

# Применяем изменения
source ~/.bashrc
```

### Проверка

После настройки проверьте:

```bash
# Проверка настроек git
git config --global --list | grep -E "(i18n|quotepath|autocrlf)"

# Проверка отображения русских символов в логах
git log --oneline -5

# Проверка текущего коммита
git log -1 --pretty=format:"%H %s"
```

Должно отображаться:
- `i18n.commitencoding=utf-8`
- `i18n.logoutputencoding=utf-8`
- `core.quotepath=false`
- Русские символы в сообщениях коммитов отображаются правильно

### Правильная команда для обновления кода

После настройки используйте:

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# Устанавливаем локаль для текущей сессии
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Получаем актуальный коммит
REMOTE_COMMIT=$(git ls-remote origin main | awk '{print $1}')

# Очищаем кеш и получаем все изменения
git fetch --all --prune

# Сбрасываем на актуальный коммит
git reset --hard $REMOTE_COMMIT
```

### Альтернативный способ с явным указанием кодировки

Если проблемы остаются, используйте:

```bash
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

REMOTE_COMMIT=$(git ls-remote origin main | awk '{print $1}')
git fetch --all --prune
git -c i18n.logoutputencoding=utf-8 -c core.quotepath=false reset --hard $REMOTE_COMMIT
```

## После исправления

После настройки git будет правильно отображать русские символы в:
- Сообщениях коммитов (`git log`)
- Статусе (`git status`)
- Выводе всех git команд

## Дополнительная информация

- [GIT_UTF8_SETUP.md](./GIT_UTF8_SETUP.md) - подробная информация о настройке UTF-8 в git
- [SERVER_GIT_SETUP.md](./SERVER_GIT_SETUP.md) - общая настройка git на сервере


