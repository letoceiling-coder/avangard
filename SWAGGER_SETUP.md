# Настройка Swagger/OpenAPI документации

## 📦 Установка L5-Swagger (рекомендуется)

### Шаг 1: Установка пакета

```bash
composer require darkaonline/l5-swagger
```

### Шаг 2: Публикация конфигурации

```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### Шаг 3: Настройка конфигурации

Отредактировать `config/l5-swagger.php`:

```php
'paths' => [
    'docs' => base_path('swagger.yaml'), // Путь к нашему файлу
    'annotations' => base_path('app'),
],
```

### Шаг 4: Генерация документации

```bash
php artisan l5-swagger:generate
```

### Шаг 5: Просмотр

Открыть: `http://localhost/api/documentation`

---

## 🔧 Альтернативные варианты

### Вариант 1: Swagger Editor (онлайн)

1. Открыть https://editor.swagger.io/
2. File → Import File → выбрать `swagger.yaml`
3. Просмотр и редактирование

### Вариант 2: Swagger UI (Docker)

```bash
docker run -p 8080:8080 \
  -e SWAGGER_JSON=/swagger.yaml \
  -v $(pwd):/swagger \
  swaggerapi/swagger-ui
```

Открыть: `http://localhost:8080`

### Вариант 3: Postman

1. Импортировать `swagger.yaml` в Postman
2. Автоматическая генерация коллекции

---

## 📝 Использование аннотаций (опционально)

Если хотите генерировать документацию из аннотаций в коде:

```php
/**
 * @OA\Get(
 *     path="/api/v1/blocks",
 *     summary="Список блоков",
 *     @OA\Parameter(name="city_id", in="query"),
 *     @OA\Response(response=200, description="Успешно")
 * )
 */
public function index(Request $request) { ... }
```

Но для нашего случая достаточно файла `swagger.yaml`.

---

## ✅ Готово!

Файл `swagger.yaml` содержит полную документацию всех endpoints.

