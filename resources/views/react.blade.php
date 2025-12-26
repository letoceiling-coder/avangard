<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Spin & Win — Крути колесо фортуны и выигрывай призы</title>
    
    <!-- Telegram Mini App SDK - loaded first for fastest initialization -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    
    <!-- Theme colors matching app design -->
    <meta name="theme-color" content="#F8A575" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="format-detection" content="telephone=no" />
    
    <!-- Prevent text size adjustment on orientation change -->
    <meta name="HandheldFriendly" content="true" />
    
    <!-- SEO Meta -->
    <meta name="description" content="Telegram Mini App с колесом фортуны. Крутите колесо, выигрывайте денежные призы от 300 до 1500 ₽. Приглашайте друзей и получайте бесплатные билеты!" />
    <meta name="author" content="Spin & Win" />
    <meta name="robots" content="index, follow" />

    <!-- Open Graph -->
    <meta property="og:title" content="Spin & Win — Колесо фортуны в Telegram" />
    <meta property="og:description" content="Крутите колесо, выигрывайте призы до 1500 ₽. Получайте бесплатные билеты каждый день!" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://lovable.dev/opengraph-image-p98pqg.png" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Spin & Win" />
    <meta name="twitter:description" content="Крути колесо фортуны и выигрывай!" />
    <meta name="twitter:image" content="https://lovable.dev/opengraph-image-p98pqg.png" />
    
    <style>
      /* Critical CSS - inline for fastest first paint */
      :root {
        --tg-viewport-height: 100vh;
        --tg-viewport-stable-height: 100vh;
      }
      
      /* Prevent overscroll bounce on iOS */
      html, body {
        overscroll-behavior: none;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y;
        overflow: hidden;
        height: 100%;
        height: var(--tg-viewport-height, 100vh);
      }
      
      body {
        margin: 0;
        padding: 0;
        background: linear-gradient(180deg, #F8A575 0%, #FDB083 100%);
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }
      
      #root {
        width: 100%;
        height: 100%;
        height: var(--tg-viewport-height, 100vh);
        overflow: hidden;
      }
      
      /* Telegram WebView optimizations */
      * {
        -webkit-tap-highlight-color: transparent;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
        box-sizing: border-box;
      }
      
      /* Allow text selection in inputs */
      input, textarea {
        -webkit-user-select: text;
        user-select: text;
      }
      
      /* GPU acceleration hints */
      .wheel-rotatable {
        will-change: transform;
        transform: translateZ(0);
        backface-visibility: hidden;
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
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
      }
      
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
    </style>
    
    <script>
      // Initialize Telegram WebApp viewport height
      (function() {
        function setViewportHeight() {
          const vh = window.innerHeight * 0.01;
          document.documentElement.style.setProperty('--vh', `${vh}px`);
          
          // Telegram WebApp viewport
          if (window.Telegram?.WebApp) {
            const tgVh = window.Telegram.WebApp.viewportHeight || window.innerHeight;
            const tgStableVh = window.Telegram.WebApp.viewportStableHeight || window.innerHeight;
            document.documentElement.style.setProperty('--tg-viewport-height', `${tgVh}px`);
            document.documentElement.style.setProperty('--tg-viewport-stable-height', `${tgStableVh}px`);
          }
        }
        
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        
        // Telegram WebApp events
        if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.onEvent('viewportChanged', setViewportHeight);
          window.Telegram.WebApp.ready();
          window.Telegram.WebApp.expand();
        }
      })();
    </script>
    
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
        }
        
        // Если не нашли через index.html, ищем файлы по паттерну
        if (empty($jsFiles) && is_dir($assetsPath)) {
            $files = glob($assetsPath . '/index-*.js');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $jsFiles[] = 'frontend/assets/' . basename($file);
                }
            }
        }
        
        if (empty($cssFiles) && is_dir($assetsPath)) {
            $files = glob($assetsPath . '/index-*.css');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $cssFiles[] = 'frontend/assets/' . basename($file);
                }
            }
        }
    @endphp
    
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

