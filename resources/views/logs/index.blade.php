<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Просмотр логов - WOW Spin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        h1 {
            color: #fff;
            font-size: 24px;
        }

        .controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .info-panel {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
        }

        .info-item {
            display: flex;
            gap: 5px;
        }

        .info-label {
            color: #9ca3af;
        }

        .info-value {
            color: #fff;
            font-weight: 500;
        }

        .file-selector {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #404040;
            background: #1a1a1a;
            color: #e0e0e0;
            font-size: 14px;
            min-width: 200px;
        }

        .log-container {
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 8px;
            overflow: hidden;
        }

        .log-header {
            background: #2a2a2a;
            padding: 15px;
            border-bottom: 1px solid #404040;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .log-content {
            background: #0d1117;
            padding: 20px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            max-height: 70vh;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .log-content::-webkit-scrollbar {
            width: 10px;
        }

        .log-content::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        .log-content::-webkit-scrollbar-thumb {
            background: #404040;
            border-radius: 5px;
        }

        .log-content::-webkit-scrollbar-thumb:hover {
            background: #505050;
        }

        .log-line {
            margin-bottom: 2px;
        }

        .log-line.error {
            color: #f87171;
        }

        .log-line.warning {
            color: #fbbf24;
        }

        .log-line.info {
            color: #60a5fa;
        }

        .log-line.debug {
            color: #9ca3af;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        .error-message {
            background: #7f1d1d;
            color: #fca5a5;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #14532d;
            color: #86efac;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .empty-logs {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #404040;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Просмотр логов</h1>
            <div class="controls">
                <div class="file-selector">
                    <select id="logFileSelect">
                        <option value="laravel.log">Загрузка...</option>
                    </select>
                    <input type="number" id="linesInput" value="500" min="100" max="5000" step="100" 
                           style="padding: 8px 12px; border-radius: 6px; border: 1px solid #404040; background: #1a1a1a; color: #e0e0e0; width: 100px; font-size: 14px;">
                    <span style="color: #9ca3af; font-size: 14px;">строк</span>
                </div>
                <button class="btn btn-primary" id="refreshBtn" onclick="loadLogs()">
                    🔄 Обновить
                </button>
                <button class="btn btn-danger" id="clearBtn" onclick="clearLogs()">
                    🗑️ Очистить логи
                </button>
            </div>
        </div>

        <div id="messageContainer"></div>

        <div class="info-panel" id="infoPanel" style="display: none;">
            <div class="info-item">
                <span class="info-label">Файл:</span>
                <span class="info-value" id="currentFile">-</span>
            </div>
            <div class="info-item">
                <span class="info-label">Размер:</span>
                <span class="info-value" id="fileSize">-</span>
            </div>
            <div class="info-item">
                <span class="info-label">Строк:</span>
                <span class="info-value" id="linesCount">-</span>
            </div>
            <div class="info-item">
                <span class="info-label">Обновлено:</span>
                <span class="info-value" id="lastUpdate">-</span>
            </div>
        </div>

        <div class="log-container">
            <div class="log-header">
                <span style="color: #9ca3af; font-size: 14px;">Содержимое лог-файла</span>
            </div>
            <div class="log-content" id="logContent">
                <div class="loading">
                    <span class="spinner"></span> Загрузка логов...
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentLogFile = 'laravel.log';
        let isLoading = false;

        // Загрузить список файлов при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadLogFiles();
            loadLogs();
            
            // Автообновление каждые 30 секунд
            setInterval(loadLogs, 30000);
        });

        // Загрузить список лог-файлов
        async function loadLogFiles() {
            try {
                const response = await fetch('/api/logs/files');
                const data = await response.json();
                
                if (data.files && data.files.length > 0) {
                    const select = document.getElementById('logFileSelect');
                    select.innerHTML = '';
                    
                    data.files.forEach(file => {
                        const option = document.createElement('option');
                        option.value = file.name;
                        option.textContent = `${file.name} (${file.size_formatted})`;
                        if (file.name === currentLogFile) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading log files:', error);
            }
        }

        // Обработчик изменения файла
        document.getElementById('logFileSelect').addEventListener('change', function() {
            currentLogFile = this.value;
            loadLogs();
        });

        // Загрузить логи
        async function loadLogs() {
            if (isLoading) return;
            
            isLoading = true;
            const refreshBtn = document.getElementById('refreshBtn');
            const logContent = document.getElementById('logContent');
            const infoPanel = document.getElementById('infoPanel');
            
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<span class="spinner"></span> Загрузка...';
            
            logContent.innerHTML = '<div class="loading"><span class="spinner"></span> Загрузка логов...</div>';
            infoPanel.style.display = 'none';
            hideMessage();

            try {
                const lines = document.getElementById('linesInput').value || 500;
                const response = await fetch(`/api/logs?file=${encodeURIComponent(currentLogFile)}&lines=${lines}`);
                const data = await response.json();

                if (data.error) {
                    showMessage(data.error, 'error');
                    logContent.innerHTML = `<div class="empty-logs">Ошибка: ${data.error}</div>`;
                } else {
                    // Обновляем информацию
                    document.getElementById('currentFile').textContent = data.file;
                    document.getElementById('fileSize').textContent = data.file_size_formatted;
                    document.getElementById('linesCount').textContent = data.lines_count;
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleString('ru-RU');
                    infoPanel.style.display = 'flex';

                    // Форматируем и отображаем логи
                    if (data.content && data.content.trim()) {
                        const formattedContent = formatLogContent(data.content);
                        logContent.innerHTML = formattedContent;
                    } else {
                        logContent.innerHTML = '<div class="empty-logs">Лог-файл пуст</div>';
                    }
                }
            } catch (error) {
                showMessage('Ошибка загрузки логов: ' + error.message, 'error');
                logContent.innerHTML = `<div class="empty-logs">Ошибка загрузки: ${error.message}</div>`;
            } finally {
                isLoading = false;
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '🔄 Обновить';
            }
        }

        // Форматировать содержимое логов
        function formatLogContent(content) {
            const lines = content.split('\n');
            return lines.map(line => {
                let className = '';
                if (line.includes('[ERROR]') || line.includes('ERROR') || line.includes('Exception')) {
                    className = 'error';
                } else if (line.includes('[WARNING]') || line.includes('WARNING')) {
                    className = 'warning';
                } else if (line.includes('[INFO]') || line.includes('INFO')) {
                    className = 'info';
                } else if (line.includes('[DEBUG]') || line.includes('DEBUG')) {
                    className = 'debug';
                }
                
                return `<div class="log-line ${className}">${escapeHtml(line)}</div>`;
            }).join('');
        }

        // Экранировать HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Очистить логи
        async function clearLogs() {
            if (!confirm('Вы уверены, что хотите очистить лог-файл? Это действие нельзя отменить.')) {
                return;
            }

            const clearBtn = document.getElementById('clearBtn');
            clearBtn.disabled = true;
            clearBtn.innerHTML = '<span class="spinner"></span> Очистка...';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch('/api/logs/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file: currentLogFile
                    })
                });

                const data = await response.json();

                if (data.error) {
                    showMessage(data.error, 'error');
                } else {
                    showMessage('Лог-файл успешно очищен', 'success');
                    loadLogs();
                    loadLogFiles();
                }
            } catch (error) {
                showMessage('Ошибка очистки логов: ' + error.message, 'error');
            } finally {
                clearBtn.disabled = false;
                clearBtn.innerHTML = '🗑️ Очистить логи';
            }
        }

        // Показать сообщение
        function showMessage(message, type = 'error') {
            const container = document.getElementById('messageContainer');
            const className = type === 'error' ? 'error-message' : 'success-message';
            container.innerHTML = `<div class="${className}">${escapeHtml(message)}</div>`;
            
            if (type === 'success') {
                setTimeout(hideMessage, 5000);
            }
        }

        // Скрыть сообщение
        function hideMessage() {
            const container = document.getElementById('messageContainer');
            container.innerHTML = '';
        }
    </script>
</body>
</html>

