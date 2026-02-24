import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useAuditStore = defineStore('audit', () => {
    const logs = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchLogs(page = 1, filters = {}) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/audit-logs', { params: { page, ...filters } });
            logs.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load audit logs.';
        } finally {
            loading.value = false;
        }
    }

    return { logs, loading, error, meta, fetchLogs };
});
