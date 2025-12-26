# Руководство по созданию API роутов в Vue компонентах

## ⚠️ Критически важно

При создании API вызовов в Vue компонентах **ОБЯЗАТЕЛЬНО** следуйте этим правилам, чтобы избежать ошибок 404.

## Структура API

### Backend (Laravel)

В `routes/api.php` все роуты находятся внутри:

```php
Route::prefix('v1')->group(function () {
    Route::apiResource('bots', BotController::class);
    Route::apiResource('users', UserController::class);
    // ...
});
```

**Результат:** Все роуты имеют префикс `/api/v1/`

### Frontend (Vue)

В `resources/js/utils/api.js` определен:

```javascript
const API_BASE = '/api/v1';
```

Все функции (`apiGet`, `apiPost`, `apiPut`, `apiDelete`) автоматически добавляют `API_BASE` к переданному пути.

## ✅ Правила использования

### 1. Всегда начинайте путь с `/`

```javascript
// ✅ ПРАВИЛЬНО
apiGet('/bots')
apiGet('/users')
apiGet('/roles')

// ❌ НЕПРАВИЛЬНО
apiGet('bots')      // → /api/v1bots (отсутствует слэш)
apiGet('users')     // → /api/v1users (отсутствует слэш)
```

### 2. НЕ добавляйте `/v1/` или `/api/v1/`

```javascript
// ✅ ПРАВИЛЬНО
apiGet('/bots')              // → /api/v1/bots
apiGet('/bots/1')            // → /api/v1/bots/1
apiPost('/bots', data)       // → /api/v1/bots

// ❌ НЕПРАВИЛЬНО (приведет к 404)
apiGet('/v1/bots')           // → /api/v1/v1/bots (ОШИБКА!)
apiGet('/api/v1/bots')       // → /api/v1/api/v1/bots (ОШИБКА!)
apiGet('v1/bots')            // → /api/v1v1/bots (ОШИБКА!)
```

### 3. Используйте только имя ресурса

```javascript
// ✅ ПРАВИЛЬНО
apiGet('/bots')              // Ресурс: bots
apiGet('/users')             // Ресурс: users
apiGet('/roles')             // Ресурс: roles
apiGet('/folders')           // Ресурс: folders
apiGet('/media')             // Ресурс: media

// ❌ НЕПРАВИЛЬНО
apiGet('/v1/bots')           // Лишний префикс
apiGet('/api/bots')          // Лишний префикс
```

## Примеры правильного использования

### Базовые CRUD операции

```javascript
// Получить список
const response = await apiGet('/bots')
const data = await response.json()

// Получить один элемент
const response = await apiGet(`/bots/${id}`)
const data = await response.json()

// Создать
const response = await apiPost('/bots', {
    name: 'My Bot',
    token: '123456:ABC'
})

// Обновить
const response = await apiPut(`/bots/${id}`, {
    name: 'Updated Bot'
})

// Удалить
const response = await apiDelete(`/bots/${id}`)
```

### Специальные роуты

```javascript
// Проверка webhook
const response = await apiGet(`/bots/${id}/check-webhook`)

// Регистрация webhook
const response = await apiPost(`/bots/${id}/register-webhook`)

// С параметрами запроса
const response = await apiGet('/users', {
    page: 1,
    per_page: 15,
    search: 'test'
})
// → /api/v1/users?page=1&per_page=15&search=test
```

## Чеклист при создании нового компонента

- [ ] Проверил существующие роуты в `routes/api.php`
- [ ] Использовал путь БЕЗ `/v1/` и БЕЗ `/api/v1/`
- [ ] Начал путь с `/` (например: `/bots`, `/users`)
- [ ] Проверил примеры в существующих компонентах (`Users.vue`, `Roles.vue`)
- [ ] Протестировал API вызовы в браузере (Network tab)

## Примеры из существующих компонентов

### Users.vue

```javascript
// ✅ Правильно
const response = await apiGet('/users')
const response = await apiGet(`/users/${id}`)
const response = await apiPost('/users', userData)
const response = await apiPut(`/users/${id}`, userData)
const response = await apiDelete(`/users/${id}`)
```

### Roles.vue

```javascript
// ✅ Правильно
const response = await apiGet('/roles')
const response = await apiPost('/roles', roleData)
const response = await apiPut(`/roles/${id}`, roleData)
const response = await apiDelete(`/roles/${id}`)
```

### Bots.vue

```javascript
// ✅ Правильно
const response = await apiGet('/bots')
const response = await apiGet(`/bots/${id}/check-webhook`)
const response = await apiPost(`/bots/${id}/register-webhook`)
const response = await apiPost('/bots', botData)
const response = await apiPut(`/bots/${id}`, botData)
const response = await apiDelete(`/bots/${id}`)
```

## Отладка

Если получаете ошибку 404:

1. **Проверьте путь в Network tab браузера**
   - Должен быть: `/api/v1/bots`
   - НЕ должен быть: `/api/v1/v1/bots` или `/api/v1/api/v1/bots`

2. **Проверьте роут в `routes/api.php`**
   - Убедитесь, что роут существует
   - Проверьте middleware (может требоваться `admin`)

3. **Проверьте использование в компоненте**
   - Убедитесь, что путь начинается с `/`
   - Убедитесь, что нет `/v1/` или `/api/v1/` в пути

## Резюме

**Формула правильного пути:**
```
API_BASE + путь_из_компонента = полный_путь
/api/v1  + /bots              = /api/v1/bots ✅
```

**Помните:**
- ✅ Начинайте с `/`
- ✅ Используйте только имя ресурса
- ❌ НЕ добавляйте `/v1/`
- ❌ НЕ добавляйте `/api/v1/`

---

**Дата создания:** 26 декабря 2025  
**Последнее обновление:** 26 декабря 2025

