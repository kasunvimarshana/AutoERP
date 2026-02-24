import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useLeaveStore = defineStore('leave', () => {
    const leaveTypes = ref([]);
    const leaveRequests = ref([]);
    const allocations = ref([]);
    const loading = ref(false);
    const allocationsLoading = ref(false);
    const error = ref(null);
    const typesMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const requestsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const allocationsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchLeaveTypes(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/leave/types', { params: { page } });
            leaveTypes.value = data.data ?? data;
            if (data.meta) typesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load leave types.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchLeaveRequests(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/leave/requests', { params: { page } });
            leaveRequests.value = data.data ?? data;
            if (data.meta) requestsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load leave requests.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchAllocations(page = 1) {
        allocationsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/leave/allocations', { params: { page } });
            allocations.value = data.data ?? data;
            if (data.meta) allocationsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load leave allocations.';
        } finally {
            allocationsLoading.value = false;
        }
    }

    return {
        leaveTypes, leaveRequests, allocations,
        loading, allocationsLoading, error,
        typesMeta, requestsMeta, allocationsMeta,
        fetchLeaveTypes, fetchLeaveRequests, fetchAllocations,
    };
});
