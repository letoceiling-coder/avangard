<template>
    <div class="subscription-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Подписка</h1>
            <p class="text-muted-foreground mt-1">Информация о подписке и сроке действия</p>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="text-muted-foreground">Загрузка информации о подписке...</div>
        </div>

        <div v-else-if="subscription" class="space-y-6">
            <!-- Основная информация о подписке -->
            <div 
                class="bg-card rounded-lg border p-6"
                :class="{
                    'border-border': isActive && !isExpiringSoon,
                    'border-yellow-500': isExpiringSoon && daysLeft > 0,
                    'border-red-500': isExpired || daysLeft === 0
                }"
            >
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold">Статус подписки</h2>
                    <span 
                        class="px-4 py-2 rounded-full text-sm font-bold"
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': subscription.status === 'active',
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': subscription.status === 'expired',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': subscription.status === 'suspended'
                        }"
                    >
                        {{ getStatusLabel(subscription.status) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Действует до</label>
                            <div class="mt-1 text-lg font-semibold">
                                {{ formatDate(subscription.expires_at) }}
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Осталось дней</label>
                            <div 
                                class="mt-1 text-lg font-semibold"
                                :class="{
                                    'text-green-600 dark:text-green-400': daysLeft > 3,
                                    'text-yellow-600 dark:text-yellow-400': daysLeft <= 3 && daysLeft > 0,
                                    'text-red-600 dark:text-red-400': daysLeft === 0 || isExpired
                                }"
                            >
                                {{ daysLeft !== null ? daysLeft : '-' }}
                                <span v-if="daysLeft !== null" class="text-sm text-muted-foreground ml-1">
                                    {{ daysText }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Статус активности</label>
                            <div class="mt-1 text-lg font-semibold">
                                <span 
                                    :class="{
                                        'text-green-600 dark:text-green-400': isActive,
                                        'text-red-600 dark:text-red-400': !isActive
                                    }"
                                >
                                    {{ isActive ? 'Активна' : 'Неактивна' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Домен</label>
                            <div class="mt-1 text-lg font-semibold">
                                {{ domain }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Предупреждение о скором истечении -->
            <div 
                v-if="isExpiringSoon" 
                class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-1">
                            Внимание! Подписка скоро истечет
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            <span v-if="daysLeft > 0">
                                Ваша подписка истекает через {{ daysLeft }} {{ daysText }}. 
                            </span>
                            <span v-else>
                                Ваша подписка истекла!
                            </span>
                            Для продолжения работы с админ-панелью необходимо продлить подписку.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Информация о блокировке -->
            <div 
                v-if="isExpired" 
                class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-red-800 dark:text-red-200 mb-1">
                            Подписка истекла
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            Доступ к админ-панели ограничен. Для продления подписки свяжитесь с администратором системы.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Дополнительная информация -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h3 class="text-lg font-semibold mb-4">Дополнительная информация</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Последняя проверка:</span>
                        <span class="font-medium">{{ lastCheckTime }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">API Endpoint:</span>
                        <span class="font-medium font-mono text-xs">{{ apiUrl }}</span>
                    </div>
                </div>
            </div>

            <!-- Кнопка обновления -->
            <div class="flex justify-end">
                <button
                    @click="checkSubscription"
                    :disabled="loading"
                    class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="loading">Обновление...</span>
                    <span v-else>Обновить информацию</span>
                </button>
            </div>
        </div>

        <div v-else class="bg-card rounded-lg border border-border p-6">
            <div class="text-center py-8">
                <p class="text-muted-foreground mb-4">Не удалось загрузить информацию о подписке</p>
                <button
                    @click="checkSubscription"
                    class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
                >
                    Попробовать снова
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

export default {
    name: 'Subscription',
    setup() {
        const subscription = ref(null);
        const loading = ref(true);
        const lastCheckTime = ref(null);
        const checkInterval = ref(null);
        const domain = ref(window.location.hostname);
        const apiUrl = ref('/api/subscription/check');

        const isActive = computed(() => {
            return subscription.value && subscription.value.is_active === true;
        });

        const isExpired = computed(() => {
            return subscription.value && !subscription.value.is_active;
        });

        const isExpiringSoon = computed(() => {
            if (!subscription.value || !subscription.value.expires_at) return false;
            return daysLeft.value <= 3 && daysLeft.value >= 0;
        });

        const daysLeft = computed(() => {
            if (!subscription.value || !subscription.value.expires_at) return null;
            try {
                const expires = new Date(subscription.value.expires_at);
                const now = new Date();
                const diff = expires - now;
                const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
                return days >= 0 ? days : 0;
            } catch (e) {
                return null;
            }
        });

        const daysText = computed(() => {
            const days = daysLeft.value;
            if (days === null) return '';
            if (days === 1) return 'день';
            if (days >= 2 && days <= 4) return 'дня';
            return 'дней';
        });

        const checkSubscription = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/subscription/check');
                if (response.data.success) {
                    subscription.value = response.data.subscription;
                    lastCheckTime.value = new Date().toLocaleString('ru-RU');
                    
                    // Если подписка неактивна, редиректим на страницу истечения
                    if (!response.data.is_active) {
                        setTimeout(() => {
                            window.location.href = '/subscription-expired';
                        }, 2000);
                    }
                }
            } catch (error) {
                console.error('Ошибка проверки подписки:', error);
                
                // Если получили 403, значит подписка истекла
                if (error.response && (error.response.status === 403 || error.response.status === 404)) {
                    setTimeout(() => {
                        window.location.href = '/subscription-expired';
                    }, 2000);
                }
            } finally {
                loading.value = false;
            }
        };

        const formatDate = (dateString) => {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('ru-RU', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        };

        const getStatusLabel = (status) => {
            const labels = {
                active: 'Активна',
                expired: 'Истекла',
                suspended: 'Приостановлена'
            };
            return labels[status] || status;
        };

        onMounted(() => {
            checkSubscription();
            // Проверяем подписку каждые 5 минут
            checkInterval.value = setInterval(() => {
                checkSubscription();
            }, 5 * 60 * 1000);
        });

        onUnmounted(() => {
            if (checkInterval.value) {
                clearInterval(checkInterval.value);
            }
        });

        return {
            subscription,
            loading,
            lastCheckTime,
            domain,
            apiUrl,
            isActive,
            isExpired,
            isExpiringSoon,
            daysLeft,
            daysText,
            checkSubscription,
            formatDate,
            getStatusLabel,
        };
    },
};
</script>

<style scoped>
.subscription-page {
    max-width: 1200px;
}
</style>

