<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ParserController extends Controller
{
    /**
     * Запустить парсер вручную
     */
    public function run(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|array',
            'type.*' => 'string|in:blocks,parkings,villages,plots,commercial-blocks,commercial-premises',
            'city' => 'nullable|array',
            'city.*' => 'string',
            'check_images' => 'nullable|boolean',
            'force' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:10000',
            'skip_errors' => 'nullable|boolean',
        ]);

        try {
            // Формируем команду
            $command = 'trend:parse';
            $parameters = [];

            if ($request->has('type') && !empty($request->input('type'))) {
                foreach ($request->input('type') as $type) {
                    $parameters['--type'][] = $type;
                }
            }

            if ($request->has('city') && !empty($request->input('city'))) {
                foreach ($request->input('city') as $city) {
                    $parameters['--city'][] = $city;
                }
            }

            if ($request->boolean('check_images')) {
                $parameters['--check-images'] = true;
            }

            if ($request->boolean('force')) {
                $parameters['--force'] = true;
            }

            if ($request->has('limit')) {
                $parameters['--limit'] = $request->input('limit');
            }

            if ($request->boolean('skip_errors')) {
                $parameters['--skip-errors'] = true;
            }

            // Запускаем команду в фоне
            $exitCode = Artisan::call($command, $parameters);

            Log::info('ParserController: Manual parser run initiated', [
                'parameters' => $parameters,
                'exit_code' => $exitCode,
            ]);

            if ($exitCode === 0) {
                return response()->json([
                    'message' => 'Парсер успешно запущен',
                    'status' => 'running',
                ], 202);
            } else {
                return response()->json([
                    'message' => 'Ошибка при запуске парсера',
                    'status' => 'error',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('ParserController: Error running parser', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при запуске парсера: ' . $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Получить статус последнего запуска парсера
     */
    public function status(): JsonResponse
    {
        // Здесь можно добавить логику для получения статуса из логов или БД
        return response()->json([
            'status' => 'unknown',
            'message' => 'Статус парсера',
        ]);
    }
}

