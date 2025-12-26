<template>
    <div class="roles-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Роли</h1>
                <p class="text-muted-foreground mt-1">Управление ролями системы</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать роль</span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка ролей...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Roles Grid -->
        <div v-if="!loading && roles.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="role in roles"
                :key="role.id"
                class="bg-card rounded-lg border border-border p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">{{ role.name }}</h3>
                        <p class="text-sm text-muted-foreground mt-1">{{ role.slug }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="editRole(role)"
                            class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                        >
                            Редактировать
                        </button>
                        <button
                            @click="deleteRole(role)"
                            class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
                <p v-if="role.description" class="text-sm text-muted-foreground mb-4">
                    {{ role.description }}
                </p>
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span>Пользователей:</span>
                    <span class="font-medium">{{ role.users?.length || 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && roles.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Роли не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ showEditModal ? 'Редактировать роль' : 'Создать роль' }}
                </h3>
                <form @submit.prevent="saveRole" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Название</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Slug</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            required
                            pattern="[a-z0-9-]+"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        />
                        <p class="text-xs text-muted-foreground mt-1">Только строчные буквы, цифры и дефисы</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-border rounded bg-background"
                        ></textarea>
                    </div>
                    <div class="flex gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { apiGet, apiPost, apiPut, apiDelete } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'Roles',
    setup() {
        const loading = ref(false)
        const saving = ref(false)
        const error = ref(null)
        const roles = ref([])
        const showCreateModal = ref(false)
        const showEditModal = ref(false)
        const form = ref({
            id: null,
            name: '',
            slug: '',
            description: ''
        })

        const fetchRoles = async () => {
            loading.value = true
            error.value = null
            try {
                const response = await apiGet('/roles')
                if (!response.ok) {
                    throw new Error('Ошибка загрузки ролей')
                }
                const data = await response.json()
                roles.value = data.data || []
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки ролей'
            } finally {
                loading.value = false
            }
        }

        const editRole = (role) => {
            form.value = {
                id: role.id,
                name: role.name,
                slug: role.slug,
                description: role.description || ''
            }
            showEditModal.value = true
        }

        const deleteRole = async (role) => {
            const result = await Swal.fire({
                title: 'Удалить роль?',
                html: `Вы уверены, что хотите удалить роль <strong>"${role.name}"</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })

            if (!result.isConfirmed) return

            try {
                const response = await apiDelete(`/roles/${role.id}`)
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка удаления роли')
                }
                await Swal.fire({
                    title: 'Роль удалена',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })
                await fetchRoles()
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка удаления роли',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            }
        }

        const saveRole = async () => {
            saving.value = true
            error.value = null
            try {
                const roleData = {
                    name: form.value.name,
                    slug: form.value.slug.toLowerCase().replace(/\s+/g, '-'),
                    description: form.value.description
                }

                let response
                if (showEditModal.value) {
                    response = await apiPut(`/roles/${form.value.id}`, roleData)
                } else {
                    response = await apiPost('/roles', roleData)
                }

                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка сохранения роли')
                }

                await Swal.fire({
                    title: showEditModal.value ? 'Роль обновлена' : 'Роль создана',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                closeModal()
                await fetchRoles()
            } catch (err) {
                error.value = err.message || 'Ошибка сохранения роли'
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения роли',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                saving.value = false
            }
        }

        const closeModal = () => {
            showCreateModal.value = false
            showEditModal.value = false
            form.value = {
                id: null,
                name: '',
                slug: '',
                description: ''
            }
        }

        onMounted(() => {
            fetchRoles()
        })

        return {
            loading,
            saving,
            error,
            roles,
            showCreateModal,
            showEditModal,
            form,
            editRole,
            deleteRole,
            saveRole,
            closeModal
        }
    }
}
</script>
