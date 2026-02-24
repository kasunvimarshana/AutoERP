import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useLocalisationStore = defineStore('localisation', () => {
    const languagePacks = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchLanguagePacks(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/localisation/language-packs', { params: { page } });
            languagePacks.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load language packs.';
        } finally {
            loading.value = false;
        }
    }

    return { languagePacks, loading, error, meta, fetchLanguagePacks };
});
