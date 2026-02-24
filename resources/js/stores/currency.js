import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useCurrencyStore = defineStore('currency', () => {
    const currencies = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    const exchangeRates = ref([]);
    const ratesLoading = ref(false);
    const ratesError = ref(null);
    const ratesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchCurrencies(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/currencies', { params: { page } });
            currencies.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load currencies.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchExchangeRates(page = 1) {
        ratesLoading.value = true;
        ratesError.value = null;
        try {
            const { data } = await api.get('/api/v1/exchange-rates', { params: { page } });
            exchangeRates.value = data.data ?? data;
            if (data.meta) ratesMeta.value = data.meta;
        } catch (e) {
            ratesError.value = e.response?.data?.message ?? 'Failed to load exchange rates.';
        } finally {
            ratesLoading.value = false;
        }
    }

    return {
        currencies, loading, error, meta, fetchCurrencies,
        exchangeRates, ratesLoading, ratesError, ratesMeta, fetchExchangeRates,
    };
});
