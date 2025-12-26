<template>
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md space-y-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold">Восстановление пароля</h1>
                <p class="text-muted-foreground mt-2">Введите ваш email для восстановления пароля</p>
            </div>
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <div v-if="!success">
                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div v-if="error" class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-800 dark:text-red-200">
                            {{ error }}
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium mb-2">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors disabled:opacity-50 border-input focus:border-ring focus:ring-ring/20"
                                placeholder="your@email.com"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!loading">Отправить ссылку</span>
                            <span v-else>Отправка...</span>
                        </button>
                    </form>
                </div>
                <div v-else class="mt-4 space-y-4 text-center">
                    <div class="rounded-md bg-accent/10 dark:bg-accent/20 p-4">
                        <p class="text-sm text-foreground">
                            Ссылка для восстановления пароля будет отправлена на указанный email
                        </p>
                    </div>
                    <router-link
                        to="/login"
                        class="block w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                    >
                        Вернуться к входу
                    </router-link>
                </div>
            </div>
            <p class="text-center text-sm text-muted-foreground">
                Вспомнили пароль?
                <router-link to="/login" class="text-primary hover:underline">Войти</router-link>
            </p>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';

export default {
    name: 'ForgotPassword',
    setup() {
        const loading = ref(false);
        const error = ref('');
        const success = ref(false);
        const form = ref({
            email: '',
        });

        const handleSubmit = async () => {
            loading.value = true;
            error.value = '';

            try {
                await axios.post('/api/auth/forgot-password', form.value);
                success.value = true;
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при отправке запроса';
            }

            loading.value = false;
        };

        return {
            form,
            loading,
            error,
            success,
            handleSubmit,
        };
    },
};
</script>

