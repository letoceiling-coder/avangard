<template>
    <header class="relative flex h-16 items-center justify-between border-b border-border bg-card backdrop-blur-xl px-4 sm:px-6 gap-2 sm:gap-4 z-30">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <button
                @click="toggleMobileMenu"
                class="lg:hidden flex-shrink-0 h-11 w-11 flex items-center justify-center rounded-md hover:bg-accent/10 transition-colors"
                aria-label="Открыть меню"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div class="hidden sm:flex items-center gap-2 text-sm min-w-0">
                <span class="text-muted-foreground truncate">Панель управления</span>
                <span class="text-muted-foreground">/</span>
                <span class="font-semibold text-foreground truncate">{{ currentPageTitle }}</span>
            </div>
            <div class="flex sm:hidden items-center text-sm min-w-0">
                <span class="font-semibold text-foreground truncate">{{ currentPageTitle }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="relative hidden md:block">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    type="search"
                    placeholder="Поиск..."
                    class="w-48 lg:w-64 pl-9 bg-input border-border rounded-xl h-11 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                />
            </div>
            <button class="md:hidden h-11 w-11 flex items-center justify-center rounded-md hover:bg-accent/10 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
            <NotificationDropdown />
            <button
                @click="toggleTheme"
                class="h-11 w-11 flex items-center justify-center rounded-md hover:bg-accent/10 transition-colors"
                :title="isDarkMode ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
            >
                <svg v-if="isDarkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </button>
            <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-accent/20 border border-accent/30 flex items-center justify-center text-sm font-bold text-accent flex-shrink-0">
                {{ userInitials }}
            </div>
        </div>
    </header>
</template>

<script>
import { computed, inject } from 'vue';
import { useStore } from 'vuex';
import { useRoute } from 'vue-router';
import NotificationDropdown from './NotificationDropdown.vue';

export default {
    name: 'Header',
    components: {
        NotificationDropdown,
    },
    setup() {
        const store = useStore();
        const route = useRoute();
        const mobileMenu = inject('mobileMenu', null);
        
        const user = computed(() => store.getters.user);
        const isDarkMode = computed(() => store.getters.isDarkMode);
        const userInitials = computed(() => {
            if (!user.value?.name) return 'U';
            const names = user.value.name.split(' ');
            return names.map(n => n[0]).join('').toUpperCase().substring(0, 2);
        });
        const currentPageTitle = computed(() => {
            return route.meta?.title || 'Панель управления';
        });

        const toggleTheme = () => {
            store.dispatch('toggleTheme');
        };

        const toggleMobileMenu = () => {
            if (mobileMenu) {
                mobileMenu.toggle();
            }
        };

        return {
            user,
            userInitials,
            currentPageTitle,
            isDarkMode,
            toggleTheme,
            toggleMobileMenu,
        };
    },
};
</script>

