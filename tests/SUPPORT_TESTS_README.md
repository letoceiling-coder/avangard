# Руководство по тестированию API системы поддержки

## Автоматические тесты (PHPUnit)

### Запуск всех тестов

```bash
php artisan test --filter SupportTicketTest
```

### Запуск конкретного теста

```bash
php artisan test --filter test_create_ticket_without_attachments
```

### Запуск с покрытием

```bash
php artisan test --coverage --filter SupportTicketTest
```

## Ручное тестирование (Bash скрипт)

### Подготовка

1. Установите переменные окружения:

```bash
export BASE_URL="http://localhost"
export SANCTUM_TOKEN="your_sanctum_token_here"
export DEPLOY_TOKEN="test-deploy-token-12345678901234567890"
```

2. Получите токен для тестирования:

```bash
# Войдите в систему и получите токен через API
curl -X POST "http://localhost/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

### Запуск скрипта

```bash
cd tests
./support_api_test.sh
```

Или с параметрами:

```bash
BASE_URL="http://your-domain.com" \
SANCTUM_TOKEN="your_token" \
DEPLOY_TOKEN="your_deploy_token" \
./support_api_test.sh
```

## Тестовые сценарии

### 1. Создание тикета

#### Без файлов
```bash
curl -X POST "http://localhost/api/v1/support/ticket" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "theme=Тестовая тема" \
  -F "message=Тестовое сообщение"
```

#### С файлами
```bash
curl -X POST "http://localhost/api/v1/support/ticket" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "theme=Тикет с файлом" \
  -F "message=Сообщение с вложением" \
  -F "attachments[]=@/path/to/file.png"
```

### 2. Получение списка тикетов

```bash
curl -X GET "http://localhost/api/v1/support/tickets?status=open&page=1" \
  -H "Authorization: Bearer {token}"
```

### 3. Получение тикета

```bash
curl -X GET "http://localhost/api/v1/support/tickets/{ticket_id}" \
  -H "Authorization: Bearer {token}"
```

### 4. Отправка сообщения

```bash
curl -X POST "http://localhost/api/v1/support/message" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "ticket_id={ticket_id}" \
  -F "message=Новое сообщение" \
  -F "attachments[]=@/path/to/file.pdf"
```

### 5. Webhook - сообщение от CRM

```bash
curl -X POST "http://localhost/api/support/webhook/message" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": "{ticket_id}",
    "message": "Ответ от CRM",
    "attachments": []
  }'
```

### 6. Webhook - изменение статуса

```bash
curl -X POST "http://localhost/api/support/webhook/status" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": "{ticket_id}",
    "status": "in_progress"
  }'
```

## Тестирование ошибок

### Валидация - отсутствует тема

```bash
curl -X POST "http://localhost/api/v1/support/ticket" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"message":"Сообщение без темы"}'
```

Ожидаемый ответ: `422 Unprocessable Entity`

### Валидация - отсутствует сообщение

```bash
curl -X POST "http://localhost/api/v1/support/ticket" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"theme":"Тема без сообщения"}'
```

Ожидаемый ответ: `422 Unprocessable Entity`

### Попытка отправить в закрытый тикет

```bash
# Сначала закрываем тикет
curl -X POST "http://localhost/api/support/webhook/status" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"ticket_id":"{ticket_id}","status":"closed"}'

# Пытаемся отправить сообщение
curl -X POST "http://localhost/api/v1/support/message" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"ticket_id":"{ticket_id}","message":"Попытка"}'
```

Ожидаемый ответ: `403 Forbidden`

### Неверный токен webhook

```bash
curl -X POST "http://localhost/api/support/webhook/message" \
  -H "Authorization: Bearer wrong-token" \
  -H "Content-Type: application/json" \
  -d '{"ticket_id":"{ticket_id}","message":"Тест"}'
```

Ожидаемый ответ: `403 Forbidden`

### Неавторизованный доступ

```bash
curl -X GET "http://localhost/api/v1/support/tickets"
```

Ожидаемый ответ: `401 Unauthorized`

## Проверка интеграции с CRM

### Настройка тестового CRM сервера

1. Установите в `.env`:
```
APP_CRM_URL=https://crm.siteaccess.ru/api/v1/tecket
DEPLOY_TOKEN=test-token-12345678901234567890
```

2. Запустите тестовый сервер CRM (можно использовать mock сервер)

3. Создайте тикет - он должен автоматически отправиться в CRM

4. Проверьте логи: `storage/logs/laravel.log`

### Тестирование отправки в CRM

```bash
# Создайте тикет
TICKET_RESPONSE=$(curl -X POST "http://localhost/api/v1/support/ticket" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "theme=Тест CRM" \
  -F "message=Тестовое сообщение")

# Проверьте логи на наличие записи о отправке в CRM
tail -f storage/logs/laravel.log | grep "Ticket sent to CRM"
```

## Проверка файлов

### Создание тестового файла

```bash
# Создайте тестовое изображение
convert -size 800x600 xc:white tests/test_image.png

# Или используйте существующий файл
cp /path/to/image.png tests/test_image.png
```

### Тестирование загрузки файлов

```bash
curl -X POST "http://localhost/api/v1/support/ticket" \
  -H "Authorization: Bearer {token}" \
  -F "theme=Тест файлов" \
  -F "message=Сообщение" \
  -F "attachments[]=@tests/test_image.png" \
  -F "attachments[]=@tests/test_document.pdf"
```

## Отладка

### Включение подробных логов

В `.env`:
```
LOG_LEVEL=debug
APP_DEBUG=true
```

### Просмотр логов в реальном времени

```bash
tail -f storage/logs/laravel.log
```

### Проверка базы данных

```bash
php artisan tinker

# Проверить тикеты
\App\Models\SupportTicket::all();

# Проверить сообщения
\App\Models\SupportMessage::all();
```

## Чек-лист тестирования

- [ ] Создание тикета без файлов
- [ ] Создание тикета с файлами
- [ ] Валидация обязательных полей
- [ ] Валидация размера файлов
- [ ] Валидация типов файлов
- [ ] Получение списка тикетов
- [ ] Фильтрация по статусу
- [ ] Пагинация
- [ ] Получение тикета с сообщениями
- [ ] Отправка сообщения в открытый тикет
- [ ] Отправка сообщения с файлами
- [ ] Блокировка отправки в закрытый тикет
- [ ] Webhook - получение сообщения от CRM
- [ ] Webhook - изменение статуса
- [ ] Webhook - валидация статуса
- [ ] Webhook - защита токеном
- [ ] Неавторизованный доступ
- [ ] Получение несуществующего тикета
- [ ] Сортировка сообщений по времени
- [ ] Интеграция с внешним CRM

