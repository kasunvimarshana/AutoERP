import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useFleetStore = defineStore('fleet', () => {
    const vehicles = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchVehicles(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/fleet/vehicles', { params: { page } });
            vehicles.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load vehicles.';
        } finally {
            loading.value = false;
        }
    }

    return { vehicles, loading, error, meta, fetchVehicles };
});
