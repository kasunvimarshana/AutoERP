import { defineStore } from 'pinia';
import { ref } from 'vue';
import { tenantService } from '../services/tenantService';

/**
 * Tenant Store
 * 
 * Manages Tenant module state (tenants, organizations, context switching)
 */
export const useTenantStore = defineStore('tenant', () => {
    // State
    const tenants = ref([]);
    const organizations = ref([]);
    const currentContext = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Tenants
    async function fetchTenants(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.getAll(params);
            tenants.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createTenant(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.create(data);
            tenants.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateTenant(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.update(id, data);
            const index = tenants.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tenants.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteTenant(id) {
        loading.value = true;
        error.value = null;
        try {
            await tenantService.tenants.delete(id);
            tenants.value = tenants.value.filter(t => t.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function activateTenant(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.activate(id);
            const index = tenants.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tenants.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deactivateTenant(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.deactivate(id);
            const index = tenants.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tenants.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function suspendTenant(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.suspend(id);
            const index = tenants.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tenants.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchTenantSettings(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.getSettings(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateTenantSettings(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.tenants.updateSettings(id, data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Organizations
    async function fetchOrganizations(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.getAll(params);
            organizations.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createOrganization(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.create(data);
            organizations.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateOrganization(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.update(id, data);
            const index = organizations.value.findIndex(o => o.id === id);
            if (index !== -1) {
                organizations.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteOrganization(id) {
        loading.value = true;
        error.value = null;
        try {
            await tenantService.organizations.delete(id);
            organizations.value = organizations.value.filter(o => o.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrganizationHierarchy(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.getHierarchy(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrganizationChildren(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.getChildren(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrganizationUsers(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.getUsers(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function addUserToOrganization(id, userId, data = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.organizations.addUser(id, userId, data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function removeUserFromOrganization(id, userId) {
        loading.value = true;
        error.value = null;
        try {
            await tenantService.organizations.removeUser(id, userId);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Context Switching
    async function switchTenant(tenantId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.switchTenant(tenantId);
            currentContext.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function switchOrganization(organizationId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.switchOrganization(organizationId);
            currentContext.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchCurrentContext() {
        loading.value = true;
        error.value = null;
        try {
            const response = await tenantService.getCurrentContext();
            currentContext.value = response.data;
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
        tenants,
        organizations,
        currentContext,
        loading,
        error,

        // Actions - Tenants
        fetchTenants,
        createTenant,
        updateTenant,
        deleteTenant,
        activateTenant,
        deactivateTenant,
        suspendTenant,
        fetchTenantSettings,
        updateTenantSettings,

        // Actions - Organizations
        fetchOrganizations,
        createOrganization,
        updateOrganization,
        deleteOrganization,
        fetchOrganizationHierarchy,
        fetchOrganizationChildren,
        fetchOrganizationUsers,
        addUserToOrganization,
        removeUserFromOrganization,

        // Actions - Context Switching
        switchTenant,
        switchOrganization,
        fetchCurrentContext,
    };
});
