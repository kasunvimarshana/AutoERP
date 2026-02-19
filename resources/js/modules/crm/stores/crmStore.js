import { defineStore } from 'pinia';
import { ref } from 'vue';
import { crmService } from '../services/crmService';

/**
 * CRM Store
 * 
 * Manages CRM module state (customers, leads, opportunities)
 */
export const useCrmStore = defineStore('crm', () => {
    // State
    const customers = ref([]);
    const leads = ref([]);
    const opportunities = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Customers
    async function fetchCustomers(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.customers.getAll(params);
            customers.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createCustomer(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.customers.create(data);
            customers.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateCustomer(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.customers.update(id, data);
            const index = customers.value.findIndex(c => c.id === id);
            if (index !== -1) {
                customers.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteCustomer(id) {
        loading.value = true;
        error.value = null;
        try {
            await crmService.customers.delete(id);
            customers.value = customers.value.filter(c => c.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Leads
    async function fetchLeads(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.leads.getAll(params);
            leads.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createLead(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.leads.create(data);
            leads.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateLead(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.leads.update(id, data);
            const index = leads.value.findIndex(l => l.id === id);
            if (index !== -1) {
                leads.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteLead(id) {
        loading.value = true;
        error.value = null;
        try {
            await crmService.leads.delete(id);
            leads.value = leads.value.filter(l => l.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function convertLeadToCustomer(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.leads.convertToCustomer(id);
            leads.value = leads.value.filter(l => l.id !== id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Opportunities
    async function fetchOpportunities(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.opportunities.getAll(params);
            opportunities.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createOpportunity(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.opportunities.create(data);
            opportunities.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateOpportunity(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.opportunities.update(id, data);
            const index = opportunities.value.findIndex(o => o.id === id);
            if (index !== -1) {
                opportunities.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteOpportunity(id) {
        loading.value = true;
        error.value = null;
        try {
            await crmService.opportunities.delete(id);
            opportunities.value = opportunities.value.filter(o => o.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateOpportunityStage(id, stage) {
        loading.value = true;
        error.value = null;
        try {
            const response = await crmService.opportunities.updateStage(id, stage);
            const index = opportunities.value.findIndex(o => o.id === id);
            if (index !== -1) {
                opportunities.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        customers,
        leads,
        opportunities,
        loading,
        error,

        // Actions - Customers
        fetchCustomers,
        createCustomer,
        updateCustomer,
        deleteCustomer,

        // Actions - Leads
        fetchLeads,
        createLead,
        updateLead,
        deleteLead,
        convertLeadToCustomer,

        // Actions - Opportunities
        fetchOpportunities,
        createOpportunity,
        updateOpportunity,
        deleteOpportunity,
        updateOpportunityStage,
    };
});
