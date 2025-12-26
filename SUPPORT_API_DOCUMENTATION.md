# Документация API системы поддержки

## Обзор

Система поддержки предоставляет REST API для управления тикетами поддержки с интеграцией внешнего CRM. Все внешние webhook-роуты защищены токеном `DEPLOY_TOKEN` из `.env`.

---

## Базовый URL

```
http://your-domain.com/api/v1
```

---

## Аутентификация

### Для админ-панели (создание тикетов, отправка сообщений)

Все запросы требуют Bearer токен в заголовке:

```
Authorization: Bearer {sanctum_token}
```

### Для webhook-роутов (внешний CRM)

Все webhook-роуты требуют токен `DEPLOY_TOKEN` в заголовке:

```
Authorization: Bearer {DEPLOY_TOKEN}
```

Где `DEPLOY_TOKEN` - значение из `.env` файла сервера.

---

## Роуты для админ-панели

### 1. Получить список тикетов

**Метод:** `GET`  
**URL:** `/api/v1/support/tickets`  
**Аутентификация:** `Bearer {sanctum_token}`  
**Доступ:** Только для администраторов и менеджеров

#### Query параметры:

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `status` | string | Нет | Фильтр по статусу: `open`, `in_progress`, `closed` |
| `page` | integer | Нет | Номер страницы (по умолчанию: 1) |
| `per_page` | integer | Нет | Количество на странице (по умолчанию: 20) |

#### Пример запроса:

```bash
GET /api/v1/support/tickets?status=open&page=1&per_page=20
Headers:
  Authorization: Bearer {sanctum_token}
  Accept: application/json
```

#### Пример ответа (200 OK):

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "theme": "Проблема с авторизацией",
        "status": "open",
        "created_at": "2025-12-13T10:00:00.000000Z",
        "updated_at": "2025-12-13T10:00:00.000000Z",
        "messages": [
          {
            "id": "660e8400-e29b-41d4-a716-446655440001",
            "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
            "sender": "local",
            "message": "Не могу войти в систему",
            "attachments": null,
            "created_at": "2025-12-13T10:00:00.000000Z"
          }
        ]
      }
    ],
    "current_page": 1,
    "last_page": 5,
    "from": 1,
    "to": 20,
    "total": 100
  }
}
```

---

### 2. Получить тикет с сообщениями

**Метод:** `GET`  
**URL:** `/api/v1/support/tickets/{id}`  
**Аутентификация:** `Bearer {sanctum_token}`  
**Доступ:** Только для администраторов и менеджеров

#### Параметры пути:

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `id` | UUID | Да | UUID тикета |

#### Пример запроса:

```bash
GET /api/v1/support/tickets/550e8400-e29b-41d4-a716-446655440000
Headers:
  Authorization: Bearer {sanctum_token}
  Accept: application/json
```

#### Пример ответа (200 OK):

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "theme": "Проблема с авторизацией",
    "status": "open",
    "created_at": "2025-12-13T10:00:00.000000Z",
    "updated_at": "2025-12-13T10:00:00.000000Z",
    "messages": [
      {
        "id": "660e8400-e29b-41d4-a716-446655440001",
        "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
        "sender": "local",
        "message": "Не могу войти в систему",
        "attachments": [
          {
            "name": "screenshot.png",
            "path": "support/attachments/abc123_screenshot.png",
            "size": 102400,
            "mime_type": "image/png",
            "url": "http://your-domain.com/storage/support/attachments/abc123_screenshot.png"
          }
        ],
        "created_at": "2025-12-13T10:00:00.000000Z"
      },
      {
        "id": "770e8400-e29b-41d4-a716-446655440002",
        "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
        "sender": "crm",
        "message": "Проверьте правильность ввода логина и пароля",
        "attachments": null,
        "created_at": "2025-12-13T11:00:00.000000Z"
      }
    ]
  }
}
```

---

### 3. Создать тикет

**Метод:** `POST`  
**URL:** `/api/v1/support/ticket`  
**Аутентификация:** `Bearer {sanctum_token}`  
**Доступ:** Только для администраторов и менеджеров  
**Content-Type:** `multipart/form-data`

#### Тело запроса (FormData):

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `theme` | string | Да | Тема тикета (макс. 255 символов) |
| `message` | string | Да | Текст сообщения |
| `attachments[]` | file[] | Нет | Массив файлов (макс. 10 МБ каждый) |

#### Поддерживаемые типы файлов:

- Изображения: `jpg`, `jpeg`, `png`, `gif`, `webp`
- Документы: `pdf`, `doc`, `docx`, `txt`

#### Пример запроса:

```bash
POST /api/v1/support/ticket
Headers:
  Authorization: Bearer {sanctum_token}
  Content-Type: multipart/form-data
  Accept: application/json

Body (FormData):
  theme: "Проблема с авторизацией"
  message: "Не могу войти в систему, выдает ошибку"
  attachments[0]: [file: screenshot.png]
  attachments[1]: [file: error.log]
```

#### Пример ответа (201 Created):

```json
{
  "success": true,
  "message": "Тикет успешно создан",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "theme": "Проблема с авторизацией",
    "status": "open",
    "created_at": "2025-12-13T10:00:00.000000Z",
    "updated_at": "2025-12-13T10:00:00.000000Z",
    "messages": [
      {
        "id": "660e8400-e29b-41d4-a716-446655440001",
        "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
        "sender": "local",
        "message": "Не могу войти в систему, выдает ошибку",
        "attachments": [
          {
            "name": "screenshot.png",
            "path": "support/attachments/abc123_screenshot.png",
            "size": 102400,
            "mime_type": "image/png",
            "url": "http://your-domain.com/storage/support/attachments/abc123_screenshot.png"
          }
        ],
        "created_at": "2025-12-13T10:00:00.000000Z"
      }
    ]
  }
}
```

**Примечание:** После создания тикет автоматически отправляется во внешний CRM по URL из конфигурации.

---

### 4. Отправить сообщение в тикет

**Метод:** `POST`  
**URL:** `/api/v1/support/message`  
**Аутентификация:** `Bearer {sanctum_token}`  
**Доступ:** Только для администраторов и менеджеров  
**Content-Type:** `multipart/form-data`

#### Ограничения:

- Чат доступен только для тикетов со статусом `open` или `in_progress`
- Для тикетов со статусом `closed` запрос вернет ошибку 403

#### Тело запроса (FormData):

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `ticket_id` | UUID | Да | UUID тикета |
| `message` | string | Да | Текст сообщения |
| `attachments[]` | file[] | Нет | Массив файлов (макс. 10 МБ каждый) |

#### Пример запроса:

```bash
POST /api/v1/support/message
Headers:
  Authorization: Bearer {sanctum_token}
  Content-Type: multipart/form-data
  Accept: application/json

Body (FormData):
  ticket_id: "550e8400-e29b-41d4-a716-446655440000"
  message: "Проверьте настройки браузера"
  attachments[0]: [file: config.json]
```

#### Пример ответа (201 Created):

```json
{
  "success": true,
  "message": "Сообщение отправлено",
  "data": {
    "id": "880e8400-e29b-41d4-a716-446655440003",
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "sender": "local",
    "message": "Проверьте настройки браузера",
    "attachments": [
      {
        "name": "config.json",
        "path": "support/attachments/def456_config.json",
        "size": 2048,
        "mime_type": "application/json",
        "url": "http://your-domain.com/storage/support/attachments/def456_config.json"
      }
    ],
    "created_at": "2025-12-13T12:00:00.000000Z"
  }
}
```

#### Пример ошибки (403 Forbidden):

```json
{
  "success": false,
  "message": "Чат недоступен для закрытых тикетов"
}
```

---

## Webhook-роуты для внешнего CRM

Все webhook-роуты требуют токен `DEPLOY_TOKEN` в заголовке `Authorization: Bearer {DEPLOY_TOKEN}`.

### 5. Получить сообщение от CRM

**Метод:** `POST`  
**URL:** `/api/support/webhook/message`  
**Аутентификация:** `Bearer {DEPLOY_TOKEN}`  
**Content-Type:** `application/json`

#### Тело запроса (JSON):

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `ticket_id` | UUID | Да | UUID тикета |
| `message` | string | Да | Текст сообщения от CRM |
| `attachments` | array | Нет | Массив объектов с информацией о вложениях |

#### Структура объекта attachment:

```json
{
  "name": "string",
  "url": "string",
  "size": "integer",
  "mime_type": "string"
}
```

#### Пример запроса:

```bash
POST /api/support/webhook/message
Headers:
  Authorization: Bearer {DEPLOY_TOKEN}
  Content-Type: application/json
  Accept: application/json

Body (JSON):
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Мы проверили ваш запрос. Проблема решена.",
  "attachments": [
    {
      "name": "solution.pdf",
      "url": "http://crm.example.com/files/solution.pdf",
      "size": 51200,
      "mime_type": "application/pdf"
    }
  ]
}
```

#### Пример ответа (201 Created):

```json
{
  "success": true,
  "message": "Сообщение получено",
  "data": {
    "id": "990e8400-e29b-41d4-a716-446655440004",
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "sender": "crm",
    "message": "Мы проверили ваш запрос. Проблема решена.",
    "attachments": [
      {
        "name": "solution.pdf",
        "url": "http://crm.example.com/files/solution.pdf",
        "size": 51200,
        "mime_type": "application/pdf"
      }
    ],
    "created_at": "2025-12-13T13:00:00.000000Z"
  }
}
```

---

### 6. Изменить статус тикета от CRM

**Метод:** `POST`  
**URL:** `/api/support/webhook/status`  
**Аутентификация:** `Bearer {DEPLOY_TOKEN}`  
**Content-Type:** `application/json`

#### Тело запроса (JSON):

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `ticket_id` | UUID | Да | UUID тикета |
| `status` | string | Да | Новый статус: `open`, `in_progress`, `closed` |

#### Пример запроса:

```bash
POST /api/support/webhook/status
Headers:
  Authorization: Bearer {DEPLOY_TOKEN}
  Content-Type: application/json
  Accept: application/json

Body (JSON):
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "in_progress"
}
```

#### Пример ответа (200 OK):

```json
{
  "success": true,
  "message": "Статус обновлен",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "theme": "Проблема с авторизацией",
    "status": "in_progress",
    "created_at": "2025-12-13T10:00:00.000000Z",
    "updated_at": "2025-12-13T13:00:00.000000Z"
  }
}
```

---

## Интеграция с внешним CRM

### Отправка тикета в CRM

При создании тикета система автоматически отправляет POST-запрос во внешний CRM.

#### URL CRM:

Настраивается в `.env`:
```
APP_CRM_URL=https://crm.siteaccess.ru/api/v1/tecket
```

Или в `config/app.php`:
```php
'crm_url' => env('APP_CRM_URL', 'https://crm.siteaccess.ru/api/v1/ticket'),
```

#### Запрос от системы поддержки в CRM:

**Метод:** `POST`  
**URL:** Значение из `APP_CRM_URL`  
**Headers:**
```
Authorization: Bearer {DEPLOY_TOKEN}
Content-Type: application/json
Accept: application/json
```

**Тело запроса (JSON):**

```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "theme": "Проблема с авторизацией",
  "message": "Не могу войти в систему, выдает ошибку",
  "attachments": [
    {
      "name": "screenshot.png",
      "url": "http://your-domain.com/storage/support/attachments/abc123_screenshot.png",
      "size": 102400,
      "mime_type": "image/png"
    }
  ],
  "project": "your_project_identifier"
}
```

**Параметры:**

| Поле | Тип | Описание |
|------|-----|----------|
| `ticket_id` | UUID | Уникальный идентификатор тикета |
| `theme` | string | Тема тикета |
| `message` | string | Текст первого сообщения |
| `attachments` | array | Массив объектов с информацией о вложениях |
| `project` | string | Идентификатор проекта (из `APP_PROJECT_IDENTIFIER`) |

**Идентификатор проекта:**

Настраивается в `.env`:
```
APP_PROJECT_IDENTIFIER=your_project_name
```

---

## Коды ответов

| Код | Описание |
|-----|----------|
| `200` | Успешный запрос |
| `201` | Ресурс создан |
| `403` | Доступ запрещен (неверный токен или закрытый тикет) |
| `404` | Ресурс не найден |
| `422` | Ошибка валидации |
| `500` | Внутренняя ошибка сервера |

---

## Обработка ошибок

### Формат ошибки валидации (422):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "theme": [
      "Тема тикета обязательна"
    ],
    "message": [
      "Сообщение обязательно"
    ]
  }
}
```

### Формат ошибки доступа (403):

```json
{
  "success": false,
  "message": "Неверный секретный ключ"
}
```

Или для закрытых тикетов:

```json
{
  "success": false,
  "message": "Чат недоступен для закрытых тикетов"
}
```

### Формат ошибки сервера (500):

```json
{
  "success": false,
  "message": "Ошибка при создании тикета"
}
```

---

## Статусы тикетов

| Статус | Описание | Чат доступен |
|--------|----------|--------------|
| `open` | Тикет открыт | ✅ Да |
| `in_progress` | Тикет в работе | ✅ Да |
| `closed` | Тикет закрыт | ❌ Нет |

---

## Типы отправителей сообщений

| Тип | Описание |
|-----|----------|
| `local` | Сообщение от администратора (из админ-панели) |
| `crm` | Сообщение от внешнего CRM (через webhook) |

---

## Ограничения

1. **Размер файла:** Максимум 10 МБ на файл
2. **Типы файлов:** Только изображения, PDF и документы (см. список выше)
3. **Чат:** Доступен только для тикетов со статусом `open` или `in_progress`
4. **Токен:** `DEPLOY_TOKEN` должен быть одинаковым на обоих серверах (система поддержки и CRM)

---

## Примеры использования

### cURL - Создать тикет

```bash
curl -X POST "http://your-domain.com/api/v1/support/ticket" \
  -H "Authorization: Bearer {sanctum_token}" \
  -F "theme=Проблема с авторизацией" \
  -F "message=Не могу войти в систему" \
  -F "attachments[]=@screenshot.png"
```

### cURL - Отправить сообщение

```bash
curl -X POST "http://your-domain.com/api/v1/support/message" \
  -H "Authorization: Bearer {sanctum_token}" \
  -F "ticket_id=550e8400-e29b-41d4-a716-446655440000" \
  -F "message=Проверьте настройки браузера" \
  -F "attachments[]=@config.json"
```

### cURL - Webhook: Сообщение от CRM

```bash
curl -X POST "http://your-domain.com/api/support/webhook/message" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "message": "Мы проверили ваш запрос. Проблема решена."
  }'
```

### cURL - Webhook: Изменить статус

```bash
curl -X POST "http://your-domain.com/api/support/webhook/status" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "closed"
  }'
```

### JavaScript (Fetch) - Получить список тикетов

```javascript
const response = await fetch('http://your-domain.com/api/v1/support/tickets?status=open&page=1', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer {sanctum_token}',
    'Accept': 'application/json'
  }
});

const data = await response.json();
console.log(data);
```

### JavaScript (Fetch) - Webhook: Отправить сообщение от CRM

```javascript
const response = await fetch('http://your-domain.com/api/support/webhook/message', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer {DEPLOY_TOKEN}',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    ticket_id: '550e8400-e29b-41d4-a716-446655440000',
    message: 'Мы проверили ваш запрос. Проблема решена.',
    attachments: []
  })
});

const data = await response.json();
console.log(data);
```

---

## Настройка на удаленном сервере

### 1. Настройка переменных окружения

В файле `.env` на сервере системы поддержки:

```env
# Токен для защиты webhook-роутов
DEPLOY_TOKEN=your_secure_token_here_min_32_chars

# URL внешнего CRM (куда отправляются тикеты)
APP_CRM_URL=https://crm.siteaccess.ru/api/v1/tecket

# Идентификатор проекта (отправляется в CRM)
APP_PROJECT_IDENTIFIER=your_project_name
```

### 2. Настройка на стороне CRM

В CRM должен быть настроен тот же `DEPLOY_TOKEN` для отправки webhook-запросов обратно в систему поддержки.

### 3. Проверка связи

#### Тест отправки тикета в CRM:

1. Создайте тикет через админ-панель
2. Проверьте логи Laravel: `storage/logs/laravel.log`
3. Должна быть запись: `Ticket sent to CRM successfully`

#### Тест получения сообщения от CRM:

```bash
curl -X POST "http://your-domain.com/api/support/webhook/message" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "message": "Тестовое сообщение"
  }'
```

Ожидаемый ответ: `{"success": true, "message": "Сообщение получено", ...}`

#### Тест изменения статуса:

```bash
curl -X POST "http://your-domain.com/api/support/webhook/status" \
  -H "Authorization: Bearer {DEPLOY_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "in_progress"
  }'
```

Ожидаемый ответ: `{"success": true, "message": "Статус обновлен", ...}`

### 4. Настройка файлового хранилища

Убедитесь, что символическая ссылка создана:

```bash
php artisan storage:link
```

Это создаст ссылку `public/storage` → `storage/app/public`, что необходимо для доступа к загруженным файлам.

### 5. Проверка прав доступа

Убедитесь, что директория для загрузки файлов имеет правильные права:

```bash
chmod -R 755 storage/app/public/support
```

---

## Безопасность

1. **Токен DEPLOY_TOKEN:**
   - Должен быть длиной минимум 32 символа
   - Должен быть уникальным и секретным
   - Не должен храниться в системе контроля версий
   - Должен быть одинаковым на обоих серверах (система поддержки и CRM)

2. **HTTPS:**
   - Рекомендуется использовать HTTPS для всех запросов
   - Особенно важно для передачи токенов и файлов

3. **Валидация:**
   - Все входные данные валидируются
   - Файлы проверяются на тип и размер
   - UUID проверяются на корректность формата

---

## Логирование

Все операции логируются в `storage/logs/laravel.log`:

- Успешная отправка тикета в CRM
- Ошибки при отправке в CRM
- Ошибки обработки webhook-запросов
- Попытки доступа с неверным токеном

---

## Поддержка

При возникновении проблем проверьте:

1. Логи Laravel: `storage/logs/laravel.log`
2. Правильность токена `DEPLOY_TOKEN`
3. Доступность внешнего CRM
4. Правильность формата UUID
5. Соответствие Content-Type заголовков

---

**Версия документации:** 1.0  
**Дата обновления:** 2025-12-13

