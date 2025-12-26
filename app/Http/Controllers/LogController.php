<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    /**
     * Показать страницу просмотра логов
     */
    public function index()
    {
        return view('logs.index');
    }

    /**
     * Получить логи (API endpoint)
     */
    public function getLogs(Request $request)
    {
        try {
            $logFile = $request->input('file', 'laravel.log');
            $lines = (int) $request->input('lines', 500); // По умолчанию последние 500 строк
            $lines = min($lines, 5000); // Максимум 5000 строк для безопасности

            $logPath = storage_path('logs/' . basename($logFile));

            // Проверка безопасности - только файлы из директории logs
            if (!File::exists($logPath) || !str_starts_with(realpath($logPath), realpath(storage_path('logs')))) {
                return response()->json([
                    'error' => 'Log file not found or access denied'
                ], 404);
            }

            // Получаем размер файла
            $fileSize = File::size($logPath);
            $fileSizeFormatted = $this->formatBytes($fileSize);

            // Читаем последние N строк файла
            $content = $this->readLastLines($logPath, $lines);

            // Получаем список всех лог-файлов
            $logFiles = $this->getLogFiles();

            return response()->json([
                'content' => $content,
                'file' => basename($logFile),
                'file_size' => $fileSize,
                'file_size_formatted' => $fileSizeFormatted,
                'lines_count' => count(explode("\n", $content)),
                'available_files' => $logFiles,
            ]);
        } catch (\Exception $e) {
            Log::error('Error reading logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error reading log file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Очистить логи
     */
    public function clearLogs(Request $request)
    {
        try {
            $logFile = $request->input('file', 'laravel.log');
            $logPath = storage_path('logs/' . basename($logFile));

            // Проверка безопасности
            if (!File::exists($logPath) || !str_starts_with(realpath($logPath), realpath(storage_path('logs')))) {
                return response()->json([
                    'error' => 'Log file not found or access denied'
                ], 404);
            }

            // Очищаем файл (создаем пустой файл)
            File::put($logPath, '');

            Log::info('Log file cleared', [
                'file' => basename($logFile),
                'cleared_at' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Log file cleared successfully',
                'file' => basename($logFile),
                'cleared_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error clearing log file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить список всех лог-файлов
     */
    public function getLogFilesList()
    {
        try {
            $logFiles = $this->getLogFiles();

            return response()->json([
                'files' => $logFiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error getting log files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Читать последние N строк файла
     */
    private function readLastLines(string $filePath, int $lines = 500): string
    {
        if (!File::exists($filePath)) {
            return '';
        }

        try {
            // Для больших файлов используем более эффективный метод
            $fileSize = File::size($filePath);
            
            // Если файл небольшой, читаем полностью
            if ($fileSize < 1024 * 1024) { // < 1MB
                $content = File::get($filePath);
                $allLines = explode("\n", $content);
                $lastLines = array_slice($allLines, -$lines);
                return implode("\n", $lastLines);
            }

            // Для больших файлов используем обратное чтение
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key() + 1;

            $startLine = max(0, $totalLines - $lines);
            $file->seek($startLine);

            $content = '';
            while (!$file->eof()) {
                $content .= $file->current();
                $file->next();
            }

            return trim($content);
        } catch (\Exception $e) {
            Log::error('Error reading log file', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Получить список всех лог-файлов
     */
    private function getLogFiles(): array
    {
        $logPath = storage_path('logs');
        $files = [];

        if (File::exists($logPath) && File::isDirectory($logPath)) {
            $logFiles = File::files($logPath);

            foreach ($logFiles as $file) {
                $fileName = $file->getFilename();
                // Показываем только .log файлы
                if (str_ends_with($fileName, '.log')) {
                    $filePath = $file->getPathname();
                    $fileSize = File::size($filePath);
                    $modified = File::lastModified($filePath);

                    $files[] = [
                        'name' => $fileName,
                        'size' => $fileSize,
                        'size_formatted' => $this->formatBytes($fileSize),
                        'modified' => date('Y-m-d H:i:s', $modified),
                        'modified_timestamp' => $modified,
                    ];
                }
            }

            // Сортируем по дате изменения (новые сверху)
            usort($files, function ($a, $b) {
                return $b['modified_timestamp'] <=> $a['modified_timestamp'];
            });
        }

        return $files;
    }

    /**
     * Форматировать размер файла
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

