import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useTaxStore = defineStore('tax', () => {
    const taxRates = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchTaxRates(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/tax/rates', { params: { page } });
            taxRates.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load tax rates.';
        } finally {
            loading.value = false;
        }
    }

    return { taxRates, loading, error, meta, fetchTaxRates };
});
