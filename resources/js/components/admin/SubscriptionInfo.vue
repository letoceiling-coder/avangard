<template>
  <div 
    v-if="subscription" 
    class="subscription-info"
    :class="{ 
      'expiring-soon': isExpiringSoon, 
      'expired': isExpired 
    }"
  >
    <div class="subscription-card">
      <div class="subscription-header">
        <h3 class="text-sm font-semibold">Статус подписки</h3>
        <span 
          :class="['badge', `badge-${subscription.status}`]"
          class="px-3 py-1 rounded-full text-xs font-bold"
        >
          {{ getStatusLabel(subscription.status) }}
        </span>
      </div>
      
      <div class="subscription-details mt-3 space-y-2">
        <div class="detail-item flex justify-between items-center">
          <span class="label text-sm text-muted-foreground">Действует до:</span>
          <span class="value text-sm font-semibold">{{ formatDate(subscription.expires_at) }}</span>
        </div>
        <div class="detail-item flex justify-between items-center">
          <span class="label text-sm text-muted-foreground">Осталось дней:</span>
          <span 
            class="value text-sm font-semibold"
            :class="{ 'text-warning': daysLeft <= 3 && daysLeft > 0, 'text-destructive': daysLeft === 0 }"
          >
            {{ daysLeft !== null ? daysLeft : '-' }}
          </span>
        </div>
      </div>
      
      <div 
        v-if="isExpiringSoon" 
        class="warning-notification mt-3 p-3 rounded-md border-l-4"
        :class="{
          'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500 text-yellow-800 dark:text-yellow-200': daysLeft > 0,
          'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-200': daysLeft === 0
        }"
      >
        <div class="flex items-center gap-2">
          <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <span class="text-xs font-medium">
            <strong>Внимание!</strong> 
            <span v-if="daysLeft > 0">
              Подписка истекает через {{ daysLeft }} {{ daysText }}
            </span>
            <span v-else>
              Подписка истекла!
            </span>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

export default {
  name: 'SubscriptionInfo',
  setup() {
    const subscription = ref(null);
    const loading = ref(true);
    const checkInterval = ref(null);

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
      try {
        const response = await axios.get('/api/subscription/check');
        if (response.data.success) {
          subscription.value = response.data.subscription;
          
          // Если подписка неактивна, редиректим на страницу истечения
          if (!response.data.is_active) {
            window.location.href = '/subscription-expired';
            return;
          }
        }
      } catch (error) {
        console.error('Ошибка проверки подписки:', error);
        
        // Если получили 403, значит подписка истекла
        if (error.response && (error.response.status === 403 || error.response.status === 404)) {
          window.location.href = '/subscription-expired';
          return;
        }
        // Для других ошибок просто логируем, но не блокируем работу
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
      isActive,
      isExpired,
      isExpiringSoon,
      daysLeft,
      daysText,
      formatDate,
      getStatusLabel,
    };
  },
};
</script>

<style scoped>
.subscription-info {
  margin-bottom: 20px;
}

.subscription-card {
  background: hsl(var(--card));
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid hsl(var(--border));
}

.subscription-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: bold;
}

.badge-active {
  background: #10b981;
  color: white;
}

.badge-expired {
  background: #ef4444;
  color: white;
}

.badge-suspended {
  background: #f59e0b;
  color: white;
}

.expiring-soon .subscription-card {
  border-color: #f59e0b;
  border-width: 2px;
}

.expired .subscription-card {
  border-color: #ef4444;
  border-width: 2px;
}
</style>

