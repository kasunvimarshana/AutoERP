import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const usePurchaseStore = defineStore('purchase', () => {
    const orders              = ref([]);
    const requisitions        = ref([]);
    const ordersLoading       = ref(false);
    const requisitionsLoading = ref(false);
    const error               = ref(null);
    const meta                = ref({ current_page: 1, last_page: 1, total: 0 });
    const requisitionMeta     = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchOrders(page = 1) {
        ordersLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/purchase/orders', { params: { page } });
            orders.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load purchase orders.';
        } finally {
            ordersLoading.value = false;
        }
    }

    async function fetchRequisitions(page = 1) {
        requisitionsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/purchase/requisitions', { params: { page } });
            requisitions.value = data.data ?? data;
            if (data.meta) requisitionMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load purchase requisitions.';
        } finally {
            requisitionsLoading.value = false;
        }
    }

    return {
        orders, requisitions,
        ordersLoading, requisitionsLoading,
        error, meta, requisitionMeta,
        fetchOrders, fetchRequisitions,
    };
});

