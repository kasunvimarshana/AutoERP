import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useTenantStore = defineStore('tenant', () => {
    const tenants = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchTenants(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/tenants', { params: { page } });
            tenants.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load tenants.';
        } finally {
            loading.value = false;
        }
    }

    return {
        tenants,
        loading,
        error,
        meta,
        fetchTenants,
    };
});
