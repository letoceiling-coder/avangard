# РЕФАКТОРИНГ ИНТЕГРАЦИИ ЗАВЕРШЕН

## ЧТО СДЕЛАНО

### 1. МИГРАЦИИ
- ✅ Добавлено поле `external_id` в `support_tickets` (оба проекта)
- ✅ Добавлено поле `external_message_id` в `support_messages` (оба проекта)
- ✅ Переименовано `theme` → `subject` в TMA
- ✅ Изменен enum `sender`: `local|crm` → `tma|crm`

### 2. СЕРВИСЫ ИНТЕГРАЦИИ
- ✅ `app/Services/Integration/IntegrationService.php` (TMA)
  - `sendTicketToCrm()` - отправка тикета в CRM
  - `sendMessageToCrm()` - отправка сообщения в CRM
  
- ✅ `app/Services/Integration/IntegrationService.php` (CRM)
  - `sendMessageToTma()` - отправка сообщения в TMA
  - `sendStatusChangeToTma()` - отправка изменения статуса

### 3. КОНТРОЛЛЕРЫ ИНТЕГРАЦИИ
- ✅ `app/Http/Controllers/Api/IntegrationController.php` (TMA)
  - `receiveMessage()` - получение сообщения от CRM
  - `receiveStatusChange()` - получение изменения статуса

- ✅ `app/Http/Controllers/Api/IntegrationController.php` (CRM)
  - `receiveTicket()` - получение тикета от TMA
  - `receiveMessage()` - получение сообщения от TMA

### 4. ОБНОВЛЕНЫ СУЩЕСТВУЮЩИЕ КОНТРОЛЛЕРЫ
- ✅ `SupportController` (TMA) - использует `IntegrationService`
- ✅ `SupportMessageController` (CRM) - использует `IntegrationService`
- ✅ `SupportTicketController` (CRM) - использует `IntegrationService`

### 5. РОУТЫ
- ✅ TMA: `/api/integration/messages`, `/api/integration/status`
- ✅ CRM: `/api/integration/tickets`, `/api/integration/messages`
- ✅ Все защищены middleware `deploy.token`

### 6. МОДЕЛИ
- ✅ Обновлены `fillable` поля
- ✅ Добавлена поддержка `external_id` и `external_message_id`
- ✅ Исправлено `message` → `body` в TMA

## НОВЫЙ API КОНТРАКТ

### TMA → CRM: Создание тикета
```
POST /api/integration/tickets
Authorization: Bearer {DEPLOY_TOKEN}
{
  "external_ticket_id": "uuid",
  "subject": "string",
  "message": "string",
  "attachments": [],
  "external_url": "https://tma.loc",
  "project": "tma"
}
```

### TMA → CRM: Отправка сообщения
```
POST /api/integration/messages
Authorization: Bearer {DEPLOY_TOKEN}
{
  "external_ticket_id": "uuid",
  "message": "string",
  "attachments": [],
  "sender": "tma",
  "external_message_id": "uuid"
}
```

### CRM → TMA: Отправка сообщения
```
POST /api/integration/messages
Authorization: Bearer {DEPLOY_TOKEN}
{
  "external_ticket_id": "uuid",
  "message": "string",
  "attachments": [],
  "sender": "crm",
  "external_message_id": "int"
}
```

### CRM → TMA: Изменение статуса
```
POST /api/integration/status
Authorization: Bearer {DEPLOY_TOKEN}
{
  "external_ticket_id": "uuid",
  "status": "open|in_progress|closed"
}
```

## ЧТО НУЖНО СДЕЛАТЬ

### 1. ЗАПУСТИТЬ МИГРАЦИИ
```bash
# TMA
php artisan migrate

# CRM
cd C:\OSPanel\domains\skrooty-crm
php artisan migrate
```

### 2. ОБНОВИТЬ .ENV
Убедитесь, что в обоих проектах:
- `DEPLOY_TOKEN` одинаковый
- `APP_CRM_URL` в TMA указывает на CRM
- `APP_URL` в TMA правильный

### 3. ОЧИСТИТЬ КЕШ
```bash
php artisan config:clear
php artisan cache:clear
```

### 4. ПРОВЕРИТЬ
1. Создать тикет в TMA → должен появиться в CRM
2. Отправить сообщение из TMA → должно появиться в CRM
3. Отправить сообщение из CRM → должно появиться в TMA
4. Изменить статус в CRM → должен обновиться в TMA

## ИЗВЕСТНЫЕ ПРОБЛЕМЫ

1. **Миграция переименования `theme` → `subject`** может не работать в MySQL напрямую
   - Решение: создать новую миграцию с `change()` или вручную переименовать

2. **Изменение ENUM** в MySQL требует пересоздания таблицы
   - Решение: миграция делает это через `DB::statement()`

3. **Legacy роуты** оставлены для обратной совместимости
   - Можно удалить после тестирования

## АРХИТЕКТУРА

```
TMA                          CRM
│                            │
├─ SupportController         ├─ SupportTicketController
│  └─ IntegrationService ────│  └─ IntegrationService
│                            │
├─ IntegrationController     ├─ IntegrationController
│  (receive from CRM)        │  (receive from TMA)
│                            │
└─ HTTP API                  └─ HTTP API
   POST /integration/*           POST /integration/*
```

## ЗАЩИТА ОТ ДУБЛИКАТОВ

- Проверка по `external_message_id` перед созданием
- Сохранение `external_message_id` после успешной отправки
- Возврат существующего сообщения при дубликате (200 OK)







