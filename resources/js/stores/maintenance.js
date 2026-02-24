import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useMaintenanceStore = defineStore('maintenance', () => {
    const equipment = ref([]);
    const requests = ref([]);
    const orders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const equipmentMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const requestsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const ordersMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchEquipment(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/maintenance/equipment', { params: { page } });
            equipment.value = data.data ?? data;
            if (data.meta) equipmentMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load equipment.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchRequests(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/maintenance/requests', { params: { page } });
            requests.value = data.data ?? data;
            if (data.meta) requestsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load maintenance requests.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/maintenance/orders', { params: { page } });
            orders.value = data.data ?? data;
            if (data.meta) ordersMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load maintenance orders.';
        } finally {
            loading.value = false;
        }
    }

    return {
        equipment, requests, orders,
        loading, error,
        equipmentMeta, requestsMeta, ordersMeta,
        fetchEquipment, fetchRequests, fetchOrders,
    };
});
