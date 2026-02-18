<<<<<<< HEAD
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth'
import type { User, LoginCredentials, RegisterData } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRoles = computed(() => user.value?.roles?.map(r => r.name) || [])
  const userPermissions = computed(() => user.value?.permissions || [])

  // Initialize from localStorage
  const init = () => {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('user')
    
    if (storedToken) {
      token.value = storedToken
    }
    
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser)
      } catch (e) {
        console.error('Failed to parse stored user', e)
=======
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
>>>>>>> kv-erp-001
      }
    }
  }

<<<<<<< HEAD
  // Login
  const login = async (credentials: LoginCredentials) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await authService.login(credentials)
      
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
        
        if (credentials.tenant_id) {
          localStorage.setItem('tenant_id', credentials.tenant_id)
        }
      } else {
        throw new Error(response.message || 'Login failed')
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Register
  const register = async (data: RegisterData) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await authService.register(data)
      
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
        
        if (data.tenant_id) {
          localStorage.setItem('tenant_id', data.tenant_id)
        }
      } else {
        throw new Error(response.message || 'Registration failed')
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Logout
  const logout = async () => {
    try {
      await authService.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      localStorage.removeItem('tenant_id')
    }
  }

  // Fetch current user
  const fetchUser = async () => {
    try {
      const response = await authService.getUser()
      
      if (response.success && response.data) {
        user.value = response.data
        localStorage.setItem('user', JSON.stringify(response.data))
      }
    } catch (err) {
      console.error('Failed to fetch user:', err)
      await logout()
    }
  }

  // Check permission
  const hasPermission = (permission: string): boolean => {
    return userPermissions.value.includes(permission)
  }

  // Check role
  const hasRole = (role: string): boolean => {
    return userRoles.value.includes(role)
  }

  return {
=======
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
>>>>>>> kv-erp-001
    user,
    token,
    loading,
    error,
<<<<<<< HEAD
    isAuthenticated,
    userRoles,
    userPermissions,
    init,
    login,
    register,
    logout,
    fetchUser,
    hasPermission,
    hasRole,
  }
})
=======
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
>>>>>>> kv-erp-001
