#!/bin/bash
# Скрипт для настройки Git с правильной кодировкой UTF-8 на сервере
# Использование: bash SERVER_GIT_UTF8_SETUP.sh

echo "🔧 Настройка Git для правильной работы с UTF-8 на сервере..."

# Устанавливаем локаль UTF-8 (если не установлена)
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Настройка Git для UTF-8
echo "📝 Настройка git config..."

# Устанавливаем кодировку для коммитов
git config --global i18n.commitencoding utf-8

# Устанавливаем кодировку для вывода логов
git config --global i18n.logoutputencoding utf-8

# Отключаем экранирование путей (для правильного отображения русских символов)
git config --global core.quotepath false

# Отключаем автоматическое преобразование окончаний строк (для Linux серверов)
git config --global core.autocrlf false

# Настройка кодировки для веб-интерфейса
git config --global gui.encoding utf-8

# Проверка настроек
echo ""
echo "✅ Настройки Git:"
echo "   i18n.commitencoding: $(git config --global --get i18n.commitencoding)"
echo "   i18n.logoutputencoding: $(git config --global --get i18n.logoutputencoding)"
echo "   core.quotepath: $(git config --global --get core.quotepath)"
echo "   core.autocrlf: $(git config --global --get core.autocrlf)"
echo ""

# Добавляем настройки в ~/.bashrc для постоянной работы
if [ -f ~/.bashrc ]; then
    if ! grep -q "export LANG=en_US.UTF-8" ~/.bashrc; then
        echo "" >> ~/.bashrc
        echo "# Git UTF-8 настройки" >> ~/.bashrc
        echo "export LANG=en_US.UTF-8" >> ~/.bashrc
        echo "export LC_ALL=en_US.UTF-8" >> ~/.bashrc
        echo "✅ Настройки добавлены в ~/.bashrc"
    else
        echo "ℹ️ Настройки локали уже есть в ~/.bashrc"
    fi
fi

echo ""
echo "✅ Настройка завершена!"
echo ""
echo "💡 Для применения изменений выполните:"
echo "   source ~/.bashrc"
echo "   или перезайдите в SSH сессию"


