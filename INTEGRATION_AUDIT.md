# АУДИТ ИНТЕГРАЦИИ TMA ↔ CRM

## 1. КРИТИЧЕСКИЕ ПРОБЛЕМЫ

### TMA (Проект A)
1. ❌ **Нет `external_id`** - не сохраняется ID тикета из CRM после создания
2. ❌ **Нет `external_message_id`** - нет защиты от дубликатов сообщений
3. ❌ **TODO в `sendMessage`** - сообщения из TMA НЕ отправляются в CRM
4. ❌ **Несоответствие полей** - TMA использует `theme`, CRM использует `subject`
5. ❌ **Неясный `sender`** - используется `local|crm`, должно быть `tma|crm`

### CRM (Проект B)
1. ❌ **Нет `external_id`** - не сохраняется ID тикета из TMA
2. ❌ **Нет `external_message_id`** - нет защиты от дубликатов
3. ⚠️ **Несоответствие полей** - CRM использует `subject`, TMA использует `theme`
4. ⚠️ **Неясный `sender`** - используется `local|crm`, должно быть `tma|crm`

### Интеграция
1. ❌ **Односторонняя отправка сообщений** - только CRM → TMA, TMA → CRM не работает
2. ❌ **Разные пути API** - TMA: `/api/support/*`, CRM: `/api/v1/support/*`
3. ❌ **Нет единого контракта** - разные форматы запросов/ответов
4. ❌ **Нет синхронизации ID** - тикеты не связаны между проектами

## 2. ТЕКУЩАЯ АРХИТЕКТУРА

### TMA → CRM
```
POST /api/v1/support/ticket (CRM)
Body: {
  ticket_id: UUID,
  theme: string,
  message: string,
  attachments: [],
  project: string,
  external_url: string
}
```

### CRM → TMA
```
POST /api/support/webhook/message (TMA)
Body: {
  ticket_id: UUID,
  message: string,
  attachments: [],
  project: string
}
```

## 3. ЧТО СЛОМАНО

1. **TMA создает тикет** → отправляет в CRM → **НЕ сохраняет external_id**
2. **TMA отправляет сообщение** → **НЕ отправляет в CRM** (TODO в коде)
3. **CRM отправляет сообщение** → TMA получает → работает ✅
4. **CRM меняет статус** → TMA получает → работает ✅

## 4. ЕДИНАЯ МОДЕЛЬ

### Ticket (оба проекта)
- `id` - UUID (локальный)
- `external_id` - UUID (ID из другого проекта)
- `status` - enum(open, in_progress, closed)
- `subject` - string (унифицировать название)
- `created_at`, `updated_at`

### Message (оба проекта)
- `id` - UUID/INT (локальный)
- `ticket_id` - UUID
- `external_message_id` - UUID/INT (ID из другого проекта)
- `sender_type` - enum(tma, crm)
- `body` - text
- `attachments` - json
- `created_at`

## 5. НОВЫЙ API КОНТРАКТ

### TMA → CRM: Создание тикета
```
POST /api/integration/tickets
Authorization: Bearer {DEPLOY_TOKEN}
Body: {
  external_ticket_id: UUID,
  subject: string,
  message: string,
  attachments: []
}
```

### TMA → CRM: Отправка сообщения
```
POST /api/integration/messages
Authorization: Bearer {DEPLOY_TOKEN}
Body: {
  external_ticket_id: UUID,
  message: string,
  attachments: [],
  sender: "tma"
}
```

### CRM → TMA: Отправка сообщения
```
POST /api/integration/messages
Authorization: Bearer {DEPLOY_TOKEN}
Body: {
  external_ticket_id: UUID,
  message: string,
  attachments: [],
  sender: "crm"
}
```

### CRM → TMA: Изменение статуса
```
POST /api/integration/status
Authorization: Bearer {DEPLOY_TOKEN}
Body: {
  external_ticket_id: UUID,
  status: "open|in_progress|closed"
}
```

## 6. ПЛАН РЕФАКТОРИНГА

1. ✅ Создать миграции для `external_id` и `external_message_id`
2. ✅ Переименовать `theme` → `subject` в TMA
3. ✅ Изменить `sender` enum: `local|crm` → `tma|crm`
4. ✅ Создать `IntegrationService` в обоих проектах
5. ✅ Унифицировать роуты: `/api/integration/*`
6. ✅ Реализовать отправку сообщений TMA → CRM
7. ✅ Добавить защиту от дубликатов по `external_message_id`







