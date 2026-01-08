#!/bin/bash

echo "🔑 Настройка SSH для GitHub"
echo ""

# Проверка наличия SSH ключей
if [ -f ~/.ssh/id_rsa.pub ] || [ -f ~/.ssh/id_ed25519.pub ]; then
    echo "✅ SSH ключи найдены:"
    ls -la ~/.ssh/id_*.pub 2>/dev/null | awk '{print "  - " $9}'
    echo ""
    
    # Показываем публичный ключ
    if [ -f ~/.ssh/id_ed25519.pub ]; then
        echo "📋 Ваш публичный ключ (ed25519):"
        cat ~/.ssh/id_ed25519.pub
        echo ""
    elif [ -f ~/.ssh/id_rsa.pub ]; then
        echo "📋 Ваш публичный ключ (RSA):"
        cat ~/.ssh/id_rsa.pub
        echo ""
    fi
    
    echo "📝 Скопируйте ключ выше и добавьте его в GitHub:"
    echo "   Settings → SSH and GPG keys → New SSH key"
    echo ""
else
    echo "❌ SSH ключи не найдены"
    echo "🔧 Создаю новый SSH ключ..."
    echo ""
    
    # Генерируем новый ключ
    read -p "Введите ваш email для GitHub: " email
    if [ -z "$email" ]; then
        echo "❌ Email не может быть пустым"
        exit 1
    fi
    
    ssh-keygen -t ed25519 -C "$email" -f ~/.ssh/id_ed25519 -N ""
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ SSH ключ создан!"
        echo ""
        echo "📋 Ваш публичный ключ:"
        cat ~/.ssh/id_ed25519.pub
        echo ""
        echo "📝 Скопируйте ключ выше и добавьте его в GitHub:"
        echo "   Settings → SSH and GPG keys → New SSH key"
        echo ""
    else
        echo "❌ Ошибка создания SSH ключа"
        exit 1
    fi
fi

# Добавляем SSH ключ в ssh-agent
echo "🔧 Настройка ssh-agent..."
eval "$(ssh-agent -s)" > /dev/null 2>&1

if [ -f ~/.ssh/id_ed25519 ]; then
    ssh-add ~/.ssh/id_ed25519 2>/dev/null
elif [ -f ~/.ssh/id_rsa ]; then
    ssh-add ~/.ssh/id_rsa 2>/dev/null
fi

# Проверяем подключение к GitHub
echo ""
echo "🔍 Проверка подключения к GitHub..."
ssh -T git@github.com 2>&1 | head -n 1

# Переключаем remote на SSH
echo ""
read -p "Переключить remote на SSH? (y/n): " switch_remote
if [ "$switch_remote" = "y" ] || [ "$switch_remote" = "Y" ]; then
    git remote set-url origin git@github.com:letoceiling-coder/avangard.git
    echo "✅ Remote переключен на SSH"
    echo ""
    echo "📋 Текущая конфигурация:"
    git remote -v
else
    echo "ℹ️  Remote не изменен. Вы можете переключить его позже командой:"
    echo "   git remote set-url origin git@github.com:letoceiling-coder/avangard.git"
fi

echo ""
echo "✅ Настройка завершена!"
echo ""
echo "📝 Следующие шаги:"
echo "   1. Добавьте публичный ключ в GitHub (если еще не добавили)"
echo "   2. Проверьте подключение: ssh -T git@github.com"
echo "   3. Попробуйте выполнить: git push"

