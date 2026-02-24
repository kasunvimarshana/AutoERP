import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useBudgetStore = defineStore('budget', () => {
    const budgets = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    const varianceReport = ref(null);
    const varianceLoading = ref(false);
    const varianceError = ref(null);

    async function fetchBudgets(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/budget/budgets', { params: { page } });
            budgets.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load budgets.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchVarianceReport(budgetId) {
        varianceLoading.value = true;
        varianceError.value = null;
        varianceReport.value = null;
        try {
            const { data } = await api.get(`/api/v1/budget/budgets/${budgetId}/variance`);
            varianceReport.value = data.data ?? data;
        } catch (e) {
            varianceError.value = e.response?.data?.message ?? 'Failed to load variance report.';
        } finally {
            varianceLoading.value = false;
        }
    }

    return { budgets, loading, error, meta, fetchBudgets, varianceReport, varianceLoading, varianceError, fetchVarianceReport };
});
