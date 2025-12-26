#!/bin/bash

# Скрипт для тестирования API системы поддержки
# Использование: ./tests/support_api_test.sh

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Настройки
BASE_URL="${BASE_URL:-http://localhost}"
API_BASE="${API_BASE:-/api/v1}"
WEBHOOK_BASE="${WEBHOOK_BASE:-/api}"

# Токены (нужно установить перед запуском)
SANCTUM_TOKEN="${SANCTUM_TOKEN:-}"
DEPLOY_TOKEN="${DEPLOY_TOKEN:-test-deploy-token-12345678901234567890}"

echo -e "${BLUE}=== Тестирование API системы поддержки ===${NC}\n"

# Функция для вывода результата
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
    fi
}

# Функция для выполнения запроса
make_request() {
    local method=$1
    local url=$2
    local data=$3
    local token=$4
    local content_type=$5
    
    if [ -z "$content_type" ]; then
        content_type="application/json"
    fi
    
    local headers=()
    headers+=("-H" "Content-Type: $content_type")
    headers+=("-H" "Accept: application/json")
    
    if [ ! -z "$token" ]; then
        headers+=("-H" "Authorization: Bearer $token")
    fi
    
    if [ "$method" = "GET" ]; then
        curl -s -w "\n%{http_code}" -X GET "${BASE_URL}${url}" "${headers[@]}"
    elif [ "$method" = "POST" ]; then
        if [ "$content_type" = "multipart/form-data" ]; then
            curl -s -w "\n%{http_code}" -X POST "${BASE_URL}${url}" "${headers[@]}" -F "$data"
        else
            curl -s -w "\n%{http_code}" -X POST "${BASE_URL}${url}" "${headers[@]}" -d "$data"
        fi
    fi
}

# Тест 1: Создание тикета без файлов
echo -e "${YELLOW}Тест 1: Создание тикета без файлов${NC}"
response=$(make_request "POST" "${API_BASE}/support/ticket" \
    "theme=Тестовая тема&message=Тестовое сообщение" \
    "$SANCTUM_TOKEN" \
    "multipart/form-data")
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')
if [ "$http_code" = "201" ]; then
    print_result 0 "Тикет создан успешно"
    TICKET_ID=$(echo "$body" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)
    echo "  Ticket ID: $TICKET_ID"
else
    print_result 1 "Ошибка создания тикета (HTTP $http_code)"
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
fi
echo ""

# Тест 2: Создание тикета с файлом
echo -e "${YELLOW}Тест 2: Создание тикета с файлом${NC}"
if [ -f "tests/test_image.png" ] || [ -f "/tmp/test_image.png" ]; then
    test_file="tests/test_image.png"
    [ ! -f "$test_file" ] && test_file="/tmp/test_image.png"
    
    response=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}${API_BASE}/support/ticket" \
        -H "Authorization: Bearer $SANCTUM_TOKEN" \
        -H "Accept: application/json" \
        -F "theme=Тикет с файлом" \
        -F "message=Сообщение с вложением" \
        -F "attachments[]=@$test_file")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    if [ "$http_code" = "201" ]; then
        print_result 0 "Тикет с файлом создан успешно"
        if [ -z "$TICKET_ID" ]; then
            TICKET_ID=$(echo "$body" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)
        fi
    else
        print_result 1 "Ошибка создания тикета с файлом (HTTP $http_code)"
    fi
else
    print_result 1 "Тестовый файл не найден (пропуск)"
fi
echo ""

# Тест 3: Валидация - отсутствует тема
echo -e "${YELLOW}Тест 3: Валидация - отсутствует тема${NC}"
response=$(make_request "POST" "${API_BASE}/support/ticket" \
    '{"message":"Сообщение без темы"}' \
    "$SANCTUM_TOKEN")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "422" ]; then
    print_result 0 "Валидация работает (тема обязательна)"
else
    print_result 1 "Валидация не сработала (HTTP $http_code)"
fi
echo ""

# Тест 4: Получение списка тикетов
echo -e "${YELLOW}Тест 4: Получение списка тикетов${NC}"
response=$(make_request "GET" "${API_BASE}/support/tickets" "" "$SANCTUM_TOKEN")
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')
if [ "$http_code" = "200" ]; then
    print_result 0 "Список тикетов получен"
    ticket_count=$(echo "$body" | grep -o '"total":[0-9]*' | cut -d':' -f2)
    echo "  Всего тикетов: $ticket_count"
else
    print_result 1 "Ошибка получения списка (HTTP $http_code)"
fi
echo ""

# Тест 5: Фильтрация по статусу
echo -e "${YELLOW}Тест 5: Фильтрация тикетов по статусу${NC}"
response=$(make_request "GET" "${API_BASE}/support/tickets?status=open" "" "$SANCTUM_TOKEN")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "200" ]; then
    print_result 0 "Фильтрация работает"
else
    print_result 1 "Ошибка фильтрации (HTTP $http_code)"
fi
echo ""

# Тест 6: Получение тикета по ID
if [ ! -z "$TICKET_ID" ]; then
    echo -e "${YELLOW}Тест 6: Получение тикета по ID${NC}"
    response=$(make_request "GET" "${API_BASE}/support/tickets/${TICKET_ID}" "" "$SANCTUM_TOKEN")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    if [ "$http_code" = "200" ]; then
        print_result 0 "Тикет получен успешно"
        messages_count=$(echo "$body" | grep -o '"messages":\[' | wc -l)
        echo "  Сообщений в тикете: $messages_count"
    else
        print_result 1 "Ошибка получения тикета (HTTP $http_code)"
    fi
    echo ""
fi

# Тест 7: Отправка сообщения в открытый тикет
if [ ! -z "$TICKET_ID" ]; then
    echo -e "${YELLOW}Тест 7: Отправка сообщения в тикет${NC}"
    response=$(make_request "POST" "${API_BASE}/support/message" \
        "ticket_id=${TICKET_ID}&message=Новое сообщение от теста" \
        "$SANCTUM_TOKEN" \
        "multipart/form-data")
    http_code=$(echo "$response" | tail -n1)
    if [ "$http_code" = "201" ]; then
        print_result 0 "Сообщение отправлено успешно"
    else
        print_result 1 "Ошибка отправки сообщения (HTTP $http_code)"
        echo "$response" | sed '$d' | jq '.' 2>/dev/null || echo "$response" | sed '$d'
    fi
    echo ""
fi

# Тест 8: Webhook - получение сообщения от CRM
if [ ! -z "$TICKET_ID" ]; then
    echo -e "${YELLOW}Тест 8: Webhook - сообщение от CRM${NC}"
    response=$(make_request "POST" "${WEBHOOK_BASE}/support/webhook/message" \
        "{\"ticket_id\":\"${TICKET_ID}\",\"message\":\"Ответ от CRM через webhook\",\"attachments\":[]}" \
        "$DEPLOY_TOKEN")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    if [ "$http_code" = "201" ]; then
        print_result 0 "Webhook сообщение обработано"
        sender=$(echo "$body" | grep -o '"sender":"[^"]*"' | cut -d'"' -f4)
        echo "  Отправитель: $sender"
    else
        print_result 1 "Ошибка webhook сообщения (HTTP $http_code)"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    fi
    echo ""
fi

# Тест 9: Webhook - изменение статуса
if [ ! -z "$TICKET_ID" ]; then
    echo -e "${YELLOW}Тест 9: Webhook - изменение статуса${NC}"
    response=$(make_request "POST" "${WEBHOOK_BASE}/support/webhook/status" \
        "{\"ticket_id\":\"${TICKET_ID}\",\"status\":\"in_progress\"}" \
        "$DEPLOY_TOKEN")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    if [ "$http_code" = "200" ]; then
        print_result 0 "Статус изменен успешно"
        status=$(echo "$body" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
        echo "  Новый статус: $status"
    else
        print_result 1 "Ошибка изменения статуса (HTTP $http_code)"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    fi
    echo ""
fi

# Тест 10: Попытка отправить сообщение в закрытый тикет
if [ ! -z "$TICKET_ID" ]; then
    echo -e "${YELLOW}Тест 10: Попытка отправить сообщение в закрытый тикет${NC}"
    # Сначала закрываем тикет
    make_request "POST" "${WEBHOOK_BASE}/support/webhook/status" \
        "{\"ticket_id\":\"${TICKET_ID}\",\"status\":\"closed\"}" \
        "$DEPLOY_TOKEN" > /dev/null 2>&1
    
    # Пытаемся отправить сообщение
    response=$(make_request "POST" "${API_BASE}/support/message" \
        "ticket_id=${TICKET_ID}&message=Попытка отправить в закрытый" \
        "$SANCTUM_TOKEN" \
        "multipart/form-data")
    http_code=$(echo "$response" | tail -n1)
    if [ "$http_code" = "403" ]; then
        print_result 0 "Защита от отправки в закрытый тикет работает"
    else
        print_result 1 "Защита не сработала (HTTP $http_code)"
    fi
    echo ""
fi

# Тест 11: Webhook - неверный токен
echo -e "${YELLOW}Тест 11: Webhook - неверный токен${NC}"
response=$(make_request "POST" "${WEBHOOK_BASE}/support/webhook/message" \
    '{"ticket_id":"550e8400-e29b-41d4-a716-446655440000","message":"Тест"}' \
    "wrong-token")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "403" ]; then
    print_result 0 "Защита токеном работает"
else
    print_result 1 "Защита не сработала (HTTP $http_code)"
fi
echo ""

# Тест 12: Валидация UUID
echo -e "${YELLOW}Тест 12: Валидация UUID${NC}"
response=$(make_request "GET" "${API_BASE}/support/tickets/invalid-uuid" "" "$SANCTUM_TOKEN")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "404" ] || [ "$http_code" = "422" ]; then
    print_result 0 "Валидация UUID работает"
else
    print_result 1 "Валидация не сработала (HTTP $http_code)"
fi
echo ""

# Тест 13: Неавторизованный доступ
echo -e "${YELLOW}Тест 13: Неавторизованный доступ${NC}"
response=$(make_request "GET" "${API_BASE}/support/tickets" "" "")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "401" ]; then
    print_result 0 "Защита авторизацией работает"
else
    print_result 1 "Защита не сработала (HTTP $http_code)"
fi
echo ""

echo -e "${BLUE}=== Тестирование завершено ===${NC}"

