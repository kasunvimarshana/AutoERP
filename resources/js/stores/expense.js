import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useExpenseStore = defineStore('expense', () => {
    const categories = ref([]);
    const claims = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const categoriesMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const claimsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchCategories(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/expense/categories', { params: { page } });
            categories.value = data.data ?? data;
            if (data.meta) categoriesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load expense categories.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchClaims(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/expense/claims', { params: { page } });
            claims.value = data.data ?? data;
            if (data.meta) claimsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load expense claims.';
        } finally {
            loading.value = false;
        }
    }

    return {
        categories, claims,
        loading, error,
        categoriesMeta, claimsMeta,
        fetchCategories, fetchClaims,
    };
});
