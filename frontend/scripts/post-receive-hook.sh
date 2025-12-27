#!/bin/bash

# Git post-receive hook для автоматического деплоя
# Разместите этот файл на сервере в: ~/trendagent.siteaccess.ru/public_html/avangard-react.git/hooks/post-receive
# Сделайте исполняемым: chmod +x post-receive

set -e

# Конфигурация
PROJECT_DIR="/home/user/trendagent.siteaccess.ru/public_html/avangard-react"
DEPLOY_DIR="/home/user/trendagent.siteaccess.ru/public_html"
BRANCH="main"
LOG_FILE="/home/user/deploy.log"

# Логирование
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== Начало деплоя ==="

# Читаем стандартный ввод от git
while read oldrev newrev refname; do
    branch=$(git rev-parse --symbolic --abbrev-ref $refname)
    
    if [ "$branch" != "$BRANCH" ]; then
        log "Пропуск ветки: $branch (ожидается: $BRANCH)"
        continue
    fi
    
    log "Обновление ветки: $branch"
    
    # Переходим в рабочую директорию
    cd "$PROJECT_DIR" || {
        log "ОШИБКА: Не удалось перейти в $PROJECT_DIR"
        exit 1
    }
    
    # Обновляем код
    log "Получение изменений из git..."
    git --git-dir="$PROJECT_DIR/.git" --work-tree="$PROJECT_DIR" checkout -f "$BRANCH"
    git --git-dir="$PROJECT_DIR/.git" --work-tree="$PROJECT_DIR" pull origin "$BRANCH"
    
    # Устанавливаем зависимости
    log "Установка зависимостей..."
    npm ci
    
    # Собираем проект
    log "Сборка проекта..."
    npm run build
    
    # Копируем файлы в production
    log "Копирование файлов в production..."
    cp -r dist/. "$DEPLOY_DIR/"
    
    log "✅ Деплой завершен успешно!"
    
done

log "=== Конец деплоя ==="

