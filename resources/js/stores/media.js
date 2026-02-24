import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useMediaStore = defineStore('media', () => {
    const files = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchFiles(page = 1, filters = {}) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/media', { params: { page, ...filters } });
            files.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load media files.';
        } finally {
            loading.value = false;
        }
    }

    async function remove(id) {
        try {
            await api.delete(`/api/v1/media/${id}`);
            files.value = files.value.filter((f) => f.id !== id);
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to delete media file.';
        }
    }

    return {
        files,
        loading,
        error,
        meta,
        fetchFiles,
        remove,
    };
});
