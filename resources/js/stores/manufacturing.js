import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useManufacturingStore = defineStore('manufacturing', () => {
    const boms = ref([]);
    const workOrders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const bomsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const workOrdersMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchBoms(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/manufacturing/boms', { params: { page } });
            boms.value = data.data ?? data;
            if (data.meta) bomsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load bills of materials.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchWorkOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/manufacturing/work-orders', { params: { page } });
            workOrders.value = data.data ?? data;
            if (data.meta) workOrdersMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load work orders.';
        } finally {
            loading.value = false;
        }
    }

    return {
        boms, workOrders,
        loading, error,
        bomsMeta, workOrdersMeta,
        fetchBoms, fetchWorkOrders,
    };
});
