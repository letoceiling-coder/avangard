<template>
    <span
        :class="[
            'px-2 py-1 text-xs rounded font-medium',
            getStatusClass(status)
        ]"
    >
        {{ getStatusLabel(status) }}
    </span>
</template>

<script>
export default {
    name: 'StatusBadge',
    props: {
        status: {
            type: String,
            required: true,
            validator: (value) => ['open', 'in_progress', 'closed'].includes(value)
        }
    },
    methods: {
        getStatusClass(status) {
            const classes = {
                open: 'bg-green-500/20 text-green-600 border border-green-500/30',
                in_progress: 'bg-blue-500/20 text-blue-600 border border-blue-500/30',
                closed: 'bg-gray-500/20 text-gray-600 border border-gray-500/30',
            }
            return classes[status] || classes.open
        },
        getStatusLabel(status) {
            const labels = {
                open: 'Открыт',
                in_progress: 'В работе',
                closed: 'Закрыт',
            }
            return labels[status] || status
        }
    }
}
</script>

