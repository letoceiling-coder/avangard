import axios from 'axios';

/**
 * ⚠️ ВАЖНО: Правила использования API роутов в Vue компонентах
 * 
 * API_BASE уже содержит '/api/v1', поэтому в компонентах НЕ нужно добавлять '/v1/' или '/api/v1/'
 * 
 * ✅ ПРАВИЛЬНО:
 *   apiGet('/bots')           → /api/v1/bots
 *   apiGet('/users')          → /api/v1/users
 *   apiGet('/roles')          → /api/v1/roles
 *   apiGet('/bots/1')         → /api/v1/bots/1
 *   apiPost('/bots', data)    → /api/v1/bots
 * 
 * ❌ НЕПРАВИЛЬНО:
 *   apiGet('/v1/bots')       → /api/v1/v1/bots (ОШИБКА!)
 *   apiGet('/api/v1/bots')    → /api/v1/api/v1/bots (ОШИБКА!)
 *   apiGet('v1/bots')         → /api/v1v1/bots (ОШИБКА!)
 * 
 * Структура роутов в routes/api.php:
 *   Route::prefix('v1')->group(function () {
 *       Route::apiResource('bots', BotController::class);
 *       // Полный путь: /api/v1/bots
 *   });
 * 
 * Всегда начинайте путь с '/' и БЕЗ '/v1/'!
 */
const API_BASE = '/api/v1';

// Получить заголовки авторизации
const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    return headers;
};

// GET запрос
export const apiGet = async (url, params = {}) => {
    let fullUrl = `${API_BASE}${url}`;
    
    // Если params - объект и не пустой, добавляем параметры
    if (params && Object.keys(params).length > 0) {
        const queryString = new URLSearchParams(params).toString();
        // Если url уже содержит параметры, добавляем через &
        if (url.includes('?')) {
            fullUrl = `${fullUrl}&${queryString}`;
        } else {
            fullUrl = `${fullUrl}?${queryString}`;
        }
    }
    
    return fetch(fullUrl, {
        method: 'GET',
        headers: getAuthHeaders(),
    });
};

// POST запрос
export const apiPost = async (url, data = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    
    // Если data - FormData, не устанавливаем Content-Type
    const headers = data instanceof FormData 
        ? { ...getAuthHeaders(), 'Content-Type': undefined }
        : getAuthHeaders();
    
    // Удаляем Content-Type если это FormData (браузер установит сам)
    if (data instanceof FormData) {
        delete headers['Content-Type'];
    }
    
    return fetch(fullUrl, {
        method: 'POST',
        headers,
        body: data instanceof FormData ? data : JSON.stringify(data),
    });
};

// PUT запрос
export const apiPut = async (url, data = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    
    // Если data - FormData, не устанавливаем Content-Type
    const headers = data instanceof FormData 
        ? { ...getAuthHeaders(), 'Content-Type': undefined }
        : getAuthHeaders();
    
    // Удаляем Content-Type если это FormData (браузер установит сам)
    if (data instanceof FormData) {
        delete headers['Content-Type'];
    }
    
    return fetch(fullUrl, {
        method: 'PUT',
        headers,
        body: data instanceof FormData ? data : JSON.stringify(data),
    });
};

// DELETE запрос
export const apiDelete = async (url) => {
    const fullUrl = `${API_BASE}${url}`;
    
    return fetch(fullUrl, {
        method: 'DELETE',
        headers: getAuthHeaders(),
    });
};

