import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useRecruitmentStore = defineStore('recruitment', () => {
    const positions = ref([]);
    const applications = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const positionsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const applicationsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchPositions(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/recruitment/positions', { params: { page } });
            positions.value = data.data ?? data;
            if (data.meta) positionsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load job positions.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchApplications(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/recruitment/applications', { params: { page } });
            applications.value = data.data ?? data;
            if (data.meta) applicationsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load job applications.';
        } finally {
            loading.value = false;
        }
    }

    return {
        positions, applications,
        loading, error,
        positionsMeta, applicationsMeta,
        fetchPositions, fetchApplications,
    };
});
