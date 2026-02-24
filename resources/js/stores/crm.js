import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useCrmStore = defineStore('crm', () => {
    const leads = ref([]);
    const opportunities = ref([]);
    const contacts = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const leadsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const opportunitiesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchLeads(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/crm/leads', { params: { page } });
            leads.value = data.data ?? data;
            if (data.meta) leadsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load leads.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchOpportunities(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/crm/opportunities', { params: { page } });
            opportunities.value = data.data ?? data;
            if (data.meta) opportunitiesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load opportunities.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchContacts(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/crm/contacts', { params: { page } });
            contacts.value = data.data ?? data;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load contacts.';
        } finally {
            loading.value = false;
        }
    }

    return {
        leads, opportunities, contacts,
        loading, error,
        leadsMeta, opportunitiesMeta,
        fetchLeads, fetchOpportunities, fetchContacts,
    };
});
