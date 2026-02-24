import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useUserStore = defineStore('user', () => {
    const users = ref([]);
    const roles = ref([]);
    const loading = ref(false);
    const rolesLoading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });
    const rolesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchUsers(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/users', { params: { page } });
            users.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load users.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchRoles(page = 1) {
        rolesLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/roles', { params: { page } });
            roles.value = data.data ?? data;
            if (data.meta) rolesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load roles.';
        } finally {
            rolesLoading.value = false;
        }
    }

    return {
        users, roles,
        loading, rolesLoading, error,
        meta, rolesMeta,
        fetchUsers, fetchRoles,
    };
});
