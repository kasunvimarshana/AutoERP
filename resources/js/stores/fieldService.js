import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useFieldServiceStore = defineStore('fieldService', () => {
    const teams = ref([]);
    const orders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const teamsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const ordersMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchTeams(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/field-service/teams', { params: { page } });
            teams.value = data.data ?? data;
            if (data.meta) teamsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load service teams.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/field-service/orders', { params: { page } });
            orders.value = data.data ?? data;
            if (data.meta) ordersMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load service orders.';
        } finally {
            loading.value = false;
        }
    }

    return {
        teams, orders,
        loading, error,
        teamsMeta, ordersMeta,
        fetchTeams, fetchOrders,
    };
});
