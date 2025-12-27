#!/bin/bash

# Скрипт автоматического деплоя
# Использование: ./scripts/deploy.sh [environment]
# environment: production (по умолчанию) или staging

set -e  # Остановка при ошибке

ENVIRONMENT=${1:-production}
SERVER_USER="${SERVER_USER:-user}"
SERVER_HOST="${SERVER_HOST:-trendagent.siteaccess.ru}"
SERVER_PATH="~/trendagent.siteaccess.ru/public_html"
PROJECT_DIR="avangard-react"
BRANCH="main"

echo "🚀 Начинаем деплой в $ENVIRONMENT..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Функция для вывода сообщений
info() {
    echo -e "${GREEN}✓${NC} $1"
}

warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

error() {
    echo -e "${RED}✗${NC} $1"
    exit 1
}

# Проверка наличия изменений
if [ -n "$(git status --porcelain)" ]; then
    warn "Есть незакоммиченные изменения. Продолжить? (y/n)"
    read -r response
    if [ "$response" != "y" ]; then
        error "Деплой отменен"
    fi
fi

# Коммит и пуш (опционально)
warn "Запушить изменения в git? (y/n)"
read -r response
if [ "$response" = "y" ]; then
    git add .
    git commit -m "Deploy: $(date '+%Y-%m-%d %H:%M:%S')" || true
    git push origin "$BRANCH"
    info "Изменения отправлены в git"
fi

# Подключение к серверу и деплой
info "Подключаемся к серверу..."
ssh "${SERVER_USER}@${SERVER_HOST}" << EOF
    set -e
    cd ${SERVER_PATH}/${PROJECT_DIR} || error "Директория не найдена"
    
    echo "📥 Получаем последние изменения из git..."
    git fetch origin
    git checkout ${BRANCH}
    git pull origin ${BRANCH}
    
    echo "📦 Устанавливаем зависимости..."
    npm ci --production=false
    
    echo "🔨 Собираем проект..."
    npm run build
    
    echo "📤 Копируем файлы в production..."
    cp -r dist/. ..
    
    echo "✅ Деплой завершен успешно!"
    echo "🌐 Приложение доступно по адресу: https://trendagent.siteaccess.ru/"
EOF

if [ $? -eq 0 ]; then
    info "Деплой завершен успешно!"
else
    error "Ошибка при деплое"
fi

