# Примеры использования API TrendAgent Parser

**Дата создания:** 2025-12-28

---

## 🔑 Авторизация

Все запросы требуют Bearer токен:

```bash
Authorization: Bearer YOUR_TOKEN
```

Получить токен можно через `/api/login` или создав токен вручную.

---

## 📋 Примеры запросов

### 1. Получить список блоков (базовый)

```bash
curl -X GET "http://localhost/api/v1/blocks" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ответ:**
```json
{
  "data": [
    {
      "id": 1,
      "guid": "oko",
      "name": "МФК ОКО",
      "city": {"id": 1, "name": "Москва"},
      "builder": {"id": 5, "name": "Capital Group"},
      "prices": {
        "min": 5000000,
        "min_formatted": "50 000 ₽"
      },
      "is_active": true
    }
  ],
  "links": {...},
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### 2. Получить список с фильтрами

```bash
curl -X GET "http://localhost/api/v1/blocks?city_id=1&is_exclusive=true&min_price=5000000&max_price=15000000&sort=price&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Параметры:**
- `city_id=1` - только Москва
- `is_exclusive=true` - только эксклюзивные
- `min_price=5000000` - от 50,000 руб
- `max_price=15000000` - до 150,000 руб
- `sort=price` - сортировка по цене
- `per_page=20` - 20 записей на страницу

### 3. Поиск по названию

```bash
curl -X GET "http://localhost/api/v1/blocks?search=ОКО" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

Ищет в полях `name` и `address`.

### 4. Фильтр по метро

```bash
curl -X GET "http://localhost/api/v1/blocks?subway_id=3" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 5. Создать блок

```bash
curl -X POST "http://localhost/api/v1/blocks" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "city_id": 1,
    "builder_id": 5,
    "guid": "test-block-123",
    "name": "ЖК Тестовый",
    "address": "Москва, ул. Тестовая, 1",
    "latitude": 55.7558,
    "longitude": 37.6173,
    "min_price": 5000000,
    "max_price": 15000000,
    "is_active": true,
    "data_source": "manual",
    "subway_ids": [1, 2, 3]
  }'
```

**Ответ (201):**
```json
{
  "data": {
    "id": 123,
    "guid": "test-block-123",
    "name": "ЖК Тестовый",
    "city": {...},
    "builder": {...},
    "subways": [...],
    "created_at": "2025-12-28T12:00:00Z"
  }
}
```

### 6. Получить один блок

```bash
curl -X GET "http://localhost/api/v1/blocks/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ответ включает:**
- Все связи (city, builder, subways, prices, images)
- Полную информацию о блоке

### 7. Обновить блок

```bash
curl -X PUT "http://localhost/api/v1/blocks/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Обновленное название",
    "min_price": 6000000
  }'
```

### 8. Удалить блок

```bash
curl -X DELETE "http://localhost/api/v1/blocks/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ответ:**
```json
{
  "message": "Блок успешно удален"
}
```

### 9. Получить список паркинга

```bash
curl -X GET "http://localhost/api/v1/parkings?city_id=1&status=available" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 10. Получить статистику ошибок

```bash
curl -X GET "http://localhost/api/v1/parser-errors/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ответ:**
```json
{
  "total": 150,
  "unresolved": 45,
  "by_type": {
    "api": 30,
    "parsing": 10,
    "validation": 5
  },
  "by_object_type": {
    "block": 25,
    "parking": 10
  },
  "recent": 12
}
```

---

## 🔍 Разные варианты фильтрации

### Вариант 1: Только активные эксклюзивные блоки в Москве

```bash
GET /api/v1/blocks?city_id=1&is_exclusive=true&is_active=true
```

### Вариант 2: Блоки с ценой от 5 до 15 млн, отсортированные по цене

```bash
GET /api/v1/blocks?min_price=5000000&max_price=15000000&sort=price&sort_direction=asc
```

### Вариант 3: Блоки рядом с определенной станцией метро

```bash
GET /api/v1/blocks?subway_id=5
```

### Вариант 4: Блоки определенного застройщика

```bash
GET /api/v1/blocks?builder_id=10
```

### Вариант 5: Блоки из парсера (не вручную созданные)

```bash
GET /api/v1/blocks?data_source=parser
```

### Вариант 6: Комбинированный фильтр

```bash
GET /api/v1/blocks?city_id=1&builder_id=5&is_exclusive=true&min_price=5000000&subway_id=3&sort=price&per_page=50
```

---

## 📊 JavaScript/TypeScript примеры

### Axios

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});

// Получить список блоков
const blocks = await api.get('/blocks', {
  params: {
    city_id: 1,
    is_exclusive: true,
    min_price: 5000000,
    sort: 'price',
    per_page: 20,
  },
});

// Создать блок
const newBlock = await api.post('/blocks', {
  city_id: 1,
  builder_id: 5,
  guid: 'test-block',
  name: 'ЖК Тестовый',
  // ...
});

// Обновить блок
const updated = await api.put(`/blocks/${blockId}`, {
  name: 'Новое название',
});

// Удалить блок
await api.delete(`/blocks/${blockId}`);
```

### Fetch API

```javascript
// Получить список
const response = await fetch('/api/v1/blocks?city_id=1', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});

const data = await response.json();
console.log(data.data); // Массив блоков

// Создать блок
const createResponse = await fetch('/api/v1/blocks', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    city_id: 1,
    guid: 'test-block',
    name: 'ЖК Тестовый',
  }),
});

const newBlock = await createResponse.json();
```

---

## 🐍 Python примеры

```python
import requests

BASE_URL = "http://localhost/api/v1"
TOKEN = "YOUR_TOKEN"

headers = {
    "Authorization": f"Bearer {TOKEN}",
    "Accept": "application/json",
}

# Получить список блоков
response = requests.get(
    f"{BASE_URL}/blocks",
    headers=headers,
    params={
        "city_id": 1,
        "is_exclusive": True,
        "min_price": 5000000,
        "sort": "price",
    }
)

blocks = response.json()["data"]

# Создать блок
new_block = requests.post(
    f"{BASE_URL}/blocks",
    headers={**headers, "Content-Type": "application/json"},
    json={
        "city_id": 1,
        "guid": "test-block",
        "name": "ЖК Тестовый",
    }
)
```

---

## ⚠️ Обработка ошибок

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found

```json
{
  "message": "No query results for model [App\\Models\\Trend\\Block] 123"
}
```

### 422 Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "city_id": ["The city id field is required."],
    "guid": ["The guid has already been taken."]
  }
}
```

### 500 Server Error

```json
{
  "message": "Ошибка при создании блока",
  "error": "Детали ошибки (только в debug режиме)"
}
```

---

## 📖 Полная документация

См. файл `swagger.yaml` для полной OpenAPI документации.

