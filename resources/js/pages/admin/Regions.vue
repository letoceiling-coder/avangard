<template>
    <div class="regions-page space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Регионы</h1>
                <p class="text-muted-foreground mt-1">Управление доступными регионами городов TrendAgent</p>
            </div>
            <button
                @click="saveAll"
                :disabled="saving || !hasChanges"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                <svg v-if="!saving" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <svg v-else class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ saving ? 'Сохранение...' : 'Сохранить изменения' }}</span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка регионов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Regions Tree -->
        <div v-if="!loading && regions.length > 0" class="bg-card rounded-lg border border-border">
            <div class="p-6 space-y-6">
                <div
                    v-for="region in regions"
                    :key="region.id"
                    class="border-b border-border last:border-b-0 pb-6 last:pb-0"
                >
                    <!-- Region Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="region.is_active"
                                    @change="toggleRegion(region.id, $event.target.checked)"
                                    class="w-5 h-5 rounded border-border text-accent focus:ring-accent/20"
                                />
                                <span class="text-lg font-semibold text-foreground">{{ region.name }}</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-muted-foreground">
                                {{ getActiveCitiesCount(region) }} / {{ region.cities?.length || 0 }} городов
                            </span>
                            <button
                                @click="toggleRegionExpand(region.id)"
                                class="px-3 py-1 text-xs bg-muted/50 hover:bg-muted rounded transition-colors"
                            >
                                {{ expandedRegions[region.id] ? '▼ Свернуть' : '▶ Развернуть' }}
                            </button>
                        </div>
                    </div>

                    <!-- Cities List -->
                    <div v-if="expandedRegions[region.id]" class="pl-8 space-y-2">
                        <div
                            v-for="city in region.cities"
                            :key="city.id"
                            class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/30 transition-colors"
                        >
                            <label class="flex items-center gap-2 cursor-pointer flex-1">
                                <input
                                    type="checkbox"
                                    :checked="city.is_active"
                                    @change="toggleCity(region.id, city.id, $event.target.checked)"
                                    class="w-4 h-4 rounded border-border text-accent focus:ring-accent/20"
                                />
                                <span class="text-sm text-foreground">{{ city.name }}</span>
                            </label>
                        </div>
                        <div v-if="!region.cities || region.cities.length === 0" class="text-sm text-muted-foreground pl-4">
                            Города не найдены
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && regions.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Регионы не найдены</p>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { apiGet, apiPut, apiPost } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'Regions',
    setup() {
        const loading = ref(false)
        const saving = ref(false)
        const error = ref(null)
        const regions = ref([])
        const expandedRegions = ref({})
        const changes = ref({
            cities: {},
            regions: {}
        })

        // Вычисляемое свойство: есть ли изменения
        const hasChanges = computed(() => {
            return Object.keys(changes.value.cities).length > 0 || Object.keys(changes.value.regions).length > 0
        })

        // Подсчет активных городов для региона
        const getActiveCitiesCount = (region) => {
            if (!region.cities || region.cities.length === 0) return 0
            return region.cities.filter(c => {
                const cityId = c.id
                // Проверяем, есть ли изменение для этого города
                if (changes.value.cities[cityId] !== undefined) {
                    return changes.value.cities[cityId]
                }
                return c.is_active
            }).length
        }

        // Загрузка данных
        const fetchRegions = async () => {
            loading.value = true
            error.value = null
            try {
                const response = await apiGet('/regions')
                if (!response.ok) {
                    throw new Error('Ошибка загрузки регионов')
                }
                const data = await response.json()
                regions.value = data.data || []
                
                // Разворачиваем все регионы по умолчанию
                regions.value.forEach(region => {
                    expandedRegions.value[region.id] = true
                })
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки регионов'
            } finally {
                loading.value = false
            }
        }

        // Переключение региона
        const toggleRegion = (regionId, isActive) => {
            changes.value.regions[regionId] = isActive
            
            // Обновляем визуально
            const region = regions.value.find(r => r.id === regionId)
            if (region) {
                region.is_active = isActive
            }
        }

        // Переключение города
        const toggleCity = (regionId, cityId, isActive) => {
            changes.value.cities[cityId] = isActive
            
            // Обновляем визуально
            const region = regions.value.find(r => r.id === regionId)
            if (region && region.cities) {
                const city = region.cities.find(c => c.id === cityId)
                if (city) {
                    city.is_active = isActive
                }
            }
        }

        // Переключение развертывания региона
        const toggleRegionExpand = (regionId) => {
            expandedRegions.value[regionId] = !expandedRegions.value[regionId]
        }

        // Сохранение всех изменений
        const saveAll = async () => {
            saving.value = true
            error.value = null
            
            try {
                // Сохраняем изменения городов
                if (Object.keys(changes.value.cities).length > 0) {
                    const citiesToUpdate = Object.entries(changes.value.cities).map(([id, isActive]) => ({
                        id: parseInt(id),
                        is_active: isActive
                    }))
                    
                    const response = await apiPost('/regions/cities/bulk-update', {
                        cities: citiesToUpdate
                    })
                    
                    if (!response.ok) {
                        const errorData = await response.json()
                        throw new Error(errorData.message || 'Ошибка обновления городов')
                    }
                }

                // Сохраняем изменения регионов
                if (Object.keys(changes.value.regions).length > 0) {
                    const regionsToUpdate = Object.entries(changes.value.regions).map(([id, isActive]) => ({
                        id: parseInt(id),
                        is_active: isActive
                    }))
                    
                    const response = await apiPost('/regions/regions/bulk-update', {
                        regions: regionsToUpdate
                    })
                    
                    if (!response.ok) {
                        const errorData = await response.json()
                        throw new Error(errorData.message || 'Ошибка обновления регионов')
                    }
                }

                // Очищаем изменения
                changes.value = { cities: {}, regions: {} }

                await Swal.fire({
                    title: 'Изменения сохранены',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                // Перезагружаем данные
                await fetchRegions()
            } catch (err) {
                error.value = err.message || 'Ошибка сохранения изменений'
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения изменений',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                saving.value = false
            }
        }

        onMounted(() => {
            fetchRegions()
        })

        return {
            loading,
            saving,
            error,
            regions,
            expandedRegions,
            changes,
            hasChanges,
            getActiveCitiesCount,
            toggleRegion,
            toggleCity,
            toggleRegionExpand,
            saveAll
        }
    }
}
</script>

<style scoped>
.regions-page {
    max-width: 1200px;
    margin: 0 auto;
}
</style>

