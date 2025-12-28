# Руководство по тестированию API TrendAgent Parser

**Дата создания:** 2025-12-28

---

## 📋 Содержание

1. [Swagger/OpenAPI документация](#swaggeropenapi-документация)
2. [Запуск тестов](#запуск-тестов)
3. [Структура тестов](#структура-тестов)
4. [Примеры тестов](#примеры-тестов)
5. [Разные варианты получения данных](#разные-варианты-получения-данных)

---

## 📖 Swagger/OpenAPI документация

### Файл: `swagger.yaml`

Полная OpenAPI 3.0.3 документация для всех endpoints API.

### Просмотр документации

#### Вариант 1: Swagger UI (если установлен l5-swagger)

```bash
# Установить пакет
composer require darkaonline/l5-swagger

# Опубликовать конфигурацию
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"

# Генерировать документацию
php artisan l5-swagger:generate

# Просмотр: http://localhost/api/documentation
```

#### Вариант 2: Онлайн редактор

1. Открыть https://editor.swagger.io/
2. Загрузить файл `swagger.yaml`
3. Просмотреть интерактивную документацию

#### Вариант 3: Swagger UI локально

```bash
# Установить Swagger UI через Docker
docker run -p 8080:8080 -e SWAGGER_JSON=/swagger.yaml -v $(pwd):/swagger swaggerapi/swagger-ui

# Открыть http://localhost:8080
```

---

## 🧪 Запуск тестов

### Все тесты

```bash
php artisan test
```

### Конкретный тест

```bash
php artisan test --filter BlockApiTest
php artisan test --filter ParkingApiTest
php artisan test --filter ParserErrorApiTest
php artisan test --filter TrendDataSyncTest
```

### С покрытием

```bash
php artisan test --coverage
```

### Только Feature тесты

```bash
php artisan test tests/Feature
```

---

## 📁 Структура тестов

```
tests/
├── Feature/
│   ├── BlockApiTest.php          # Тесты API блоков
│   ├── ParkingApiTest.php        # Тесты API паркинга
│   ├── ParserErrorApiTest.php    # Тесты API ошибок
│   └── TrendDataSyncTest.php     # Тесты синхронизации
└── Unit/
    └── (unit тесты)
```

---

## 🎯 Примеры тестов

### Тест: Получение списка блоков

```php
public function test_get_blocks_list_without_filters()
{
    Block::factory()->count(5)->create();
    
    $response = $this->withHeaders([
        'Authorization' => "Bearer {$this->token}",
    ])->get('/api/v1/blocks');
    
    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);
}
```

### Тест: Создание блока

```php
public function test_create_block()
{
    $blockData = [
        'city_id' => $this->city->id,
        'guid' => 'test-block',
        'name' => 'Тестовый ЖК',
        // ...
    ];
    
    $response = $this->postJson('/api/v1/blocks', $blockData);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('blocks', ['guid' => 'test-block']);
}
```

---

## 🔍 Разные варианты получения данных

### 1. Базовый список (без фильтров)

```bash
GET /api/v1/blocks
Authorization: Bearer {token}
```

**Ожидаемый результат:**
- Статус: 200
- Структура: `{data: [...], links: {...}, meta: {...}}`
- По умолчанию: только активные блоки
- Пагинация: 15 записей на страницу

### 2. С фильтром по городу

```bash
GET /api/v1/blocks?city_id=1
```

**Тест:** `test_get_blocks_list_filtered_by_city`

### 3. С фильтром по застройщику

```bash
GET /api/v1/blocks?builder_id=5
```

**Тест:** `test_get_blocks_list_filtered_by_builder`

### 4. С фильтром по цене

```bash
GET /api/v1/blocks?min_price=5000000&max_price=15000000
```

**Примечание:** Цены в фильтре указываются в **рублях**, но в БД хранятся в копейках.

**Тест:** `test_get_blocks_list_filtered_by_price`

### 5. С фильтром по эксклюзивности

```bash
GET /api/v1/blocks?is_exclusive=true
```

**Тест:** `test_get_blocks_list_filtered_by_exclusive`

### 6. С поиском

```bash
GET /api/v1/blocks?search=ОКО
```

Ищет по полям `name` и `address`.

**Тест:** `test_get_blocks_list_with_search`

### 7. С фильтром по метро

```bash
GET /api/v1/blocks?subway_id=3
```

**Тест:** `test_get_blocks_filtered_by_subway`

### 8. С сортировкой

```bash
GET /api/v1/blocks?sort=price&sort_direction=asc
```

Доступные варианты сортировки:
- `price` - по цене
- `name` - по названию
- `deadline` - по сроку сдачи
- `created` - по дате создания

**Тест:** `test_get_blocks_list_with_sorting`

### 9. С пагинацией

```bash
GET /api/v1/blocks?per_page=20&page=2
```

**Ограничения:**
- `per_page`: от 1 до 100
- `page`: минимум 1

**Тест:** `test_get_blocks_list_with_pagination`

### 10. С фильтром по источнику данных

```bash
GET /api/v1/blocks?data_source=parser
```

**Тест:** `test_get_blocks_filtered_by_data_source`

### 11. Комбинированные фильтры

```bash
GET /api/v1/blocks?city_id=1&builder_id=5&is_exclusive=true&min_price=5000000&sort=price&per_page=20
```

**Тест:** Комбинация всех фильтров работает корректно

### 12. Включение неактивных записей

```bash
GET /api/v1/blocks?include_inactive=true
```

По умолчанию показываются только активные (`is_active = true`).

---

## 📊 Тестовые сценарии

### ✅ Успешные сценарии

1. **Получение списка** - возвращает данные с правильной структурой
2. **Создание** - создает запись и возвращает 201
3. **Обновление** - обновляет запись и возвращает 200
4. **Удаление** - мягкое удаление (soft delete)
5. **Фильтрация** - все фильтры работают корректно
6. **Пагинация** - правильная структура links и meta

### ❌ Сценарии с ошибками

1. **401 Unauthorized** - без токена авторизации
2. **404 Not Found** - несуществующий ресурс
3. **422 Validation Error** - невалидные данные
4. **500 Server Error** - внутренние ошибки

---

## 🔧 Настройка тестов

### Переменные окружения для тестов

Создать `.env.testing`:

```env
APP_ENV=testing
DB_DATABASE=test_database
DB_CONNECTION=sqlite
```

### Запуск с очисткой БД

Тесты используют `RefreshDatabase` trait, который автоматически:
- Создает тестовую БД
- Запускает миграции
- Очищает БД после каждого теста

---

## 📝 Примеры использования API

### cURL примеры

```bash
# Получить список блоков
curl -X GET "http://localhost/api/v1/blocks?city_id=1&is_exclusive=true" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Создать блок
curl -X POST "http://localhost/api/v1/blocks" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "city_id": 1,
    "builder_id": 5,
    "guid": "test-block",
    "name": "Тестовый ЖК",
    "is_active": true
  }'

# Получить один блок
curl -X GET "http://localhost/api/v1/blocks/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript примеры

```javascript
// Получить список с фильтрами
const response = await fetch('/api/v1/blocks?city_id=1&min_price=5000000', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});

const data = await response.json();
console.log(data.data); // Массив блоков
console.log(data.meta); // Метаданные пагинации

// Создать блок
const newBlock = await fetch('/api/v1/blocks', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    city_id: 1,
    guid: 'test-block',
    name: 'Тестовый ЖК',
    // ...
  }),
});
```

---

## ✅ Покрытие тестами

### Текущее покрытие

- ✅ **BlockController** - все методы
- ✅ **ParkingController** - все методы
- ✅ **ParserErrorController** - основные методы
- ✅ **TrendDataSyncService** - синхронизация блоков
- ✅ **Фильтры** - все варианты фильтрации
- ✅ **Валидация** - ошибки валидации
- ✅ **Авторизация** - проверка токенов

### Что можно добавить

- [ ] Тесты для VillageController
- [ ] Тесты для CommercialBlockController
- [ ] Интеграционные тесты с реальным API
- [ ] Тесты производительности
- [ ] Тесты на больших объемах данных

---

**Все тесты готовы к запуску!**

