import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useHelpdeskStore = defineStore('helpdesk', () => {
    const tickets = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    const kbArticles = ref([]);
    const kbArticlesLoading = ref(false);
    const kbArticlesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    const kbCategories = ref([]);
    const kbCategoriesLoading = ref(false);
    const kbCategoriesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchTickets(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/helpdesk/tickets', { params: { page } });
            tickets.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load tickets.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchKbArticles(page = 1) {
        kbArticlesLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/helpdesk/kb/articles', { params: { page } });
            kbArticles.value = data.data ?? data;
            if (data.meta) kbArticlesMeta.value = data.meta;
            else if (Array.isArray(data.data ?? data)) {
                kbArticlesMeta.value = { current_page: 1, last_page: 1, total: (data.data ?? data).length };
            }
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load KB articles.';
        } finally {
            kbArticlesLoading.value = false;
        }
    }

    async function fetchKbCategories(page = 1) {
        kbCategoriesLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/helpdesk/kb/categories', { params: { page } });
            kbCategories.value = data.data ?? data;
            if (data.meta) kbCategoriesMeta.value = data.meta;
            else if (Array.isArray(data.data ?? data)) {
                kbCategoriesMeta.value = { current_page: 1, last_page: 1, total: (data.data ?? data).length };
            }
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load KB categories.';
        } finally {
            kbCategoriesLoading.value = false;
        }
    }

    return {
        tickets, loading, error, meta, fetchTickets,
        kbArticles, kbArticlesLoading, kbArticlesMeta, fetchKbArticles,
        kbCategories, kbCategoriesLoading, kbCategoriesMeta, fetchKbCategories,
    };
});
