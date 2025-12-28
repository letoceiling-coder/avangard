<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DeployController extends Controller
{
    protected $phpPath;
    protected $phpVersion;
    protected $basePath;

    public function __construct()
    {
        $this->basePath = base_path();
    }

    /**
     * Выполнить деплой на сервере
     */
    public function deploy(Request $request)
    {
        $startTime = microtime(true);
        Log::info('🚀 Начало деплоя', [
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $result = [
            'success' => false,
            'message' => '',
            'data' => [],
        ];

        try {
            // Определяем PHP путь
            $this->phpPath = $this->getPhpPath();
            $this->phpVersion = $this->getPhpVersion();

            Log::info("Используется PHP: {$this->phpPath} (версия: {$this->phpVersion})");

            // 0. Очистка файлов разработки в начале
            $this->cleanDevelopmentFiles();

            // Получаем ветку из запроса или используем текущую ветку сервера
            $requestedBranch = $request->input('branch');
            if (!$requestedBranch) {
                // Пытаемся определить текущую ветку на сервере
                $currentBranchProcess = Process::path($this->basePath)
                    ->run('git rev-parse --abbrev-ref HEAD 2>&1');
                $requestedBranch = trim($currentBranchProcess->output()) ?: 'main';
            }
            
            // Получаем ожидаемый commit hash из запроса
            $expectedCommitHash = $request->input('commit_hash');
            
            Log::info("🌿 Используется ветка для деплоя: {$requestedBranch}");
            if ($expectedCommitHash) {
                Log::info("🎯 Ожидаемый коммит: " . substr($expectedCommitHash, 0, 7));
            }

            // 1. Git pull
            $gitPullResult = $this->handleGitPull($requestedBranch, $expectedCommitHash);
            
            // Получаем текущий commit hash ПОСЛЕ настройки безопасной директории
            $oldCommitHash = $this->getCurrentCommitHash();
            $result['data']['git_pull'] = $gitPullResult['status'];
            $result['data']['branch'] = $gitPullResult['branch'] ?? 'unknown';
            if (!$gitPullResult['success']) {
                throw new \Exception("Ошибка git pull: {$gitPullResult['error']}");
            }

            // 1.5. Проверка наличия собранных файлов фронтенда
            $frontendCheck = $this->checkFrontendFiles();
            $result['data']['frontend_files'] = $frontendCheck;
            if (!$frontendCheck['manifest_exists']) {
                Log::warning('⚠️ Manifest.json не найден после git pull. Убедитесь, что файлы собраны локально и закоммичены в git.');
            }

            // 2. Composer install
            $composerResult = $this->handleComposerInstall();
            $result['data']['composer_install'] = $composerResult['status'];
            if (!$composerResult['success']) {
                throw new \Exception("Ошибка composer install: {$composerResult['error']}");
            }

            // 2.5. Очистка кешей после composer install
            $this->clearPackageDiscoveryCache();

            // 3. Миграции
            $migrationsResult = $this->runMigrations();
            $result['data']['migrations'] = $migrationsResult;
            if ($migrationsResult['status'] !== 'success') {
                throw new \Exception("Ошибка миграций: {$migrationsResult['error']}");
            }

            // 3.5. Выполнение seeders (только если явно запрошено)
            $runSeeders = $request->input('run_seeders', false);
            if ($runSeeders) {
                $seedersResult = $this->runSeeders();
                $result['data']['seeders'] = $seedersResult;
                Log::info('Seeders выполнены по запросу');
            } else {
                $result['data']['seeders'] = [
                    'status' => 'skipped',
                    'message' => 'Seeders пропущены (используйте --with-seed для выполнения)',
                ];
                Log::info('Seeders пропущены (не указан флаг run_seeders)');
            }

            // 4. Очистка временных файлов разработки
            $this->cleanDevelopmentFiles();

            // 5. Очистка кешей
            $cacheResult = $this->clearAllCaches();
            $result['data']['cache_cleared'] = $cacheResult['success'];

            // 6. Оптимизация
            $optimizeResult = $this->optimizeApplication();
            $result['data']['optimized'] = $optimizeResult['success'];

            // 7. Финальная очистка файлов разработки
            $this->cleanDevelopmentFiles();

            // Получаем новый commit hash
            $newCommitHash = $this->getCurrentCommitHash();

            // Формируем успешный ответ
            $result['success'] = true;
            $result['message'] = 'Деплой успешно завершен';
            $result['data'] = array_merge($result['data'], [
                'php_version' => $this->phpVersion,
                'php_path' => $this->phpPath,
                'branch' => $requestedBranch,
                'old_commit_hash' => $oldCommitHash,
                'new_commit_hash' => $newCommitHash,
                'commit_changed' => $oldCommitHash !== $newCommitHash,
                'deployed_at' => now()->toDateTimeString(),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
            ]);

            Log::info('✅ Деплой успешно завершен', $result['data']);

        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['data']['error'] = $e->getMessage();
            $result['data']['trace'] = config('app.debug') ? $e->getTraceAsString() : null;
            $result['data']['deployed_at'] = now()->toDateTimeString();
            $result['data']['duration_seconds'] = round(microtime(true) - $startTime, 2);
            
            // Добавляем информацию о ветке даже при ошибке
            if (isset($requestedBranch)) {
                $result['data']['branch'] = $requestedBranch;
            }

            Log::error('❌ Ошибка деплоя', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'branch' => $requestedBranch ?? 'unknown',
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Определить путь к PHP
     */
    protected function getPhpPath(): string
    {
        // 1. Проверить явно указанный путь в .env
        $phpPath = env('PHP_PATH');
        if ($phpPath && $this->isPhpExecutable($phpPath)) {
            return $phpPath;
        }

        // 2. Попробовать автоматически найти PHP
        $possiblePaths = ['php8.2', 'php8.3', 'php8.1', 'php'];
        foreach ($possiblePaths as $path) {
            if ($this->isPhpExecutable($path)) {
                return $path;
            }
        }

        // 3. Fallback на 'php'
        return 'php';
    }

    /**
     * Проверить доступность PHP
     */
    protected function isPhpExecutable(string $path): bool
    {
        try {
            // Проверка через which (Unix-like)
            $result = shell_exec("which {$path} 2>/dev/null");
            if ($result && trim($result)) {
                return true;
            }

            // Проверка через exec (версия PHP)
            exec("{$path} --version 2>&1", $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить версию PHP
     */
    protected function getPhpVersion(): string
    {
        try {
            exec("{$this->phpPath} --version 2>&1", $output, $returnCode);
            if ($returnCode === 0 && isset($output[0])) {
                preg_match('/PHP\s+(\d+\.\d+\.\d+)/', $output[0], $matches);
                return $matches[1] ?? 'unknown';
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return 'unknown';
    }

    /**
     * Выполнить git pull
     * 
     * @param string $branch Ветка для обновления (если не указана, используется 'main')
     * @param string|null $expectedCommitHash Ожидаемый commit hash для проверки
     */
    protected function handleGitPull(string $branch = 'main', ?string $expectedCommitHash = null): array
    {
        try {
            // Логируем базовый путь для отладки
            Log::info("🔍 Базовая директория проекта: {$this->basePath}");
            Log::info("🔍 Проверка существования .git: " . (is_dir($this->basePath . '/.git') ? 'ДА' : 'НЕТ'));
            
            // Проверяем, что это git репозиторий
            $gitDir = $this->basePath . '/.git';
            if (!is_dir($gitDir)) {
                $error = "Директория не является git репозиторием. Путь: {$this->basePath}, .git существует: " . (file_exists($gitDir) ? 'да (но не директория)' : 'нет');
                Log::error($error);
                return [
                    'success' => false,
                    'status' => 'error',
                    'error' => $error,
                ];
            }

            // Настройка безопасной директории для git (решает проблему dubious ownership)
            // ВАЖНО: Это должно быть первым шагом перед всеми git командами
            $this->ensureGitSafeDirectory();
            
            // Определяем безопасную директорию для всех git команд
            // Используем одинарные кавычки внутри двойных для правильного экранирования
            $safeDirectoryPath = escapeshellarg($this->basePath);
            $gitEnv = [
                'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
            ];
            // Формируем команду с правильным экранированием
            $gitBaseCmd = 'git -c safe.directory=' . $safeDirectoryPath;

            // Проверяем статус git перед pull
            $statusProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' status --porcelain 2>&1');

            $hasChanges = !empty(trim($statusProcess->output()));

            // Если есть локальные изменения, сохраняем их в stash
            if ($hasChanges) {
                Log::info('Обнаружены локальные изменения, сохраняем в stash...');
                $stashMessage = 'Auto-stash before deploy ' . now()->toDateTimeString();
                $stashProcess = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' stash push -m ' . escapeshellarg($stashMessage) . ' 2>&1');

                if (!$stashProcess->successful()) {
                    Log::warning('Не удалось сохранить изменения в stash', [
                        'error' => $stashProcess->errorOutput(),
                    ]);
                }
            }

            // Получаем текущий commit перед обновлением
            $beforeCommit = $this->getCurrentCommitHash();
            Log::info("📦 Commit до обновления: " . ($beforeCommit ?: 'не определен'));
            Log::info("🌿 Обновляем ветку: {$branch}");

            // Проверяем текущую ветку и переключаемся на нужную, если необходимо
            $currentBranchProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' rev-parse --abbrev-ref HEAD 2>&1');
            $currentBranch = trim($currentBranchProcess->output()) ?: 'main';
            
            if ($currentBranch !== $branch) {
                Log::info("🔄 Переключаемся с ветки {$currentBranch} на {$branch}...");
                $checkoutProcess = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' checkout -B ' . escapeshellarg($branch) . ' 2>&1');
                
                if (!$checkoutProcess->successful()) {
                    Log::warning('⚠️ Не удалось переключиться на ветку', [
                        'output' => $checkoutProcess->output(),
                        'error' => $checkoutProcess->errorOutput(),
                    ]);
                } else {
                    Log::info("✅ Переключились на ветку {$branch}");
                }
            }

            // 1. Получаем последние изменения из репозитория
            // Сначала делаем полный fetch всех веток, чтобы гарантировать получение всех изменений
            Log::info("📥 Выполняем git fetch origin...");
            $fetchAllProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' fetch origin --prune 2>&1');

            if (!$fetchAllProcess->successful()) {
                Log::warning('⚠️ Не удалось выполнить git fetch origin', [
                    'output' => $fetchAllProcess->output(),
                    'error' => $fetchAllProcess->errorOutput(),
                ]);
            } else {
                Log::info('✅ Git fetch origin выполнен успешно');
            }

            // Дополнительно обновляем конкретную ветку
            Log::info("📥 Выполняем git fetch origin {$branch}...");
            $fetchProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' fetch origin ' . escapeshellarg($branch) . ' 2>&1');

            if (!$fetchProcess->successful()) {
                Log::warning('⚠️ Не удалось выполнить git fetch для ветки', [
                    'output' => $fetchProcess->output(),
                    'error' => $fetchProcess->errorOutput(),
                ]);
            } else {
                Log::info('✅ Git fetch для ветки выполнен успешно');
            }

            // Проверяем, что origin/{branch} существует
            $checkRemoteBranchProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' rev-parse --verify origin/' . escapeshellarg($branch) . ' 2>&1');
            
            if (!$checkRemoteBranchProcess->successful()) {
                Log::warning("⚠️ Ветка origin/{$branch} не найдена в remote", [
                    'output' => $checkRemoteBranchProcess->output(),
                    'error' => $checkRemoteBranchProcess->errorOutput(),
                ]);
            } else {
                $remoteCommit = trim($checkRemoteBranchProcess->output());
                Log::info("📍 Удаленный коммит origin/{$branch}: " . substr($remoteCommit, 0, 7));
            }

            // Используем переданный ожидаемый коммит
            $maxFetchAttempts = $expectedCommitHash ? 5 : 1; // Повторяем fetch до 5 раз, если ожидаем конкретный коммит
            $fetchDelay = 2; // Задержка между попытками в секундах
            
            // Если ожидается конкретный коммит, делаем несколько попыток fetch и проверяем его наличие
            $commitFound = false;
            if ($expectedCommitHash && strlen($expectedCommitHash) === 40) {
                Log::info("🎯 Ожидается коммит: " . substr($expectedCommitHash, 0, 7));
                
                // Сначала пробуем найти коммит в remote напрямую (независимо от origin/main)
                // Это работает, если коммит уже есть в remote репозитории
                for ($attempt = 1; $attempt <= $maxFetchAttempts; $attempt++) {
                    // Проверяем, существует ли коммит локально
                    $checkCommitProcess = Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' cat-file -e ' . escapeshellarg($expectedCommitHash) . ' 2>&1');
                    
                    if ($checkCommitProcess->successful()) {
                        Log::info("✅ Коммит найден локально после попытки {$attempt}");
                        $commitFound = true;
                        break;
                    }
                    
                    // Если коммит не найден локально, пробуем fetch с конкретного коммита
                    // Это может работать, если remote знает о коммите, даже если он не в origin/main
                    Log::info("⏳ Коммит не найден локально, пробуем fetch с коммитом (попытка {$attempt}/{$maxFetchAttempts})...");
                    
                    // Пробуем fetch с конкретного коммита напрямую
                    $fetchCommitProcess = Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' fetch origin ' . escapeshellarg($expectedCommitHash) . ' 2>&1');
                    
                    // Также пробуем обычный fetch для ветки
                    Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' fetch origin ' . escapeshellarg($branch) . ' 2>&1');
                    
                    // И полный fetch для надежности
                    Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' fetch origin --prune 2>&1');
                    
                    if ($attempt < $maxFetchAttempts) {
                        // Ждем перед следующей попыткой
                        sleep($fetchDelay);
                    }
                }
                
                if (!$commitFound) {
                    Log::warning("⚠️ Ожидаемый коммит не найден локально после {$maxFetchAttempts} попыток fetch. Пробуем использовать его напрямую для reset.");
                }
            }

            // 2. Сбрасываем локальную ветку на origin/{branch} (принудительное обновление)
            // Если ожидается конкретный коммит, используем его напрямую
            if ($expectedCommitHash && strlen($expectedCommitHash) === 40) {
                // Сначала пробуем reset на конкретный коммит
                Log::info("🔄 Выполняем git reset --hard {$expectedCommitHash}...");
                $process = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' reset --hard ' . escapeshellarg($expectedCommitHash) . ' 2>&1');
                
                if (!$process->successful()) {
                    Log::warning("⚠️ Не удалось переключиться на коммит {$expectedCommitHash} напрямую, пробуем fetch и reset снова...");
                    
                    // Пробуем еще раз fetch и reset
                    Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' fetch origin --depth=100 ' . escapeshellarg($branch) . ' 2>&1');
                    
                    // Пробуем reset еще раз
                    $process = Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' reset --hard ' . escapeshellarg($expectedCommitHash) . ' 2>&1');
                    
                    if (!$process->successful()) {
                        Log::warning("⚠️ Не удалось переключиться на коммит {$expectedCommitHash} после повторного fetch, используем origin/{$branch}");
                        // Fallback на origin/branch
                        $process = Process::path($this->basePath)
                            ->env($gitEnv)
                            ->run($gitBaseCmd . ' reset --hard origin/' . escapeshellarg($branch) . ' 2>&1');
                    }
                }
            } else {
                // Обычный reset на origin/branch
                Log::info("🔄 Выполняем git reset --hard origin/{$branch}...");
                $process = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' reset --hard origin/' . escapeshellarg($branch) . ' 2>&1');
            }

            if (!$process->successful()) {
                Log::warning('Git reset --hard не удался, пробуем альтернативные методы', [
                    'error' => $process->errorOutput(),
                    'output' => $process->output(),
                ]);

                // Пробуем сначала обновить ссылку на remote
                Log::info("🔄 Обновляем ссылку на remote ветку...");
                $updateRefProcess = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' update-ref refs/heads/' . escapeshellarg($branch) . ' origin/' . escapeshellarg($branch) . ' 2>&1');
                
                if ($updateRefProcess->successful()) {
                    Log::info('✅ Ссылка на ветку обновлена через update-ref');
                    // Затем делаем reset
                    $process = Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' reset --hard HEAD 2>&1');
                } else {
                    // Если update-ref не сработал, пробуем pull с force
                    Log::info("🔄 Пробуем git pull с force...");
                    $process = Process::path($this->basePath)
                        ->env($gitEnv)
                        ->run($gitBaseCmd . ' pull origin ' . escapeshellarg($branch) . ' --no-rebase --force 2>&1');
                }
            }
            
            // Проверяем результат и логируем вывод для диагностики
            if ($process->successful()) {
                $output = $process->output();
                if (!empty(trim($output))) {
                    Log::info('Git reset/pull вывод: ' . substr($output, 0, 500));
                }
            } else {
                Log::error('Не удалось обновить код через все методы', [
                    'error' => $process->errorOutput(),
                    'output' => $process->output(),
                ]);
            }
            
            // Финальная проверка: если ожидался конкретный коммит, проверяем что он установлен
            if ($expectedCommitHash && strlen($expectedCommitHash) === 40) {
                $finalCommit = $this->getCurrentCommitHash();
                if ($finalCommit === $expectedCommitHash) {
                    Log::info("✅ Успешно обновлено до ожидаемого коммита: " . substr($expectedCommitHash, 0, 7));
                } else {
                    Log::warning("⚠️ Обновлено до коммита " . ($finalCommit ? substr($finalCommit, 0, 7) : 'unknown') . ", ожидался " . substr($expectedCommitHash, 0, 7));
                }
            }

            // 3. Получаем новый commit после обновления
            $afterCommit = $this->getCurrentCommitHash();
            Log::info("📦 Commit после обновления: " . ($afterCommit ?: 'не определен'));

            // 4. Проверяем, обновились ли файлы
            if ($beforeCommit && $afterCommit && $beforeCommit !== $afterCommit) {
                Log::info("✅ Код успешно обновлен: {$beforeCommit} -> {$afterCommit}");
            } elseif ($beforeCommit && $afterCommit && $beforeCommit === $afterCommit) {
                Log::info("ℹ️ Код уже актуален, изменений нет");
            }

            if ($process->successful()) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'output' => $process->output(),
                    'had_local_changes' => $hasChanges,
                    'branch' => $branch,
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
                'branch' => $branch,
            ];
        } catch (\Exception $e) {
            Log::error('Исключение в handleGitPull', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Настроить безопасную директорию для git
     * Решает проблему "detected dubious ownership in repository"
     */
    protected function ensureGitSafeDirectory(): void
    {
        try {
            // Сначала пытаемся добавить в глобальную конфигурацию
            // Используем кавычки для экранирования пути с пробелами
            $escapedPath = escapeshellarg($this->basePath);
            $process = Process::path($this->basePath)
                ->run("git config --global --add safe.directory {$escapedPath} 2>&1");

            // Если глобально не получилось, пробуем локально
            if (!$process->successful()) {
                $processLocal = Process::path($this->basePath)
                    ->run("git config --local --add safe.directory {$escapedPath} 2>&1");

                // Если и локально не получилось, используем переменную окружения
                if (!$processLocal->successful()) {
                    // Используем переменную окружения для текущей сессии
                    putenv("GIT_CEILING_DIRECTORIES=" . dirname($this->basePath));
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки настройки - возможно, уже настроено или нет прав
            Log::warning('Не удалось настроить safe.directory для git', [
                'path' => $this->basePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Выполнить composer install
     */
    protected function handleComposerInstall(): array
    {
        try {
            $composerPath = $this->getComposerPath();
            Log::info("🔍 Путь к composer: {$composerPath}");

            // Устанавливаем HOME в домашнюю директорию пользователя проекта для composer
            // Это важно для правильной работы composer на Beget
            $homeDir = dirname(dirname($this->basePath)); // /home/d/dsc23ytp
            Log::info("🔍 HOME директория: {$homeDir}");

            // Формируем команду
            // Если composer в директории проекта - используем его напрямую через PHP
            // Это работает лучше всего на Beget, так как веб-сервер имеет доступ к файлам проекта
            if (!empty($composerPath) && $composerPath !== 'composer' && strpos($composerPath, '/') !== false) {
                $escapedPath = escapeshellarg($composerPath);
                
                // Если composer.phar - используем PHP напрямую
                if (strpos($composerPath, 'composer.phar') !== false) {
                    $command = "{$this->phpPath} {$escapedPath} install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1";
                    Log::info("🔍 Используем PHP для выполнения composer.phar: {$this->phpPath} {$escapedPath}");
                } else {
                    // Для обычного composer скрипта пробуем выполнить через PHP
                    // Сначала пробуем напрямую через PHP
                    $command = "{$this->phpPath} {$escapedPath} install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1";
                    Log::info("🔍 Используем PHP для выполнения composer: {$this->phpPath} {$escapedPath}");
                }
            } else {
                // Если путь не найден, пробуем команду composer (может не сработать из-за прав)
                $command = "composer install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1";
            }
            Log::info("🔍 Команда composer: {$command}");

            // Подготавливаем переменные окружения
            // HOME уже установлен выше в $homeDir
            $env = [
                'HOME' => $homeDir,
                'COMPOSER_HOME' => $homeDir . '/.composer',
                'COMPOSER_DISABLE_XDEBUG_WARN' => '1',
            ];
            $env['COMPOSER_HOME'] = $env['HOME'] . '/.composer';
            
            $process = Process::path($this->basePath)
                ->timeout(600) // 10 минут
                ->env($env)
                ->run($command);

            if ($process->successful()) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'output' => $process->output(),
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить путь к composer
     */
    protected function getComposerPath(): string
    {
        // 1. Проверить явно указанный путь в .env (высший приоритет)
        $composerPath = env('COMPOSER_PATH');
        if ($composerPath && $composerPath !== '' && $composerPath !== 'composer') {
            // Обрезаем пробелы и кавычки, проверяем, что путь не пустой
            $composerPath = trim($composerPath);
            $composerPath = trim($composerPath, '"\'');
            if ($composerPath) {
                // Проверяем, существует ли файл
                try {
                    $testProcess = Process::run("test -f " . escapeshellarg($composerPath) . " && echo 'exists' 2>&1");
                    if ($testProcess->successful() && trim($testProcess->output()) === 'exists') {
                        Log::info("Composer найден по пути из .env: {$composerPath}");
                        return $composerPath;
                    } else {
                        Log::warning("Composer путь из .env не существует: {$composerPath}");
                    }
                } catch (\Exception $e) {
                    Log::warning("Ошибка проверки composer пути из .env: " . $e->getMessage());
                }
            }
        }

        // 2. Проверить локальный composer.phar в директории проекта (приоритет - веб-сервер имеет доступ)
        $localComposerPhar = $this->basePath . '/bin/composer.phar';
        try {
            $testProcess = Process::run("test -f " . escapeshellarg($localComposerPhar) . " && echo 'exists' 2>&1");
            if ($testProcess->successful() && trim($testProcess->output()) === 'exists') {
                Log::info("Composer найден локально в проекте: {$localComposerPhar}");
                return $localComposerPhar;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }
        
        // 3. Проверить также обычный composer (без .phar)
        $localComposer = $this->basePath . '/bin/composer';
        try {
            $testProcess = Process::run("test -f " . escapeshellarg($localComposer) . " && echo 'exists' 2>&1");
            if ($testProcess->successful() && trim($testProcess->output()) === 'exists') {
                Log::info("Composer найден локально в проекте: {$localComposer}");
                return $localComposer;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }

        // 4. Если локального composer нет - попробовать скачать его
        try {
            $binDir = $this->basePath . '/bin';
            if (!is_dir($binDir)) {
                mkdir($binDir, 0755, true);
            }
            
            // Скачиваем composer.phar
            $composerPhar = $binDir . '/composer.phar';
            Log::info("Попытка скачать composer в: {$composerPhar}");
            
            $downloadProcess = Process::path($this->basePath)
                ->run("curl -sS https://getcomposer.org/installer | {$this->phpPath} 2>&1");
            
            if ($downloadProcess->successful()) {
                // Проверяем, был ли создан composer.phar в текущей директории
                $checkPhar = Process::run("test -f " . escapeshellarg($this->basePath . '/composer.phar') . " && echo 'exists' 2>&1");
                if ($checkPhar->successful() && trim($checkPhar->output()) === 'exists') {
                    // Перемещаем в bin/
                    Process::path($this->basePath)
                        ->run("mv composer.phar " . escapeshellarg($composerPhar) . " 2>&1");
                    Log::info("Composer успешно скачан: {$composerPhar}");
                    return $composerPhar;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Не удалось скачать composer: " . $e->getMessage());
        }

        // 5. Попробовать найти composer через which (может не работать через веб-сервер)
        try {
            $whichProcess = Process::run('which composer 2>&1');
            if ($whichProcess->successful()) {
                $foundPath = trim($whichProcess->output());
                if ($foundPath && $foundPath !== 'composer') {
                    Log::info("Composer найден через which: {$foundPath}");
                    return $foundPath;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Ошибка при поиске composer через which: " . $e->getMessage());
        }
        
        // 6. Попробовать найти composer в стандартных местах
        $possiblePaths = [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            '/opt/composer/composer',
        ];

        foreach ($possiblePaths as $path) {
            try {
                $testProcess = Process::run("test -f " . escapeshellarg($path) . " && echo 'exists' 2>&1");
                if ($testProcess->successful() && trim($testProcess->output()) === 'exists') {
                    Log::info("Composer найден по пути: {$path}");
                    return $path;
                }
            } catch (\Exception $e) {
                // Продолжаем проверку других путей
            }
        }

        // 7. Последний fallback - возвращаем пустую строку (будет ошибка при выполнении)
        Log::error("Composer не найден нигде. Установите composer или укажите COMPOSER_PATH в .env");
        return '';
    }

    /**
     * Очистить кеш package discovery
     */
    protected function clearPackageDiscoveryCache(): void
    {
        try {
            $packagesCachePath = $this->basePath . '/bootstrap/cache/packages.php';
            if (file_exists($packagesCachePath)) {
                unlink($packagesCachePath);
                Log::info('Кеш package discovery удален');
            }

            $servicesCachePath = $this->basePath . '/bootstrap/cache/services.php';
            if (file_exists($servicesCachePath)) {
                unlink($servicesCachePath);
                Log::info('Кеш сервис-провайдеров удален');
            }

            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan config:clear");

            if ($process->successful()) {
                Log::info('Кеш конфигурации очищен');
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке кеша package discovery', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Выполнить миграции
     */
    protected function runMigrations(): array
    {
        try {
            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan migrate --force");

            if ($process->successful()) {
                $output = $process->output();
                preg_match_all('/Migrating:\s+(\d{4}_\d{2}_\d{2}_\d{6}_[\w_]+)/', $output, $matches);
                $migrationsRun = count($matches[0]);

                return [
                    'status' => 'success',
                    'migrations_run' => $migrationsRun,
                    'message' => $migrationsRun > 0
                        ? "Выполнено миграций: {$migrationsRun}"
                        : 'Новых миграций не обнаружено',
                    'output' => $output,
                ];
            }

            return [
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Выполнить seeders
     */
    protected function runSeeders(?string $specificSeeder = null, bool $all = false): array
    {
        try {
            // Убеждаемся, что phpPath установлен
            if (!$this->phpPath) {
                $this->phpPath = $this->getPhpPath();
            }

            $seeders = [];
            
            if ($specificSeeder) {
                // Выполняем конкретный seeder
                $seeders = [$specificSeeder];
            } elseif ($all) {
                // Выполняем все seeders (через db:seed без указания класса)
                // В этом случае Laravel выполнит DatabaseSeeder
                $process = Process::path($this->basePath)
                    ->timeout(600) // 10 минут для всех seeders
                    ->run("{$this->phpPath} artisan db:seed --force");

                if ($process->successful()) {
                    Log::info("✅ Все seeders выполнены успешно");
                    return [
                        'status' => 'success',
                        'total' => 1,
                        'success' => 1,
                        'failed' => 0,
                        'results' => ['all' => 'success'],
                        'message' => 'Все seeders выполнены успешно',
                    ];
                } else {
                    $error = $process->errorOutput() ?: $process->output();
                    Log::error("❌ Ошибка выполнения всех seeders", [
                        'error' => $error,
                    ]);
                    return [
                        'status' => 'error',
                        'error' => substr($error, 0, 500),
                    ];
                }
            } else {
                // По умолчанию - список seeders для текущего проекта
                $seeders = [
                    'RoleSeeder',
                ];
            }

            $results = [];
            $totalSuccess = 0;
            $totalFailed = 0;

            foreach ($seeders as $seeder) {
                try {
                    Log::info("Выполнение seeder: {$seeder}");
                    $process = Process::path($this->basePath)
                        ->timeout(300) // 5 минут на каждый seeder
                        ->run("{$this->phpPath} artisan db:seed --class={$seeder} --force");

                    if ($process->successful()) {
                        $results[$seeder] = 'success';
                        $totalSuccess++;
                        Log::info("✅ Seeder выполнен успешно: {$seeder}");
                    } else {
                        $error = $process->errorOutput() ?: $process->output();
                        $results[$seeder] = 'error: ' . substr($error, 0, 200);
                        $totalFailed++;
                        Log::warning("⚠️ Ошибка выполнения seeder: {$seeder}", [
                            'error' => $error,
                        ]);
                    }
                } catch (\Exception $e) {
                    $results[$seeder] = 'exception: ' . $e->getMessage();
                    $totalFailed++;
                    Log::error("❌ Исключение при выполнении seeder: {$seeder}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'status' => $totalFailed === 0 ? 'success' : 'partial',
                'total' => count($seeders),
                'success' => $totalSuccess,
                'failed' => $totalFailed,
                'results' => $results,
                'message' => $totalFailed === 0
                    ? "Все seeders выполнены успешно ({$totalSuccess})"
                    : "Выполнено seeders: {$totalSuccess}, ошибок: {$totalFailed}",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Выполнить seeders через API запрос
     */
    public function seed(Request $request)
    {
        $startTime = microtime(true);
        Log::info('🌱 Начало выполнения seeders', [
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $result = [
            'success' => false,
            'message' => '',
            'data' => [],
        ];

        try {
            // Определяем PHP путь
            $this->phpPath = $this->getPhpPath();
            $this->phpVersion = $this->getPhpVersion();

            Log::info("Используется PHP: {$this->phpPath} (версия: {$this->phpVersion})");

            $class = $request->input('class');
            $all = $request->input('all', false);

            // Выполняем seeders (phpPath уже установлен)
            $seedersResult = $this->runSeeders($class, $all);

            // Формируем ответ
            $result['success'] = $seedersResult['status'] === 'success';
            $result['message'] = $seedersResult['message'] ?? ($seedersResult['error'] ?? 'Unknown error');
            $result['data'] = array_merge($seedersResult, [
                'php_version' => $this->phpVersion,
                'php_path' => $this->phpPath,
                'executed_at' => now()->toDateTimeString(),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
            ]);

            if ($result['success']) {
                Log::info('✅ Seeders успешно выполнены', $result['data']);
            } else {
                Log::warning('⚠️ Seeders выполнены с ошибками', $result['data']);
            }

        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['data']['error'] = $e->getMessage();
            $result['data']['trace'] = config('app.debug') ? $e->getTraceAsString() : null;
            $result['data']['executed_at'] = now()->toDateTimeString();
            $result['data']['duration_seconds'] = round(microtime(true) - $startTime, 2);

            Log::error('❌ Ошибка выполнения seeders', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Очистить временные файлы разработки
     */
    protected function cleanDevelopmentFiles(): void
    {
        try {
            $filesToRemove = [
                'public/hot',
            ];

            foreach ($filesToRemove as $file) {
                $filePath = $this->basePath . '/' . trim($file, '/');

                if (file_exists($filePath)) {
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    } elseif (is_dir($filePath)) {
                        $this->deleteDirectory($filePath);
                    }
                    Log::info("Удален файл разработки: {$file}");
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке файлов разработки', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Рекурсивно удалить директорию
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Очистить все кеши
     */
    protected function clearAllCaches(): array
    {
        // Сначала очищаем отдельные кеши, затем optimize:clear
        // optimize:clear очищает все, но может вызвать проблемы с загрузкой маршрутов
        $commands = [
            'config:clear',
            'cache:clear',
            'route:clear',
            'view:clear',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $process = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan {$command}");

                $results[$command] = $process->successful();
                
                if ($command === 'route:clear') {
                    Log::info("Кеш маршрутов очищен");
                }
            } catch (\Exception $e) {
                $results[$command] = false;
                Log::warning("Ошибка очистки кеша: {$command}", ['error' => $e->getMessage()]);
            }
        }

        // Затем очищаем все через optimize:clear (но это может вызвать проблемы)
        // Поэтому делаем это аккуратно
        try {
            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan optimize:clear");
            
            $results['optimize:clear'] = $process->successful();
            Log::info("Кеш package discovery удален");
            Log::info("Кеш сервис-провайдеров удален");
            Log::info("Кеш конфигурации очищен");
        } catch (\Exception $e) {
            $results['optimize:clear'] = false;
            Log::warning("Ошибка optimize:clear", ['error' => $e->getMessage()]);
        }

        return [
            'success' => !in_array(false, $results, true),
            'details' => $results,
        ];
    }

    /**
     * Оптимизировать приложение
     */
    protected function optimizeApplication(): array
    {
        // Важно: сначала кешируем конфигурацию, затем маршруты
        // Это гарантирует, что маршруты будут загружены правильно
        $commands = [
            'config:cache',
            'route:cache',
            'view:cache',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $process = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan {$command}");

                $success = $process->successful();
                $results[$command] = $success;
                
                if ($command === 'route:cache' && $success) {
                    // Проверяем, что файл маршрутов создан
                    $routesCachePath = $this->basePath . '/bootstrap/cache/routes-v7.php';
                    if (file_exists($routesCachePath)) {
                        Log::info("✅ Файл маршрутов успешно создан: routes-v7.php");
                    } else {
                        Log::warning("⚠️ Файл маршрутов не найден после кеширования: routes-v7.php");
                        $results[$command] = false;
                    }
                }
                
                if (!$success) {
                    $error = $process->errorOutput() ?: $process->output();
                    Log::warning("Ошибка оптимизации: {$command}", ['error' => $error]);
                }
            } catch (\Exception $e) {
                $results[$command] = false;
                Log::warning("Ошибка оптимизации: {$command}", ['error' => $e->getMessage()]);
            }
        }

        return [
            'success' => !in_array(false, $results, true),
            'details' => $results,
        ];
    }

    /**
     * Проверить наличие файлов фронтенда
     */
    protected function checkFrontendFiles(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $manifestExists = file_exists($manifestPath);
        
        $assetsDir = public_path('build/assets');
        $assetsExists = is_dir($assetsDir);
        $assetsCount = 0;
        
        if ($assetsExists) {
            $files = glob($assetsDir . '/*.{js,css}', GLOB_BRACE);
            $assetsCount = $files ? count($files) : 0;
        }
        
        return [
            'manifest_exists' => $manifestExists,
            'assets_dir_exists' => $assetsExists,
            'assets_count' => $assetsCount,
        ];
    }

    /**
     * Получить текущий commit hash
     */
    protected function getCurrentCommitHash(): ?string
    {
        try {
            $safeDirectoryPath = escapeshellarg($this->basePath);
            $process = Process::path($this->basePath)
                ->env([
                    'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                ])
                ->run("git -c safe.directory={$safeDirectoryPath} rev-parse HEAD 2>&1");

            if ($process->successful()) {
                $hash = trim($process->output());
                if (!empty($hash) && strlen($hash) === 40) {
                    return $hash;
                }
            } else {
                Log::warning('Не удалось получить commit hash', [
                    'output' => $process->output(),
                    'error' => $process->errorOutput(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при получении commit hash', [
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }
}
