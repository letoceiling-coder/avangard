<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подписка истекла</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 28px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .expires-at {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #e74c3c;
        }
        .expires-at strong {
            color: #333;
            display: block;
            margin-bottom: 8px;
        }
        .expires-at .date {
            color: #666;
            font-size: 16px;
        }
        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .contact-info p {
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .status-expired {
            background: #fee;
            color: #c33;
        }
        .status-suspended {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚫 Доступ ограничен</h1>
        
        @if(isset($subscription['status']))
            <div class="status-badge status-{{ $subscription['status'] }}">
                @if($subscription['status'] === 'expired')
                    Подписка истекла
                @elseif($subscription['status'] === 'suspended')
                    Подписка приостановлена
                @else
                    Подписка неактивна
                @endif
            </div>
        @endif
        
        <p><strong>Ваша подписка истекла или неактивна</strong></p>
        <p>Для продолжения работы с админ-панелью необходимо продлить подписку.</p>
        
        @if(isset($subscription['expires_at']))
        <div class="expires-at">
            <strong>Дата окончания:</strong>
            <span class="date">
                {{ \Carbon\Carbon::parse($subscription['expires_at'])->format('d.m.Y H:i') }}
            </span>
        </div>
        @endif
        
        <div class="contact-info">
            <p>Для продления подписки свяжитесь с администратором системы.</p>
            <p><strong>Email:</strong> admin@siteaccess.ru</p>
            <p><strong>Сайт:</strong> <a href="https://crm.siteaccess.ru" target="_blank" style="color: #667eea;">crm.siteaccess.ru</a></p>
        </div>
    </div>
</body>
</html>

