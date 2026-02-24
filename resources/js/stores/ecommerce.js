import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useECommerceStore = defineStore('ecommerce', () => {
    const products = ref([]);
    const orders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const productsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const ordersMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchProducts(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/ecommerce/products', { params: { page } });
            products.value = data.data ?? data;
            if (data.meta) productsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load product listings.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/ecommerce/orders', { params: { page } });
            orders.value = data.data ?? data;
            if (data.meta) ordersMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load e-commerce orders.';
        } finally {
            loading.value = false;
        }
    }

    return {
        products, orders,
        loading, error,
        productsMeta, ordersMeta,
        fetchProducts, fetchOrders,
    };
});
