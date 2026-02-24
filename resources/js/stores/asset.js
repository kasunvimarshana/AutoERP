import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useAssetStore = defineStore('asset', () => {
    const assets = ref([]);
    const categories = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const assetsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const categoriesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchAssets(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/assets', { params: { page } });
            assets.value = data.data ?? data;
            if (data.meta) assetsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load assets.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchCategories(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/assets/categories', { params: { page } });
            categories.value = data.data ?? data;
            if (data.meta) categoriesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load asset categories.';
        } finally {
            loading.value = false;
        }
    }

    return {
        assets, categories,
        loading, error,
        assetsMeta, categoriesMeta,
        fetchAssets, fetchCategories,
    };
});
