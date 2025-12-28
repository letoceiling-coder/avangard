#!/bin/bash
# Быстрое исправление проблемы с кодировкой в git на сервере
# Использование: скопируйте и выполните команды ниже на сервере

echo "🔧 Быстрое исправление кодировки Git на сервере..."
echo ""

# Переходим в директорию проекта
cd /home/d/dsc23ytp/trendagent.siteaccess.ru/public_html

# Устанавливаем локаль UTF-8
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Настройка Git для UTF-8
echo "📝 Настройка git config..."
git config --global i18n.commitencoding utf-8
git config --global i18n.logoutputencoding utf-8
git config --global core.quotepath false
git config --global core.autocrlf false

# Добавляем настройки в ~/.bashrc для постоянной работы
if ! grep -q "export LANG=en_US.UTF-8" ~/.bashrc 2>/dev/null; then
    echo "" >> ~/.bashrc
    echo "# Git UTF-8 настройки" >> ~/.bashrc
    echo "export LANG=en_US.UTF-8" >> ~/.bashrc
    echo "export LC_ALL=en_US.UTF-8" >> ~/.bashrc
    echo "✅ Настройки добавлены в ~/.bashrc"
fi

echo ""
echo "✅ Настройка завершена!"
echo ""
echo "📋 Проверка настроек:"
git config --global --get i18n.commitencoding
git config --global --get i18n.logoutputencoding
git config --global --get core.quotepath
echo ""
echo "💡 Для применения изменений выполните: source ~/.bashrc"
echo "   или перезайдите в SSH сессию"
echo ""
echo "🔄 Теперь можно обновить код:"
echo "   REMOTE_COMMIT=\$(git ls-remote origin main | awk '{print \$1}')"
echo "   git fetch --all --prune"
echo "   git reset --hard \$REMOTE_COMMIT"


