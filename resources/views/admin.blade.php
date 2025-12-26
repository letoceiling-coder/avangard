<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Админ панель</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    <script>
        // Применяем тему до загрузки страницы, чтобы избежать мигания
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            if (theme === 'dark') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                html.style.colorScheme = 'dark';
            } else {
                html.style.colorScheme = 'light';
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-background text-foreground">
    <div id="admin-app"></div>
</body>
</html>

