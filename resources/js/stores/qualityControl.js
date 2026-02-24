import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useQualityControlStore = defineStore('qualityControl', () => {
    const qualityPoints = ref([]);
    const inspections = ref([]);
    const alerts = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pointsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const inspectionsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const alertsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchQualityPoints(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/qc/quality-points', { params: { page } });
            qualityPoints.value = data.data ?? data;
            if (data.meta) pointsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load quality points.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchInspections(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/qc/inspections', { params: { page } });
            inspections.value = data.data ?? data;
            if (data.meta) inspectionsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load inspections.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchAlerts(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/qc/alerts', { params: { page } });
            alerts.value = data.data ?? data;
            if (data.meta) alertsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load quality alerts.';
        } finally {
            loading.value = false;
        }
    }

    return {
        qualityPoints, inspections, alerts,
        loading, error,
        pointsMeta, inspectionsMeta, alertsMeta,
        fetchQualityPoints, fetchInspections, fetchAlerts,
    };
});
