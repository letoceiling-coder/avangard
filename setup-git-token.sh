#!/bin/bash

echo "🔑 Настройка Personal Access Token для GitHub"
echo ""

echo "📝 Инструкция по созданию токена:"
echo "   1. Откройте: https://github.com/settings/tokens"
echo "   2. Нажмите 'Generate new token (classic)'"
echo "   3. Название: 'Avangard Deploy'"
echo "   4. Права: выберите 'repo' (полный доступ к репозиториям)"
echo "   5. Нажмите 'Generate token'"
echo "   6. СКОПИРУЙТЕ ТОКЕН (он показывается только один раз!)"
echo ""

read -p "Введите ваш GitHub username: " username
if [ -z "$username" ]; then
    echo "❌ Username не может быть пустым"
    exit 1
fi

read -sp "Введите Personal Access Token: " token
echo ""
if [ -z "$token" ]; then
    echo "❌ Token не может быть пустым"
    exit 1
fi

# Обновляем remote URL с токеном
echo ""
echo "🔧 Обновление remote URL..."
git remote set-url origin "https://${username}:${token}@github.com/letoceiling-coder/avangard.git"

if [ $? -eq 0 ]; then
    echo "✅ Remote URL обновлен"
    echo ""
    echo "📋 Текущая конфигурация (без токена):"
    git remote -v | sed "s/${token}/***HIDDEN***/g"
    echo ""
    echo "✅ Настройка завершена!"
    echo ""
    echo "📝 Теперь вы можете выполнить:"
    echo "   git push"
    echo "   php artisan deploy --insecure"
else
    echo "❌ Ошибка обновления remote URL"
    exit 1
fi

