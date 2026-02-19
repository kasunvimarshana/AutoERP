import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '../services/apiClient';

/**
 * Auth Store
 * 
 * Manages authentication state:
 * - User data
 * - JWT tokens
 * - Tenant/Organization context
 * - Permissions (RBAC/ABAC)
 */
export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref(null);
    const token = ref(localStorage.getItem('jwt_token'));
    const refreshToken = ref(localStorage.getItem('refresh_token'));
    const tenantId = ref(localStorage.getItem('tenant_id'));
    const organizationId = ref(localStorage.getItem('organization_id'));
    const permissions = ref([]);
    const roles = ref([]);

    // Getters
    const isAuthenticated = computed(() => !!token.value);
    const currentUser = computed(() => user.value);
    const currentTenant = computed(() => tenantId.value);
    const currentOrganization = computed(() => organizationId.value);

    // Check if user has permission
    const hasPermission = computed(() => (permission) => {
        return permissions.value.includes(permission);
    });

    // Check if user has role
    const hasRole = computed(() => (role) => {
        return roles.value.includes(role);
    });

    // Check if user has any of the permissions
    const hasAnyPermission = computed(() => (permissionList) => {
        return permissionList.some(p => permissions.value.includes(p));
    });

    // Check if user has all permissions
    const hasAllPermissions = computed(() => (permissionList) => {
        return permissionList.every(p => permissions.value.includes(p));
    });

    // Actions
    async function login(credentials) {
        try {
            const response = await apiClient.post('/auth/login', credentials);
            const { user: userData, token: jwtToken, refresh_token, tenant, organization, permissions: userPermissions, roles: userRoles } = response.data.data;

            // Store tokens
            token.value = jwtToken;
            refreshToken.value = refresh_token;
            localStorage.setItem('jwt_token', jwtToken);
            localStorage.setItem('refresh_token', refresh_token);

            // Store user data
            user.value = userData;
            
            // Store context
            tenantId.value = tenant.id;
            organizationId.value = organization?.id;
            localStorage.setItem('tenant_id', tenant.id);
            if (organization?.id) {
                localStorage.setItem('organization_id', organization.id);
            }

            // Store permissions and roles
            permissions.value = userPermissions || [];
            roles.value = userRoles || [];

            return true;
        } catch (error) {
            console.error('Login failed:', error);
            throw error;
        }
    }

    async function logout() {
        try {
            await apiClient.post('/auth/logout');
        } catch (error) {
            console.error('Logout failed:', error);
        } finally {
            // Clear state
            user.value = null;
            token.value = null;
            refreshToken.value = null;
            tenantId.value = null;
            organizationId.value = null;
            permissions.value = [];
            roles.value = [];

            // Clear localStorage
            localStorage.clear();
        }
    }

    async function fetchUser() {
        try {
            const response = await apiClient.get('/auth/me');
            const { user: userData, permissions: userPermissions, roles: userRoles } = response.data.data;
            
            user.value = userData;
            permissions.value = userPermissions || [];
            roles.value = userRoles || [];

            return user.value;
        } catch (error) {
            console.error('Failed to fetch user:', error);
            throw error;
        }
    }

    async function switchOrganization(newOrganizationId) {
        organizationId.value = newOrganizationId;
        localStorage.setItem('organization_id', newOrganizationId);
        
        // Fetch updated permissions for new organization
        await fetchUser();
    }

    return {
        // State
        user,
        token,
        tenantId,
        organizationId,
        permissions,
        roles,

        // Getters
        isAuthenticated,
        currentUser,
        currentTenant,
        currentOrganization,
        hasPermission,
        hasRole,
        hasAnyPermission,
        hasAllPermissions,

        // Actions
        login,
        logout,
        fetchUser,
        switchOrganization,
    };
});
