import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useSalesStore = defineStore('sales', () => {
    const orders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    // Price lists
    const priceLists = ref([]);
    const priceListsLoading = ref(false);
    const priceListsError = ref(null);
    const priceListsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/sales/orders', { params: { page } });
            orders.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load sales orders.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchPriceLists(page = 1) {
        priceListsLoading.value = true;
        priceListsError.value = null;
        try {
            const { data } = await api.get('/api/v1/sales/price-lists', { params: { page } });
            priceLists.value = data.data ?? data;
            if (data.meta) priceListsMeta.value = data.meta;
        } catch (e) {
            priceListsError.value = e.response?.data?.message ?? 'Failed to load price lists.';
        } finally {
            priceListsLoading.value = false;
        }
    }

    return {
        orders, loading, error, meta, fetchOrders,
        priceLists, priceListsLoading, priceListsError, priceListsMeta, fetchPriceLists,
    };
});
