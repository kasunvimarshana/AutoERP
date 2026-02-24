import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useInventoryStore = defineStore('inventory', () => {
    const products = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    // Stock valuation state
    const valuation = ref([]);
    const valuationLoading = ref(false);
    const valuationMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    // Lot & serial tracking state
    const lots = ref([]);
    const lotsLoading = ref(false);
    const lotsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchProducts(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/inventory/products', { params: { page } });
            products.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load products.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchValuation(page = 1) {
        valuationLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/inventory/valuation', { params: { page } });
            valuation.value = data.data ?? data;
            if (data.meta) valuationMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load valuation entries.';
        } finally {
            valuationLoading.value = false;
        }
    }

    async function fetchLots(page = 1, filters = {}) {
        lotsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/inventory/lots', { params: { page, ...filters } });
            lots.value = data.data ?? data;
            if (data.meta) lotsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load lots.';
        } finally {
            lotsLoading.value = false;
        }
    }

    // Cycle count state
    const cycleCounts = ref([]);
    const cycleCountsLoading = ref(false);
    const cycleCountsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchCycleCounts(page = 1, filters = {}) {
        cycleCountsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/inventory/cycle-counts', { params: { page, ...filters } });
            cycleCounts.value = data.data ?? data;
            if (data.meta) cycleCountsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load cycle counts.';
        } finally {
            cycleCountsLoading.value = false;
        }
    }

    // Product variants state
    const variants = ref([]);
    const variantsLoading = ref(false);
    const variantsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchVariants(page = 1, filters = {}) {
        variantsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/inventory/variants', { params: { page, ...filters } });
            variants.value = data.data ?? data;
            if (data.meta) variantsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load product variants.';
        } finally {
            variantsLoading.value = false;
        }
    }

    return {
        products, loading, error, meta, fetchProducts,
        valuation, valuationLoading, valuationMeta, fetchValuation,
        lots, lotsLoading, lotsMeta, fetchLots,
        cycleCounts, cycleCountsLoading, cycleCountsMeta, fetchCycleCounts,
        variants, variantsLoading, variantsMeta, fetchVariants,
    };
});
