import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useSubscriptionStore = defineStore('subscription', () => {
    const plans = ref([]);
    const subscriptions = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const plansMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const subscriptionsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchPlans(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/subscriptions/plans', { params: { page } });
            plans.value = data.data ?? data;
            if (data.meta) plansMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load subscription plans.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchSubscriptions(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/subscriptions', { params: { page } });
            subscriptions.value = data.data ?? data;
            if (data.meta) subscriptionsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load subscriptions.';
        } finally {
            loading.value = false;
        }
    }

    return {
        plans, subscriptions,
        loading, error,
        plansMeta, subscriptionsMeta,
        fetchPlans, fetchSubscriptions,
    };
});
