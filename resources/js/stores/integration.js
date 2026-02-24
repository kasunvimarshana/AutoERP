import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useIntegrationStore = defineStore('integration', () => {
    const webhooks = ref([]);
    const apiKeys = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const webhooksMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const apiKeysMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchWebhooks(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/integration/webhooks', { params: { page } });
            webhooks.value = data.data ?? data;
            if (data.meta) webhooksMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load webhooks.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchApiKeys(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/integration/api-keys', { params: { page } });
            apiKeys.value = data.data ?? data;
            if (data.meta) apiKeysMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load API keys.';
        } finally {
            loading.value = false;
        }
    }

    return {
        webhooks, apiKeys,
        loading, error,
        webhooksMeta, apiKeysMeta,
        fetchWebhooks, fetchApiKeys,
    };
});
