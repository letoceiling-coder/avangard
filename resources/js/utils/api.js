import axios from 'axios';

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

