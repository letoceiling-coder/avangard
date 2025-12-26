<template>
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md space-y-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold">Сброс пароля</h1>
                <p class="text-muted-foreground mt-2">Введите новый пароль</p>
            </div>
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div v-if="error" class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-800 dark:text-red-200">
                        {{ error }}
                    </div>
                    <div v-if="success" class="rounded-md bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-800 dark:text-green-200">
                        {{ success }}
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
                    <div>
                        <label for="password" class="block text-sm font-medium mb-2">Новый пароль</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors disabled:opacity-50 border-input focus:border-ring focus:ring-ring/20"
                            placeholder="Минимум 8 символов"
                        />
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium mb-2">Подтверждение пароля</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors disabled:opacity-50 border-input focus:border-ring focus:ring-ring/20"
                            placeholder="Повторите пароль"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!loading">Изменить пароль</span>
                        <span v-else>Изменение...</span>
                    </button>
                </form>
            </div>
            <p class="text-center text-sm text-muted-foreground">
                <router-link to="/login" class="text-primary hover:underline">Вернуться к входу</router-link>
            </p>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
    name: 'ResetPassword',
    setup() {
        const route = useRoute();
        const router = useRouter();
        const loading = ref(false);
        const error = ref('');
        const success = ref('');
        const form = ref({
            email: '',
            password: '',
            password_confirmation: '',
            token: '',
        });

        onMounted(() => {
            form.value.token = route.query.token || '';
        });

        const handleSubmit = async () => {
            loading.value = true;
            error.value = '';
            success.value = '';

            try {
                await axios.post('/api/auth/reset-password', form.value);
                success.value = 'Пароль успешно изменен';
                setTimeout(() => {
                    router.push('/login');
                }, 2000);
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при изменении пароля';
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

