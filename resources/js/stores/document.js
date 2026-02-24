import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useDocumentStore = defineStore('document', () => {
    const documents = ref([]);
    const categories = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const documentsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const categoriesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchDocuments(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/documents', { params: { page } });
            documents.value = data.data ?? data;
            if (data.meta) documentsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load documents.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchCategories(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/documents/categories', { params: { page } });
            categories.value = data.data ?? data;
            if (data.meta) categoriesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load document categories.';
        } finally {
            loading.value = false;
        }
    }

    return {
        documents, categories,
        loading, error,
        documentsMeta, categoriesMeta,
        fetchDocuments, fetchCategories,
    };
});
