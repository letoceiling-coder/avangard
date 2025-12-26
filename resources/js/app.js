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
            state.user = user;
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
                commit('SET_USER', response.data.user);
            } catch (error) {
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

// Router
const routes = [
    {
        path: '/',
        redirect: () => {
            const token = localStorage.getItem('token');
            return token ? '/admin' : '/login';
        },
    },
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
        path: '/admin',
        component: () => import('./layouts/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: () => import('./pages/admin/Dashboard.vue'),
            },
            {
                path: 'products',
                name: 'admin.products',
                component: () => import('./pages/admin/Products.vue'),
            },
            {
                path: 'categories',
                name: 'admin.categories',
                component: () => import('./pages/admin/Categories.vue'),
            },
            {
                path: 'services',
                name: 'admin.services',
                component: () => import('./pages/admin/Services.vue'),
            },
            {
                path: 'media',
                name: 'admin.media',
                component: () => import('./pages/admin/Media.vue'),
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
            },
            {
                path: 'versions',
                name: 'admin.versions',
                component: () => import('./pages/admin/Versions.vue'),
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: () => import('./pages/admin/Settings.vue'),
            },
            {
                path: 'notifications',
                name: 'admin.notifications',
                component: () => import('./pages/admin/Notifications.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const isAuthenticated = store.getters.isAuthenticated;
    
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if ((to.path === '/login' || to.path === '/register') && isAuthenticated) {
        next('/admin');
    } else if (to.meta.requiresRole) {
        // Проверка ролей
        const requiredRoles = Array.isArray(to.meta.requiresRole) 
            ? to.meta.requiresRole 
            : [to.meta.requiresRole];
        
        if (!store.getters.hasAnyRole(requiredRoles)) {
            // Пользователь не имеет нужной роли
            next('/admin');
        } else {
            next();
        }
    } else {
        next();
    }
});

// Initialize app
import App from './App.vue';
const app = createApp(App);

// Set up axios defaults
if (store.state.token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${store.state.token}`;
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
store.dispatch('fetchUser').then((user) => {
    if (user) {
        store.dispatch('fetchMenu');
        store.dispatch('fetchNotifications');
    }
});

app.use(store);
app.use(router);

// Mount app
// Монтируем приложение в контейнер #app (единая точка входа)
const appContainer = document.getElementById('app');
if (appContainer) {
    app.mount('#app');
}
