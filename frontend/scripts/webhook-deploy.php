<?php
/**
 * Webhook для автоматического деплоя
 * Настройте этот URL в GitHub/GitLab как webhook
 * 
 * Безопасность:
 * 1. Добавьте секретный ключ в настройках webhook
 * 2. Проверьте IP адреса GitHub/GitLab
 * 3. Используйте HTTPS
 */

// Конфигурация
$SECRET = 'your-secret-key-here'; // Измените на свой секретный ключ
$PROJECT_DIR = '/home/user/trendagent.siteaccess.ru/public_html/avangard-react';
$BRANCH = 'main';
$LOG_FILE = '/home/user/deploy.log';

// Логирование
function logMessage($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Проверка секретного ключа
$headers = getallheaders();
$signature = $headers['X-Hub-Signature-256'] ?? '';

if (empty($signature)) {
    http_response_code(401);
    logMessage('ERROR: Missing signature');
    die('Unauthorized');
}

$payload = file_get_contents('php://input');
$expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $SECRET);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    logMessage('ERROR: Invalid signature');
    die('Unauthorized');
}

// Проверка ветки
$data = json_decode($payload, true);
$ref = $data['ref'] ?? '';

if ($ref !== "refs/heads/{$BRANCH}") {
    http_response_code(200);
    logMessage("INFO: Ignoring branch $ref");
    die('Branch ignored');
}

// Выполнение деплоя
logMessage('INFO: Starting deployment...');

$output = [];
$returnCode = 0;

$commands = [
    "cd $PROJECT_DIR",
    "git fetch origin",
    "git checkout $BRANCH",
    "git pull origin $BRANCH",
    "npm ci",
    "npm run build",
    "cp -r dist/. .."
];

$command = implode(' && ', $commands);
exec("$command 2>&1", $output, $returnCode);

if ($returnCode === 0) {
    logMessage('SUCCESS: Deployment completed');
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Deployment completed']);
} else {
    logMessage('ERROR: Deployment failed - ' . implode("\n", $output));
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Deployment failed']);
}

