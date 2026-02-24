import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useLogisticsStore = defineStore('logistics', () => {
    const carriers = ref([]);
    const deliveryOrders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const carriersMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const deliveryMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchCarriers(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/logistics/carriers', { params: { page } });
            carriers.value = data.data ?? data;
            if (data.meta) carriersMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load carriers.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchDeliveryOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/logistics/delivery-orders', { params: { page } });
            deliveryOrders.value = data.data ?? data;
            if (data.meta) deliveryMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load delivery orders.';
        } finally {
            loading.value = false;
        }
    }

    return {
        carriers, deliveryOrders,
        loading, error,
        carriersMeta, deliveryMeta,
        fetchCarriers, fetchDeliveryOrders,
    };
});
