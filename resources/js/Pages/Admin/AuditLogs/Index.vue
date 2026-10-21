<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    actions: { type: Array, default: () => [] },
    actors: { type: Array, default: () => [] },
});

const q = ref(props.filters.q || '');
const action = ref(props.filters.action || '');
const userId = ref(props.filters.user_id || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');

const search = () => router.get('/admin/audit-logs', {
    q: q.value,
    action: action.value,
    user_id: userId.value,
    from: from.value,
    to: to.value,
}, { preserveState: true });
</script>

<template>
    <PageHeader title="سجل التدقيق" text="كل إجراء حساس على المنصة: من نفّذه، ومتى، ومن أي عنوان." />
    <form class="app-card grid gap-3 p-4 md:grid-cols-5" @submit.prevent="search">
        <input v-model="q" class="app-select" placeholder="الممثل أو الإجراء أو IP">
        <select v-model="action" class="app-select">
            <option value="">كل الإجراءات</option>
            <option v-for="item in actions" :key="item.value" :value="item.value">{{ item.label }}</option>
        </select>
        <select v-model="userId" class="app-select">
            <option value="">كل الممثلين</option>
            <option v-for="actor in actors" :key="actor.id" :value="actor.id">{{ actor.name }}</option>
        </select>
        <input v-model="from" type="date" class="app-select">
        <div class="flex gap-2">
            <input v-model="to" type="date" class="app-select flex-1">
            <button class="min-h-12 rounded-2xl bg-navy px-4 text-sm font-semibold text-white">تصفية</button>
        </div>
    </form>
    <div class="app-card overflow-hidden">
        <div v-if="logs.data?.length" class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-canvas text-muted">
                    <tr>
                        <th>الإجراء</th>
                        <th>الممثل</th>
                        <th>العنصر</th>
                        <th>التفاصيل</th>
                        <th>IP</th>
                        <th>الوقت</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs.data" :key="log.id" class="border-t border-line align-top hover:bg-canvas/60">
                        <td class="font-semibold text-navy">{{ log.action_label }}</td>
                        <td>
                            <span class="block">{{ log.actor?.name || 'نظام' }}</span>
                            <span class="text-xs text-muted">{{ log.actor?.email }}</span>
                        </td>
                        <td>{{ log.entity }} #{{ log.entity_id }}</td>
                        <td class="max-w-xs text-xs text-muted">{{ log.metadata ? JSON.stringify(log.metadata) : '—' }}</td>
                        <td>{{ log.ip_address || '—' }}</td>
                        <td>{{ log.created_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="لا توجد أحداث مطابقة" text="غيّر الفلاتر أو نفّذ إجراءاً إدارياً ليظهر هنا." />
    </div>
    <Pagination :links="logs.links" />
</template>
