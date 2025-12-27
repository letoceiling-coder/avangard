<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrendSsoParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrendSsoController extends Controller
{
    /**
     * Авторизация через Trend SSO
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'login_url' => 'nullable|url',
        ]);

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');
            $loginUrl = $request->input('login_url');

            $parser = new TrendSsoParser($loginUrl);
            $authData = $parser->authenticate($phone, $password);

            return response()->json([
                'success' => true,
                'message' => 'Авторизация успешна',
                'data' => $authData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка авторизации Trend SSO', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение контента со страницы /objects/list
     */
    public function getObjectsList(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'url' => 'nullable|url',
            'parse' => 'nullable|boolean',
            'count' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string',
            'sort_order' => 'nullable|string|in:asc,desc',
            'room' => 'nullable|array',
            'room.*' => 'nullable|integer|min:1',
            'object_type' => 'nullable|string|in:apartments,parking,houses,plots,commercial',
        ]);

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');
            $targetUrl = $request->input('url', 'https://spb.trendagent.ru/objects/list');
            $shouldParse = $request->input('parse', true);

            // Авторизация через API (быстрее и надежнее)
            $apiAuth = new \App\Services\TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!$authData['authenticated'] ?? false) {
                throw new \Exception('Авторизация не удалась');
            }

            // Используем прямой API запрос вместо парсинга HTML
            // Контент генерируется через JavaScript, поэтому используем API /v4_29/blocks/search/
            $apiParams = [];
            
            // Извлекаем параметры из URL, если они есть
            $parsedUrl = parse_url($targetUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $urlParams);
                if (isset($urlParams['lang'])) {
                    $apiParams['lang'] = $urlParams['lang'];
                }
            }
            
            // Параметры пагинации и количества
            $count = (int) $request->input('count', 20);
            $offset = (int) $request->input('offset', 0);
            $page = (int) $request->input('page', 1);
            
            // Ограничиваем максимальное количество (защита от слишком больших запросов)
            $maxCount = 100;
            if ($count > $maxCount) {
                $count = $maxCount;
            }
            if ($count < 1) {
                $count = 20;
            }
            
            // Вычисляем offset из page, если передан page
            if ($page > 1 && $offset === 0) {
                $offset = ($page - 1) * $count;
            }
            
            $apiParams['count'] = $count;
            $apiParams['offset'] = $offset;
            
            // Дополнительные параметры сортировки
            if ($request->has('sort')) {
                $apiParams['sort'] = $request->input('sort');
            }
            if ($request->has('sort_order')) {
                $apiParams['sort_order'] = $request->input('sort_order');
            }
            
            // Параметр room для фильтрации по типу объекта
            // room=30 - Коттеджи (дома с участками)
            // room=40 - Таунхаусы (дома с участками)
            // room может быть массивом для нескольких типов
            if ($request->has('room')) {
                $roomValue = $request->input('room');
                if (is_array($roomValue)) {
                    $apiParams['room'] = array_filter($roomValue, function($r) {
                        return is_numeric($r) && $r > 0;
                    });
                } elseif (is_numeric($roomValue) && $roomValue > 0) {
                    $apiParams['room'] = [(int)$roomValue];
                }
            }
            
            // Тип объекта (для удобства)
            $objectType = $request->input('object_type', 'apartments'); // apartments, parking, houses, plots, commercial
            
            // Маппинг типов объектов на коды room
            $roomTypeMap = [
                'apartments' => [], // Квартиры - без фильтра room
                'parking' => [], // Паркинг (нужно уточнить код)
                'houses' => [30, 40], // Дома с участками: 30=Коттеджи, 40=Таунхаусы
                'plots' => [], // Участки (нужно уточнить код)
                'commercial' => [], // Коммерция (нужно уточнить код)
            ];
            
            // Если передан object_type и не передан room, используем маппинг
            if (!isset($apiParams['room']) && isset($roomTypeMap[$objectType])) {
                $mappedRooms = $roomTypeMap[$objectType];
                if (!empty($mappedRooms)) {
                    $apiParams['room'] = $mappedRooms;
                }
            }
            
            // Выбираем правильный метод API в зависимости от типа объекта
            if ($objectType === 'parking') {
                // Для паркинга используем отдельный API
                $apiData = $apiAuth->getParkingsSearch($apiParams);
            } elseif ($objectType === 'plots') {
                // Для участков используем отдельный API
                $apiData = $apiAuth->getPlotsSearch($apiParams);
            } elseif ($objectType === 'commercial') {
                // Для коммерции используем отдельный API
                $apiData = $apiAuth->getCommercialSearch($apiParams);
            } else {
                // Для остальных типов используем стандартный API
                $apiData = $apiAuth->getBlocksSearch($apiParams);
            }
            
            // Определяем, есть ли еще данные
            // Если вернулось меньше объектов, чем запрошено, значит это последняя страница
            $returnedCount = count($apiData['data'] ?? []);
            $hasMore = $returnedCount >= $count;
            
            // Определяем API endpoint в зависимости от типа объекта
            $apiEndpoint = match($objectType) {
                'parking' => 'https://parkings.trendagent.ru/search/blocks',
                'plots' => 'https://house-api.trendagent.ru/v1/search/villages',
                'commercial' => 'https://commerce.trendagent.ru/search/blocks',
                default => 'https://api.trendagent.ru/v4_29/blocks/search/',
            };
            
            $result = [
                'success' => true,
                'url' => $targetUrl,
                'source' => 'api',
                'api_endpoint' => $apiEndpoint,
                'object_type' => $objectType,
                'room_filter' => $apiParams['room'] ?? null,
                'pagination' => [
                    'count' => $count,
                    'offset' => $offset,
                    'page' => $page,
                    'has_more' => $hasMore,
                    'returned_count' => $returnedCount,
                ],
            ];

            // Добавляем данные из API (разная структура для разных типов)
            if ($objectType === 'parking') {
                $result['data'] = [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'places_count' => $apiData['places_count'] ?? 0,
                    'booked_places_count' => $apiData['booked_places_count'] ?? 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } elseif ($objectType === 'plots') {
                $result['data'] = [
                    'total_count' => $apiData['total_count'] ?? 0,
                    'result_count' => $apiData['result_count'] ?? 0,
                    'plots_count' => $apiData['plots_count'] ?? 0,
                    'blocks_count' => 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } elseif ($objectType === 'commercial') {
                $result['data'] = [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'premises_count' => $apiData['premises_count'] ?? 0,
                    'booked_premises_count' => $apiData['booked_premises_count'] ?? 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } else {
                $result['data'] = [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'prelaunches_count' => $apiData['prelaunches_count'] ?? 0,
                    'apartments_count' => $apiData['apartments_count'] ?? 0,
                    'booked_apartments_count' => $apiData['booked_apartments_count'] ?? 0,
                    'view_apartments_count' => $apiData['view_apartments_count'] ?? 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            }
            
            // Логируем структуру первого объекта для отладки (только структуру, без данных)
            if (!empty($apiData['data']) && is_array($apiData['data'])) {
                $firstObject = $apiData['data'][0];
                $structureSample = [];
                foreach ($firstObject as $key => $value) {
                    if (is_array($value)) {
                        $structureSample[$key] = array_keys($value);
                        // Для изображения показываем полную структуру
                        if ($key === 'image') {
                            $structureSample[$key] = $value;
                        }
                    } else {
                        $structureSample[$key] = gettype($value);
                    }
                }
                Log::info('Структура объекта из API (пример)', [
                    'structure' => $structureSample,
                    'image_url_example' => $firstObject['image']['url'] ?? null,
                ]);
            }

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения контента objects/list', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
