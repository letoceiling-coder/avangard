<template>
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md space-y-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold">Регистрация</h1>
                <p class="text-muted-foreground mt-2">Создайте новый аккаунт</p>
            </div>
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div v-if="error" class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-800 dark:text-red-200">
                        {{ error }}
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">Имя</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors disabled:opacity-50 border-input focus:border-ring focus:ring-ring/20"
                            placeholder="Иван Иванов"
                        />
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
                        <label for="password" class="block text-sm font-medium mb-2">Пароль</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors disabled:opacity-50 border-input focus:border-ring focus:ring-ring/20"
                            placeholder="Минимум 8 символов"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Пароль должен содержать буквы разного регистра, цифры и символы
                        </p>
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
                        <span v-if="!loading">Зарегистрироваться</span>
                        <span v-else>Регистрация...</span>
                    </button>
                </form>
            </div>
            <p class="text-center text-sm text-muted-foreground">
                Уже есть аккаунт?
                <router-link to="/login" class="text-primary hover:underline">Войти</router-link>
            </p>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

export default {
    name: 'Register',
    setup() {
        const store = useStore();
        const router = useRouter();
        const loading = ref(false);
        const error = ref('');
        const form = ref({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        });

        const handleSubmit = async () => {
            loading.value = true;
            error.value = '';

            const result = await store.dispatch('register', form.value);

            if (result.success) {
                router.push({ name: 'admin.dashboard' });
            } else {
                error.value = result.error;
            }

            loading.value = false;
        };

        return {
            form,
            loading,
            error,
            handleSubmit,
        };
    },
};
</script>

