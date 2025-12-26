<template>
    <div class="flex h-screen w-full overflow-hidden bg-background">
        <!-- Overlay для мобильного меню -->
        <div
            v-if="isMobileMenuOpen"
            @click="closeMobileMenu"
            class="fixed inset-0 bg-black/50 z-40 lg:hidden transition-opacity"
            :class="isMobileMenuOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        ></div>
        
        <Sidebar />
        <div class="flex flex-1 flex-col overflow-hidden min-w-0">
            <Header />
            <main class="flex-1 overflow-y-auto bg-background p-4 sm:p-6 lg:p-8">
                <div class="max-w-[1600px] mx-auto">
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script>
import { ref, provide, watch } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from '../components/admin/Sidebar.vue';
import Header from '../components/admin/Header.vue';

export default {
    name: 'AdminLayout',
    components: {
        Sidebar,
        Header,
    },
    setup() {
        const route = useRoute();
        const isMobileMenuOpen = ref(false);

        const openMobileMenu = () => {
            isMobileMenuOpen.value = true;
            // Блокируем скролл body при открытом меню
            document.body.style.overflow = 'hidden';
        };

        const closeMobileMenu = () => {
            isMobileMenuOpen.value = false;
            // Разблокируем скролл body
            document.body.style.overflow = '';
        };

        const toggleMobileMenu = () => {
            if (isMobileMenuOpen.value) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        };

        // Закрываем меню при изменении маршрута (навигации)
        watch(() => route.path, () => {
            if (isMobileMenuOpen.value) {
                closeMobileMenu();
            }
        });

        // Предоставляем функции и состояние дочерним компонентам
        provide('mobileMenu', {
            isOpen: isMobileMenuOpen,
            open: openMobileMenu,
            close: closeMobileMenu,
            toggle: toggleMobileMenu,
        });

        return {
            isMobileMenuOpen,
            closeMobileMenu,
        };
    },
};
</script>

