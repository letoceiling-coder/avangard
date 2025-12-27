<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        // Всегда используем собранные файлы (без dev сервера)
        $indexHtmlPath = public_path('frontend/index.html');
        $assetsPath = public_path('frontend/assets');
        $cssFiles = [];
        $jsFiles = [];
        
        // Пытаемся найти файлы через index.html или через поиск в assets
        if (file_exists($indexHtmlPath)) {
            $htmlContent = file_get_contents($indexHtmlPath);
            
            // Извлекаем пути к CSS файлам
            preg_match_all('/<link[^>]*href=["\']([^"\']*\.css[^"\']*)["\'][^>]*>/i', $htmlContent, $cssMatches);
            if (!empty($cssMatches[1])) {
                foreach ($cssMatches[1] as $cssPath) {
                    // Пути после сборки с base: '/frontend/' будут /frontend/assets/...
                    // Оставляем путь как есть, asset() обработает его правильно
                    $cssFiles[] = $cssPath;
                }
            }
            
            // Извлекаем пути к JS файлам
            preg_match_all('/<script[^>]*src=["\']([^"\']*\.js[^"\']*)["\'][^>]*>/i', $htmlContent, $jsMatches);
            if (!empty($jsMatches[1])) {
                foreach ($jsMatches[1] as $jsPath) {
                    // Пути после сборки с base: '/frontend/' будут /frontend/assets/...
                    // Оставляем путь как есть, asset() обработает его правильно
                    $jsFiles[] = $jsPath;
                }
            }
            
            // Извлекаем title и meta из index.html
            preg_match('/<title>([^<]*)<\/title>/i', $htmlContent, $titleMatch);
            $pageTitle = !empty($titleMatch[1]) ? $titleMatch[1] : 'LiveGrid — Поиск недвижимости';
            
            preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $htmlContent, $descMatch);
            $pageDescription = !empty($descMatch[1]) ? $descMatch[1] : 'LiveGrid — современный агрегатор недвижимости. Новостройки, квартиры, дома в Белгороде, Краснодаре, Ростове. Шахматка квартир, актуальные цены.';
        } else {
            $pageTitle = 'LiveGrid — Поиск недвижимости | Квартиры, новостройки, дома';
            $pageDescription = 'LiveGrid — современный агрегатор недвижимости. Новостройки, квартиры, дома в Белгороде, Краснодаре, Ростове. Шахматка квартир, актуальные цены.';
        }
        
        // Если не нашли через index.html, ищем файлы по паттерну
        if (empty($jsFiles) && is_dir($assetsPath)) {
            $files = glob($assetsPath . '/index-*.js');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $jsFiles[] = '/frontend/assets/' . basename($file);
                }
            }
        }
        
        if (empty($cssFiles) && is_dir($assetsPath)) {
            $files = glob($assetsPath . '/index-*.css');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $cssFiles[] = '/frontend/assets/' . basename($file);
                }
            }
        }
    @endphp
    
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="author" content="LiveGrid">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        #root {
            width: 100%;
            height: 100%;
        }
        
        /* Loading state */
        #root:empty::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            margin: -20px 0 0 -20px;
            border: 3px solid #e0e0e0;
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
    @if(!empty($jsFiles))
        <!-- Подключение собранных файлов React -->
        {{-- Подключаем CSS файлы --}}
        @foreach($cssFiles as $css)
            @if(str_starts_with($css, 'http://') || str_starts_with($css, 'https://'))
                {{-- Внешние URL --}}
                <link rel="stylesheet" href="{{ $css }}">
            @elseif(str_starts_with($css, '/assets/'))
                {{-- Пути /assets/... будут проксироваться через Laravel маршрут --}}
                <link rel="stylesheet" href="{{ $css }}">
            @elseif(str_starts_with($css, '/'))
                {{-- Абсолютные пути --}}
                <link rel="stylesheet" href="{{ $css }}">
            @else
                {{-- Относительные пути --}}
                <link rel="stylesheet" href="{{ asset($css) }}">
            @endif
        @endforeach
        
        {{-- Подключаем JS файлы --}}
        @foreach($jsFiles as $js)
            @if(str_starts_with($js, 'http://') || str_starts_with($js, 'https://'))
                {{-- Внешние URL --}}
                <script type="module" src="{{ $js }}"></script>
            @elseif(str_starts_with($js, '/assets/'))
                {{-- Пути /assets/... будут проксироваться через Laravel маршрут --}}
                <script type="module" src="{{ $js }}"></script>
            @elseif(str_starts_with($js, '/'))
                {{-- Абсолютные пути --}}
                <script type="module" src="{{ $js }}"></script>
            @else
                {{-- Относительные пути --}}
                <script type="module" src="{{ asset($js) }}"></script>
            @endif
        @endforeach
    @else
        <!-- React приложение не собрано. Выполните сборку: npm run build:react -->
        <div style="padding: 20px; text-align: center; font-family: Arial;">
            <h2>React приложение не собрано</h2>
            <p>Выполните сборку:</p>
            <pre style="background: #f5f5f5; padding: 10px; display: inline-block;">npm run build:react</pre>
        </div>
        <script>
            console.error('React приложение не собрано. Выполните: npm run build:react');
        </script>
    @endif
</head>
<body>
    <div id="root"></div>
</body>
</html>
