<template>
    <div class="dashboard-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Панель управления</h1>
            <p class="text-muted-foreground mt-1">Добро пожаловать в админ панель</p>
        </div>
        
        <SubscriptionInfo />
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold mb-2">Статистика</h2>
                <p class="text-muted-foreground">Здесь будет статистика</p>
            </div>
        </div>
    </div>
</template>

<script>
import SubscriptionInfo from '../../components/admin/SubscriptionInfo.vue';

export default {
    name: 'Dashboard',
    components: {
        SubscriptionInfo,
    },
};
</script>

