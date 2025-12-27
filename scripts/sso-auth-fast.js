import puppeteer from 'puppeteer';

const phone = process.argv[2];
const password = process.argv[3];
const loginUrl = process.argv[4] || 'https://sso.trend.tech/login?return_oauth_url=https%3A%2F%2Ftrendagent.ru%2Foauth&return_url=https%3A%2F%2Ftrendagent.ru%2F&app_id=66d84f584c0168b8ccd281c3';

(async () => {
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-blink-features=AutomationControlled']
    });
    
    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });
        
        // Перехватываем сетевые запросы для получения токена
        let foundToken = null;
        let tokenFound = false;
        
        page.on('response', async (response) => {
            const url = response.url();
            // Ищем токен в URL ответов
            if (url.includes('oauth') || url.includes('token') || url.includes('trendagent.ru')) {
                try {
                    const responseUrl = new URL(url);
                    const token = responseUrl.searchParams.get('auth_token') || 
                                 responseUrl.searchParams.get('access_token');
                    if (token && !tokenFound) {
                        tokenFound = true;
                        foundToken = token;
                        console.error('Токен найден в сетевом запросе:', url.substring(0, 100));
                    }
                } catch (e) {
                    // Игнорируем ошибки
                }
            }
        });
        
        // Переходим на страницу логина
        console.error('[1] Переход на страницу логина...');
        await page.goto(loginUrl, {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });
        
        // Минимальное ожидание для рендеринга
        await page.waitForTimeout(2000);
        
        // Ищем поле телефона - быстрый поиск
        console.error('[2] Поиск поля телефона...');
        let phoneInput = null;
        const phoneSelectors = ['input[type="tel"]', 'input[autocomplete="tel"]', 'input[type="text"]'];
        
        for (const selector of phoneSelectors) {
            try {
                const elements = await page.$$(selector);
                for (const el of elements) {
                    const isVisible = await el.evaluate(e => e.offsetWidth > 0 && e.offsetHeight > 0);
                    if (isVisible) {
                        phoneInput = el;
                        break;
                    }
                }
                if (phoneInput) break;
            } catch (e) {}
        }
        
        if (!phoneInput) {
            throw new Error('Поле телефона не найдено');
        }
        
        // Вводим данные быстро
        console.error('[3] Ввод данных...');
        await phoneInput.click();
        await phoneInput.type(phone, { delay: 30 });
        await page.waitForTimeout(300);
        
        const passwordInput = await page.$('input[type="password"]');
        if (!passwordInput) {
            throw new Error('Поле пароля не найдено');
        }
        
        await passwordInput.click();
        await passwordInput.type(password, { delay: 30 });
        await page.waitForTimeout(300);
        
        // Ищем кнопку отправки
        const submitButton = await page.$('button[type="submit"]') || await page.$('button');
        if (!submitButton) {
            throw new Error('Кнопка отправки не найдена');
        }
        
        const initialUrl = page.url();
        console.error('[4] Отправка формы, начальный URL:', initialUrl);
        
        // Слушаем изменения URL для быстрого извлечения токена
        let navigationUrl = null;
        const urlHandler = () => {
            navigationUrl = page.url();
            console.error('[5] URL изменился:', navigationUrl);
            
            // Пробуем извлечь токен сразу
            try {
                const urlParams = new URLSearchParams(new URL(navigationUrl).search);
                const token = urlParams.get('auth_token') || urlParams.get('access_token');
                if (token && !tokenFound) {
                    tokenFound = true;
                    foundToken = token;
                    console.error('[6] Токен найден в URL!');
                }
            } catch (e) {
                // Игнорируем ошибки
            }
        };
        
        page.on('framenavigated', urlHandler);
        
        // Кликаем и ждем навигации с коротким таймаутом
        console.error('[4] Нажатие кнопки отправки...');
        const clickPromise = submitButton.click();
        const navigationPromise = page.waitForNavigation({ 
            waitUntil: 'domcontentloaded',
            timeout: 15000 // 15 секунд максимум
        });
        
        // Ограничиваем время ожидания 20 секундами
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => resolve('timeout'), 20000);
        });
        
        const result = await Promise.race([
            Promise.all([clickPromise, navigationPromise]).then(() => 'navigation'),
            timeoutPromise
        ]);
        
        page.off('framenavigated', urlHandler);
        
        if (result === 'timeout') {
            console.error('[5] Таймаут навигации, продолжаем...');
        } else {
            console.error('[5] Навигация завершена');
        }
        
        // Минимальное ожидание - только для стабилизации
        await page.waitForTimeout(1000);
        
        // Получаем финальный URL
        const finalUrl = page.url();
        console.error('[6] Финальный URL:', finalUrl);
        
        // Если токен уже найден, используем его
        if (tokenFound && foundToken) {
            console.error('[7] Используем токен, найденный во время навигации');
            const cookies = await page.cookies();
            
            const result = {
                success: true,
                cookies: cookies.map(c => ({
                    name: c.name,
                    value: c.value,
                    domain: c.domain,
                    path: c.path,
                    expires: c.expires,
                    httpOnly: c.httpOnly,
                    secure: c.secure
                })),
                tokens: {
                    access_token: foundToken,
                    auth_token: foundToken
                },
                currentUrl: finalUrl,
                initialUrl: initialUrl,
                urlChanged: finalUrl !== initialUrl,
                sessionId: cookies.find(c => c.name.toLowerCase().includes('session'))?.value || null
            };
            
            console.log(JSON.stringify(result));
            await browser.close();
            return;
        }
        
        // Если токен не найден, пробуем извлечь из финального URL
        const cookies = await page.cookies();
        const tokens = {};
        
        try {
            const urlParams = new URLSearchParams(new URL(finalUrl).search);
            const token = urlParams.get('auth_token') || urlParams.get('access_token');
            if (token) {
                tokens.access_token = token;
                tokens.auth_token = token;
                console.error('[7] Токен найден в финальном URL');
            }
        } catch (e) {
            console.error('[7] Ошибка парсинга URL:', e.message);
        }
        
        // Пробуем извлечь токен из cookies
        if (!tokens.access_token) {
            for (const cookie of cookies) {
                if (cookie.name.toLowerCase().includes('token') || cookie.name.toLowerCase().includes('auth')) {
                    tokens.access_token = cookie.value;
                    tokens.auth_token = cookie.value;
                    console.error('[7] Токен найден в cookie:', cookie.name);
                    break;
                }
            }
        }
        
        // Формируем результат
        const resultData = {
            success: true,
            cookies: cookies.map(c => ({
                name: c.name,
                value: c.value,
                domain: c.domain,
                path: c.path,
                expires: c.expires,
                httpOnly: c.httpOnly,
                secure: c.secure
            })),
            tokens: tokens,
            currentUrl: finalUrl,
            initialUrl: initialUrl,
            urlChanged: finalUrl !== initialUrl,
            sessionId: cookies.find(c => c.name.toLowerCase().includes('session'))?.value || null
        };
        
        console.log(JSON.stringify(resultData));
        
    } catch (error) {
        const errorResult = {
            success: false,
            error: error.message,
            stack: error.stack
        };
        console.log(JSON.stringify(errorResult));
        process.exit(1);
    } finally {
        await browser.close();
    }
})();


