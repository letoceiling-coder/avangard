import './bootstrap';
import { createApp } from 'vue';
import { createStore } from 'vuex';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';

// Store
const store = createStore({
    state: {
        user: null,
        token: localStorage.getItem('token') || null,
        menu: [],
        notifications: [],
        theme: localStorage.getItem('theme') || 'light',
    },
    mutations: {
        SET_USER(state, user) {
            console.log('🔍 SET_USER mutation - Setting user:', {
                user,
                roles: user?.roles,
                rolesCount: user?.roles?.length || 0,
            });
            state.user = user;
            console.log('✅ SET_USER mutation - User set:', {
                user: state.user,
                roles: state.user?.roles,
                rolesCount: state.user?.roles?.length || 0,
            });
        },
        SET_TOKEN(state, token) {
            state.token = token;
            if (token) {
                localStorage.setItem('token', token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            } else {
                localStorage.removeItem('token');
                delete axios.defaults.headers.common['Authorization'];
            }
        },
        SET_MENU(state, menu) {
            state.menu = menu;
        },
        SET_NOTIFICATIONS(state, notifications) {
            state.notifications = notifications;
        },
        LOGOUT(state) {
            state.user = null;
            state.token = null;
            state.menu = [];
            state.notifications = [];
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        },
        SET_THEME(state, theme) {
            state.theme = theme;
            localStorage.setItem('theme', theme);
            // Применяем тему к документу
            const html = document.documentElement;
            const body = document.body;
            if (theme === 'dark') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                if (body) body.classList.add('dark');
                html.style.colorScheme = 'dark';
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
                if (body) body.classList.remove('dark');
                html.style.colorScheme = 'light';
            }
        },
    },
    actions: {
        async login({ commit, dispatch }, credentials) {
            try {
                const response = await axios.post('/api/auth/login', credentials);
                commit('SET_TOKEN', response.data.token);
                commit('SET_USER', response.data.user);
                // Загружаем меню после успешной авторизации
                await dispatch('fetchMenu');
                await dispatch('fetchNotifications');
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message || 'Ошибка авторизации' };
            }
        },
        async register({ commit, dispatch }, userData) {
            try {
                const response = await axios.post('/api/auth/register', userData);
                commit('SET_TOKEN', response.data.token);
                commit('SET_USER', response.data.user);
                // Загружаем меню после успешной регистрации
                await dispatch('fetchMenu');
                await dispatch('fetchNotifications');
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message || 'Ошибка регистрации' };
            }
        },
        async logout({ commit }) {
            try {
                await axios.post('/api/auth/logout');
            } catch (error) {
                console.error('Logout error:', error);
            }
            commit('LOGOUT');
        },
        async fetchUser({ commit, state }) {
            if (!state.token) return;
            try {
                const response = await axios.get('/api/auth/user');
                console.log('🔍 fetchUser - Response:', {
                    user: response.data.user,
                    roles: response.data.user?.roles,
                    rolesCount: response.data.user?.roles?.length || 0,
                });
                commit('SET_USER', response.data.user);
                console.log('✅ fetchUser - User set in store:', {
                    user: state.user,
                    roles: state.user?.roles,
                });
            } catch (error) {
                console.error('❌ fetchUser - Error:', error);
                commit('LOGOUT');
            }
        },
        async fetchMenu({ commit, state }) {
            if (!state.token) return;
            try {
                const response = await axios.get('/api/admin/menu');
                // Используем JSON для правильного логирования реактивных объектов
                console.log('Menu loaded:', JSON.parse(JSON.stringify(response.data.menu)));
                commit('SET_MENU', response.data.menu);
            } catch (error) {
                console.error('Menu fetch error:', error);
            }
        },
        async fetchNotifications({ commit, state }) {
            if (!state.token) return;
            try {
                const response = await axios.get('/api/notifications');
                commit('SET_NOTIFICATIONS', response.data.notifications);
            } catch (error) {
                console.error('Notifications fetch error:', error);
            }
        },
        toggleTheme({ commit, state }) {
            const newTheme = state.theme === 'dark' ? 'light' : 'dark';
            commit('SET_THEME', newTheme);
        },
    },
    getters: {
        isAuthenticated: (state) => !!state.token,
        user: (state) => state.user,
        menu: (state) => state.menu,
        notifications: (state) => state.notifications,
        theme: (state) => state.theme,
        isDarkMode: (state) => state.theme === 'dark',
        unreadNotificationsCount: (state) => {
            return state.notifications.filter(n => !n.read).length;
        },
        hasRole: (state) => (roleSlug) => {
            if (!state.user || !state.user.roles) return false;
            return state.user.roles.some(role => role.slug === roleSlug);
        },
        hasAnyRole: (state) => (roleSlugs) => {
            if (!state.user || !state.user.roles) return false;
            return state.user.roles.some(role => roleSlugs.includes(role.slug));
        },
        isAdmin: (state) => {
            if (!state.user || !state.user.roles) return false;
            return state.user.roles.some(role => role.slug === 'admin');
        },
    },
});

// Router - используем базовый путь /admin
// Все маршруты определены относительно /admin, поэтому в router они без префикса /admin
const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('./pages/auth/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('./pages/auth/Register.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('./pages/auth/ForgotPassword.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('./pages/auth/ResetPassword.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/',
        component: () => import('./layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresRole: ['admin'] },
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: () => import('./pages/admin/Dashboard.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            {
                path: 'media',
                name: 'admin.media',
                component: () => import('./pages/admin/Media.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            {
                path: 'notifications',
                name: 'admin.notifications',
                component: () => import('./pages/admin/Notifications.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            {
                path: 'users',
                name: 'admin.users',
                component: () => import('./pages/admin/Users.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            {
                path: 'roles',
                name: 'admin.roles',
                component: () => import('./pages/admin/Roles.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            {
                path: 'subscription',
                name: 'admin.subscription',
                component: () => import('./pages/admin/Subscription.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            // Документация
            {
                path: 'documentation',
                name: 'admin.documentation',
                component: () => import('./pages/admin/Documentation.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'] },
            },
            {
                path: 'support',
                name: 'admin.support',
                component: () => import('./pages/admin/Support.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'] },
            },
            {
                path: 'support/:id',
                name: 'admin.support.ticket',
                component: () => import('./pages/admin/SupportTicket.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'] },
            },
        ],
    },
];

// КРИТИЧНО: Исправляем текущий путь ДО инициализации Vue Router
// Это нужно сделать как можно раньше, чтобы Vue Router не использовал неправильный путь
const currentPath = window.location.pathname;
const currentHref = window.location.href;

console.log('🔍 Initial path check:', {
    pathname: currentPath,
    href: currentHref,
    documentBaseURI: document.baseURI,
});

// Исправляем путь, если он содержит /public/
if (currentPath.includes('/public/')) {
    const fixedPath = currentPath.replace(/\/public\/?/g, '/');
    const fixedHref = currentHref.replace(/\/public\/?/g, '/');
    console.log('🔧 Fixing current path with /public/:', { 
        originalPath: currentPath, 
        fixedPath,
        originalHref: currentHref,
        fixedHref,
    });
    // Заменяем текущий URL на исправленный БЕЗ перезагрузки страницы
    window.history.replaceState({}, '', fixedPath);
    console.log('✅ Replaced history state with fixed path');
}

// Исправляем base для Vue Router
// Всегда используем '/admin' как base, независимо от document.baseURI
let routerBase = '/admin';
console.log('🔧 Vue Router - Base:', { 
    routerBase, 
    documentBaseURI: document.baseURI,
    currentPath: window.location.pathname,
    fixedPath: window.location.pathname.replace(/\/public\/?/g, '/'),
});

const router = createRouter({
    history: createWebHistory(routerBase),
    routes,
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
    // КРИТИЧНО: Исправляем путь, если он содержит /public/
    if (to.path.includes('/public/')) {
        const fixedPath = to.path.replace(/\/public\/?/g, '/');
        console.log('🔧 Router Guard - Fixing path with /public/:', { original: to.path, fixed: fixedPath });
        // Редиректим на исправленный путь
        next(fixedPath);
        return;
    }
    
    // Исправляем fullPath, если он содержит /public/
    if (to.fullPath.includes('/public/')) {
        const fixedFullPath = to.fullPath.replace(/\/public\/?/g, '/');
        console.log('🔧 Router Guard - Fixing fullPath with /public/:', { original: to.fullPath, fixed: fixedFullPath });
        // Редиректим на исправленный путь
        next(fixedFullPath);
        return;
    }
    
    const isAuthenticated = store.getters.isAuthenticated;
    
    // КРИТИЧНО: Если требуется авторизация или роль, но пользователь еще не загружен, загружаем его
    if ((to.meta.requiresAuth || to.meta.requiresRole) && isAuthenticated && !store.state.user) {
        console.log('⏳ Router Guard - User not loaded, fetching user...');
        try {
            await store.dispatch('fetchUser');
            console.log('✅ Router Guard - User loaded:', {
                user: store.state.user,
                roles: store.state.user?.roles?.map(r => r.slug) || [],
            });
        } catch (error) {
            console.error('❌ Router Guard - Failed to fetch user:', error);
            next('/login');
            return;
        }
    }
    
    console.log('🔍 Router Guard - Navigation:', {
        to: to.path,
        fullPath: to.fullPath,
        from: from.path,
        requiresAuth: to.meta.requiresAuth,
        requiresRole: to.meta.requiresRole,
        isAuthenticated,
        user: store.state.user,
        userRoles: store.state.user?.roles?.map(r => r.slug) || [],
    });
    
    // 1. Проверка авторизации - ПЕРВЫЙ ПРИОРИТЕТ
    if (to.meta.requiresAuth && !isAuthenticated) {
        console.log('❌ Router Guard - Not authenticated, redirecting to /login');
        next('/login');
        return;
    }
    
    // 2. Если пользователь авторизован и пытается зайти на страницы авторизации, редиректим на главную
    if ((to.path === '/login' || to.path === '/register') && isAuthenticated) {
        console.log('✅ Router Guard - Already authenticated, redirecting to /');
        next('/');
        return;
    }
    
    // 3. Проверка подписки для админ-панели
    if (to.meta.requiresAuth && isAuthenticated && to.path !== '/subscription-expired') {
        try {
            const subscriptionResponse = await axios.get('/api/subscription/check');
            if (!subscriptionResponse.data.success || !subscriptionResponse.data.is_active) {
                console.log('❌ Router Guard - Subscription expired or inactive, redirecting to expired page');
                window.location.href = '/subscription-expired';
                return;
            }
        } catch (error) {
            // Если получили 403, значит подписка истекла
            if (error.response && error.response.status === 403) {
                console.log('❌ Router Guard - Subscription check failed (403), redirecting to expired page');
                window.location.href = '/subscription-expired';
                return;
            }
            // Для других ошибок продолжаем (может быть временная проблема с API)
            console.warn('⚠️ Router Guard - Subscription check error, continuing:', error.message);
        }
    }
    
    // 4. Проверка ролей - ВАЖНО: проверяем ПОСЛЕ загрузки пользователя
    if (to.meta.requiresRole) {
        const requiredRoles = Array.isArray(to.meta.requiresRole) 
            ? to.meta.requiresRole 
            : [to.meta.requiresRole];
        
        const userRoles = store.state.user?.roles?.map(r => r.slug) || [];
        const hasRole = store.getters.hasAnyRole(requiredRoles);
        
        console.log('🔍 Router Guard - Role check:', {
            route: to.path,
            routeName: to.name,
            requiredRoles,
            hasRole,
            userRoles,
            user: store.state.user,
            userRolesFull: store.state.user?.roles,
        });
        
        if (!hasRole) {
            // Пользователь не имеет нужной роли
            console.log('❌ Router Guard - No required role, redirecting to /', {
                route: to.path,
                requiredRoles,
                userRoles,
                userHasRoles: !!store.state.user?.roles,
                userRolesCount: store.state.user?.roles?.length || 0,
            });
            next('/');
            return;
        } else {
            console.log('✅ Router Guard - Role check passed', {
                route: to.path,
                requiredRoles,
                userRoles,
            });
        }
    }
    
    console.log('✅ Router Guard - All checks passed, allowing navigation');
    next();
});

// Initialize app
import App from './App.vue';
const app = createApp(App);

// Set up axios defaults
if (store.state.token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${store.state.token}`;
}

// Инициализация пользователя при загрузке приложения
if (store.state.token) {
    console.log('🔍 App initialization - Token found, fetching user...');
    store.dispatch('fetchUser').then(() => {
        console.log('✅ App initialization - User fetched:', {
            user: store.state.user,
            roles: store.state.user?.roles,
            rolesCount: store.state.user?.roles?.length || 0,
        });
        // Загружаем меню после загрузки пользователя
        store.dispatch('fetchMenu');
        store.dispatch('fetchNotifications');
    }).catch((error) => {
        console.error('❌ App initialization - Error fetching user:', error);
    });
} else {
    console.log('⚠️ App initialization - No token found');
}

// Инициализация темы при загрузке приложения
// Применяем тему сразу, до монтирования приложения
const savedTheme = localStorage.getItem('theme') || 'light';
const html = document.documentElement;
if (savedTheme === 'dark') {
    html.classList.add('dark');
    html.setAttribute('data-theme', 'dark');
    html.style.colorScheme = 'dark';
} else {
    html.classList.remove('dark');
    html.setAttribute('data-theme', 'light');
    html.style.colorScheme = 'light';
}
// Устанавливаем начальное состояние в store
store.state.theme = savedTheme;

// Initialize user and menu on app start
if (store.state.token) {
    console.log('🔍 App initialization - Token found, fetching user...');
    store.dispatch('fetchUser').then(() => {
        console.log('✅ App initialization - User fetched:', {
            user: store.state.user,
            roles: store.state.user?.roles,
            rolesCount: store.state.user?.roles?.length || 0,
        });
        // Загружаем меню после загрузки пользователя
        store.dispatch('fetchMenu');
        store.dispatch('fetchNotifications');
    }).catch((error) => {
        console.error('❌ App initialization - Error fetching user:', error);
    });
} else {
    console.log('⚠️ App initialization - No token found');
}

app.use(store);
app.use(router);

// Mount app
// Монтируем приложение в контейнер #admin-app
const appContainer = document.getElementById('admin-app');
if (appContainer) {
    app.mount('#admin-app');
}

