import { defineStore } from 'pinia';
import { ref } from 'vue';
import { authService } from '../services/authService';

/**
 * Auth Management Store
 * Handles user and role management (not authentication)
 */
export const useAuthManagementStore = defineStore('authManagement', () => {
    // State
    const users = ref([]);
    const roles = ref([]);
    const permissions = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Users
    const fetchUsers = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.getAll(params);
            users.value = response.data.data || response.data || [];
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createUser = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.create(data);
            await fetchUsers();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateUser = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.update(id, data);
            await fetchUsers();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteUser = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.delete(id);
            await fetchUsers();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const activateUser = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.activate(id);
            await fetchUsers();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deactivateUser = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.deactivate(id);
            await fetchUsers();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const assignRolesToUser = async (userId, roleIds) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.users.assignRoles(userId, roleIds);
            await fetchUsers();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // Roles
    const fetchRoles = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.roles.getAll(params);
            roles.value = response.data.data || response.data || [];
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createRole = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.roles.create(data);
            await fetchRoles();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateRole = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.roles.update(id, data);
            await fetchRoles();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteRole = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.roles.delete(id);
            await fetchRoles();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const assignPermissionsToRole = async (roleId, permissionIds) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.roles.assignPermissions(roleId, permissionIds);
            await fetchRoles();
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // Permissions
    const fetchPermissions = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await authService.permissions.getAll(params);
            permissions.value = response.data.data || response.data || [];
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        // State
        users,
        roles,
        permissions,
        loading,
        error,

        // Users
        fetchUsers,
        createUser,
        updateUser,
        deleteUser,
        activateUser,
        deactivateUser,
        assignRolesToUser,

        // Roles
        fetchRoles,
        createRole,
        updateRole,
        deleteRole,
        assignPermissionsToRole,

        // Permissions
        fetchPermissions,
    };
});

export default useAuthManagementStore;
