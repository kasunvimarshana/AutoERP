import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useReportingStore = defineStore('reporting', () => {
    const dashboards = ref([]);
    const reports = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const dashboardsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const reportsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchDashboards(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/reporting/dashboards', { params: { page } });
            dashboards.value = data.data ?? data;
            if (data.meta) dashboardsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load reporting dashboards.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchReports(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/reporting/reports', { params: { page } });
            reports.value = data.data ?? data;
            if (data.meta) reportsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load reports.';
        } finally {
            loading.value = false;
        }
    }

    return {
        dashboards, reports,
        loading, error,
        dashboardsMeta, reportsMeta,
        fetchDashboards, fetchReports,
    };
});
