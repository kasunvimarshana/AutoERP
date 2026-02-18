import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/api/auth';
import { apiClient } from '@/api/client';
import type { User, LoginCredentials, RegisterData } from '@/types';
import { useTenantStore } from './tenant';
import { configureEcho, setEcho, disconnectEcho } from '@/config/echo';

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null);
  const token = ref<string | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const userPermissions = computed(() => user.value?.permissions || []);
  const userRole = computed(() => user.value?.role || null);

  // Actions
  async function initialize() {
    const storedToken = apiClient.getAuthToken();
    
    if (storedToken) {
      token.value = storedToken;
      try {
        await fetchCurrentUser();
        // Initialize Echo if token exists
        initializeEcho(storedToken);
      } catch (err) {
        clearAuth();
      }
    }
  }

  async function login(credentials: LoginCredentials) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authApi.login(credentials);
      
      token.value = response.access_token;
      user.value = response.user;
      
      // Set token with expiry time (default 1 day if not provided)
      const expiresIn = response.expires_in || 86400;
      apiClient.setAuthToken(response.access_token, expiresIn);
      apiClient.setTenantId(response.tenant.id);

      // Initialize tenant store
      const tenantStore = useTenantStore();
      tenantStore.setTenant(response.tenant);

      // Initialize Laravel Echo for real-time notifications
      initializeEcho(response.access_token);

      return response;
    } catch (err: any) {
      error.value = err.message || 'Login failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function register(data: RegisterData) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authApi.register(data);
      
      token.value = response.access_token;
      user.value = response.user;
      
      // Set token with expiry time (default 1 day if not provided)
      const expiresIn = response.expires_in || 86400;
      apiClient.setAuthToken(response.access_token, expiresIn);
      apiClient.setTenantId(response.tenant.id);

      // Initialize tenant store
      const tenantStore = useTenantStore();
      tenantStore.setTenant(response.tenant);

      // Initialize Laravel Echo for real-time notifications
      initializeEcho(response.access_token);

      return response;
    } catch (err: any) {
      error.value = err.message || 'Registration failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    loading.value = true;

    try {
      await authApi.logout();
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      clearAuth();
      loading.value = false;
    }
  }

  async function fetchCurrentUser() {
    try {
      user.value = await authApi.getCurrentUser();
      return user.value;
    } catch (err) {
      throw err;
    }
  }

  function clearAuth() {
    user.value = null;
    token.value = null;
    apiClient.clearAuth();

    // Disconnect Laravel Echo
    disconnectEcho();

    // Clear tenant store
    const tenantStore = useTenantStore();
    tenantStore.clearTenant();
  }

  function hasPermission(permission: string): boolean {
    return userPermissions.value.includes(permission) || userPermissions.value.includes('*');
  }

  function hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(permission => hasPermission(permission));
  }

  function hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => hasPermission(permission));
  }

  function hasRole(role: string): boolean {
    return userRole.value === role;
  }

  /**
   * Initialize Laravel Echo for WebSocket connections
   */
  function initializeEcho(authToken: string) {
    try {
      const echo = configureEcho(authToken);
      setEcho(echo);
      window.Echo = echo;
      console.log('Laravel Echo initialized successfully');
    } catch (error) {
      console.error('Failed to initialize Laravel Echo:', error);
      // Show user-friendly notification that real-time features are unavailable
      console.warn('Real-time notifications will fall back to polling. WebSocket connection could not be established.');
    }
  }

  return {
    // State
    user,
    token,
    loading,
    error,
    // Getters
    isAuthenticated,
    userPermissions,
    userRole,
    // Actions
    initialize,
    login,
    register,
    logout,
    fetchCurrentUser,
    clearAuth,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
  };
});
