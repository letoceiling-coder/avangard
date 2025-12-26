# Логирование системы поддержки

## Обзор

Система поддержки использует отдельный канал логирования `tickets` для записи всех операций с тикетами в отдельный файл `storage/logs/tickets.log`.

## Конфигурация

### Канал логирования

В `config/logging.php` настроен канал `tickets`:

```php
'tickets' => [
    'driver' => 'daily',
    'path' => storage_path('logs/tickets.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_TICKETS_DAYS', 90), // Хранить логи 90 дней
    'replace_placeholders' => true,
],
```

### Переменные окружения

В `.env` можно настроить:

```env
# Уровень логирования (debug, info, warning, error)
LOG_LEVEL=debug

# Количество дней хранения логов тикетов
LOG_TICKETS_DAYS=90
```

## Что логируется

### 1. Создание тикета

**Событие:** `Ticket created`

**Уровень:** `info`

**Данные:**
- `ticket_id` - UUID тикета
- `theme` - тема тикета
- `status` - статус (обычно "open")
- `user_id` - ID пользователя
- `user_email` - email пользователя
- `ip` - IP адрес
- `user_agent` - User-Agent браузера
- `attachments_count` - количество вложений
- `message_length` - длина сообщения

**Пример:**
```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "theme": "Проблема с доступом",
  "status": "open",
  "user_id": 1,
  "user_email": "admin@example.com",
  "ip": "192.168.1.1",
  "attachments_count": 2,
  "message_length": 150
}
```

### 2. Отправка тикета в CRM

**Событие:** `Ticket sent to CRM`

**Уровень:** `info` (успех) или `error` (ошибка)

**Данные:**
- `ticket_id` - UUID тикета
- `theme` - тема тикета
- `status` - статус
- `success` - успешность отправки
- `crm_url` - URL CRM
- `response_status` - HTTP статус ответа
- `response` - ответ от CRM (при успехе)
- `attachments_count` - количество вложений

**Пример успеха:**
```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "theme": "Проблема с доступом",
  "success": true,
  "crm_url": "https://crm.siteaccess.ru/api/v1/tecket",
  "response_status": 200
}
```

**Пример ошибки:**
```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "success": false,
  "status": 500,
  "response": "Internal Server Error"
}
```

### 3. Создание сообщения

**Событие:** `Message created`

**Уровень:** `info`

**Данные:**
- `message_id` - UUID сообщения
- `ticket_id` - UUID тикета
- `sender` - отправитель ("local" или "crm")
- `has_attachments` - наличие вложений
- `attachments_count` - количество вложений
- `user_id` - ID пользователя (для local)
- `ip` - IP адрес
- `message_length` - длина сообщения

**Пример:**
```json
{
  "message_id": "660e8400-e29b-41d4-a716-446655440001",
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "sender": "local",
  "has_attachments": true,
  "attachments_count": 1,
  "user_id": 1,
  "ip": "192.168.1.1"
}
```

### 4. Изменение статуса тикета

**Событие:** `Ticket status changed`

**Уровень:** `info`

**Данные:**
- `ticket_id` - UUID тикета
- `theme` - тема тикета
- `old_status` - предыдущий статус
- `new_status` - новый статус
- `changed_by` - кто изменил ("admin" или "crm")
- `user_id` - ID пользователя (если admin)
- `ip` - IP адрес

**Пример:**
```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "theme": "Проблема с доступом",
  "old_status": "open",
  "new_status": "in_progress",
  "changed_by": "crm",
  "ip": "192.168.1.1"
}
```

### 5. Получение сообщения от CRM

**Событие:** `Message received from CRM`

**Уровень:** `info`

**Данные:**
- `message_id` - UUID сообщения
- `ticket_id` - UUID тикета
- `has_attachments` - наличие вложений
- `ip` - IP адрес
- `user_agent` - User-Agent
- `message_length` - длина сообщения

**Пример:**
```json
{
  "message_id": "660e8400-e29b-41d4-a716-446655440002",
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "has_attachments": false,
  "ip": "192.168.1.1"
}
```

### 6. Webhook запросы

**Событие:** `Webhook: {type}`

**Уровень:** `info` (успех) или `error` (ошибка)

**Типы:**
- `message` - получение сообщения
- `status` - изменение статуса

**Данные:**
- `type` - тип webhook
- `data` - данные запроса
- `success` - успешность обработки
- `ip` - IP адрес
- `user_agent` - User-Agent

**Пример:**
```json
{
  "type": "status",
  "data": {
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "closed"
  },
  "success": true,
  "ip": "192.168.1.1"
}
```

### 7. Ошибки

**Событие:** `Error: {action}`

**Уровень:** `error`

**Действия:**
- `Creating ticket` - ошибка создания тикета
- `Sending message` - ошибка отправки сообщения
- `Processing webhook message` - ошибка обработки webhook
- `Updating ticket status` - ошибка обновления статуса

**Данные:**
- `error` - сообщение об ошибке
- `file` - файл где произошла ошибка
- `line` - строка ошибки
- `trace` - полный стек вызовов
- `ip` - IP адрес
- Контекстные данные (ticket_id, theme и т.д.)

**Пример:**
```json
{
  "error": "SQLSTATE[23000]: Integrity constraint violation",
  "file": "/app/Http/Controllers/Api/SupportController.php",
  "line": 75,
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### 8. Просмотр тикетов

**Событие:** `Tickets list requested`

**Уровень:** `debug`

**Данные:**
- `filters` - фильтры запроса
- `count` - количество найденных тикетов
- `user_id` - ID пользователя
- `ip` - IP адрес

**Событие:** `Ticket viewed`

**Уровень:** `debug`

**Данные:**
- `ticket_id` - UUID тикета
- `theme` - тема тикета
- `status` - статус
- `messages_count` - количество сообщений
- `user_id` - ID пользователя
- `ip` - IP адрес

### 9. Попытка отправки в закрытый тикет

**Событие:** `Attempt to send message to closed ticket`

**Уровень:** `warning`

**Данные:**
- `ticket_id` - UUID тикета
- `status` - текущий статус
- `user_id` - ID пользователя
- `ip` - IP адрес

### 10. Неавторизованный доступ

**Событие:** `Unauthorized access: {action}`

**Уровень:** `warning`

**Данные:**
- `ip` - IP адрес
- `user_agent` - User-Agent
- `url` - запрашиваемый URL
- `path` - путь запроса
- `user_id` - ID пользователя (если есть)

### 11. Проверка токена webhook

**Событие:** `Deploy token check for support webhook`

**Уровень:** `debug`

**Данные:**
- `ip` - IP адрес
- `path` - путь запроса
- `tokens_match` - совпадение токенов

**Событие:** `Invalid deploy token for support webhook`

**Уровень:** `warning`

**Данные:**
- `ip` - IP адрес
- `path` - путь запроса
- `user_agent` - User-Agent

## Использование

### В коде

Используйте класс `SupportLogger` для логирования:

```php
use App\Services\SupportLogger;

// Создание тикета
SupportLogger::logTicketCreated($ticket, [
    'attachments_count' => 2,
]);

// Отправка в CRM
SupportLogger::logTicketSentToCrm($ticket, true, [
    'response_status' => 200,
]);

// Создание сообщения
SupportLogger::logMessageCreated($message);

// Изменение статуса
SupportLogger::logTicketStatusChanged($ticket, 'open', 'closed');

// Ошибка
SupportLogger::logError('Creating ticket', $exception, [
    'theme' => $request->input('theme'),
]);
```

### Прямое логирование

Можно использовать напрямую канал `tickets`:

```php
use Illuminate\Support\Facades\Log;

Log::channel('tickets')->info('Custom log message', [
    'ticket_id' => $ticket->id,
    'custom_data' => 'value',
]);
```

## Просмотр логов

### Через файл

Логи хранятся в `storage/logs/tickets-YYYY-MM-DD.log`:

```bash
# Последний лог
tail -f storage/logs/tickets-$(date +%Y-%m-%d).log

# Поиск по тикету
grep "550e8400-e29b-41d4-a716-446655440000" storage/logs/tickets-*.log

# Поиск ошибок
grep "ERROR" storage/logs/tickets-*.log

# Поиск webhook запросов
grep "Webhook" storage/logs/tickets-*.log
```

### Через Laravel

```php
use Illuminate\Support\Facades\Storage;

$logs = Storage::get('logs/tickets-' . date('Y-m-d') . '.log');
```

## Ротация логов

Логи автоматически ротируются ежедневно. Старые логи удаляются через 90 дней (настраивается через `LOG_TICKETS_DAYS`).

## Мониторинг

### Рекомендуемые метрики

1. **Количество созданных тикетов** - `grep "Ticket created" | wc -l`
2. **Количество ошибок** - `grep "ERROR" | wc -l`
3. **Неудачные отправки в CRM** - `grep "Failed to send ticket to CRM" | wc -l`
4. **Неавторизованные попытки** - `grep "Unauthorized access" | wc -l`
5. **Попытки отправки в закрытые тикеты** - `grep "Attempt to send message to closed ticket" | wc -l`

### Алерты

Настройте мониторинг на:
- Высокое количество ошибок
- Неудачные отправки в CRM
- Подозрительные IP адреса
- Частые неавторизованные попытки

## Безопасность

⚠️ **Важно:** Логи могут содержать чувствительные данные:
- IP адреса
- User-Agent
- Email пользователей
- Содержимое сообщений

Обеспечьте:
- Ограниченный доступ к файлам логов
- Шифрование при хранении
- Регулярную очистку старых логов
- Не логируйте пароли и токены

## Примеры запросов

### Найти все операции с тикетом

```bash
grep "550e8400-e29b-41d4-a716-446655440000" storage/logs/tickets-*.log
```

### Найти все ошибки за сегодня

```bash
grep "ERROR" storage/logs/tickets-$(date +%Y-%m-%d).log
```

### Найти все webhook запросы

```bash
grep "Webhook" storage/logs/tickets-*.log
```

### Найти все неавторизованные попытки

```bash
grep "Unauthorized access" storage/logs/tickets-*.log
```

### Статистика по дням

```bash
# Количество тикетов по дням
for file in storage/logs/tickets-*.log; do
    echo "$file: $(grep -c "Ticket created" "$file")"
done
```

---

**Версия:** 1.0  
**Дата:** 2025-12-13

