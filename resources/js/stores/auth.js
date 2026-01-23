import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import authService from '@/services/auth';
import { STORAGE_KEYS } from '@/config/constants';

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null);
  const token = ref(null);
  const loading = ref(false);
  const error = ref(null);

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const userName = computed(() => user.value?.name || '');
  const userEmail = computed(() => user.value?.email || '');
  const userRoles = computed(() => user.value?.roles || []);
  const userPermissions = computed(() => user.value?.permissions || []);

  // Private helper functions
  
  /**
   * Store auth data in state and localStorage
   * @private
   */
  function storeAuthData(authToken, userData) {
    token.value = authToken;
    user.value = userData;
    
    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, authToken);
    localStorage.setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(userData));
  }
  
  // Actions
  
  /**
   * Initialize auth state from localStorage
   */
  function initAuth() {
    const storedToken = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    const storedUser = localStorage.getItem(STORAGE_KEYS.AUTH_USER);
    
    if (storedToken && storedUser) {
      token.value = storedToken;
      try {
        user.value = JSON.parse(storedUser);
      } catch (e) {
        console.error('Failed to parse stored user:', e);
        clearAuth();
      }
    }
  }

  /**
   * Check authentication status
   */
  async function checkAuth() {
    if (!token.value) {
      initAuth();
    }

    if (token.value) {
      try {
        const response = await authService.me();
        if (response.success && response.data) {
          user.value = response.data;
          localStorage.setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(response.data));
        }
      } catch (err) {
        console.error('Auth check failed:', err);
        clearAuth();
      }
    }
  }

  /**
   * Register a new user
   */
  async function register(credentials) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.register(credentials);
      
      if (response.success && response.data) {
        storeAuthData(response.data.token, response.data.user);
        
        return response;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Login user
   */
  async function login(credentials) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.login(credentials);
      
      if (response.success && response.data) {
        storeAuthData(response.data.token, response.data.user);
        
        return response;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Logout from current device
   */
  async function logout() {
    loading.value = true;
    error.value = null;

    try {
      await authService.logout();
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      clearAuth();
      loading.value = false;
    }
  }

  /**
   * Logout from all devices
   */
  async function logoutAll() {
    loading.value = true;
    error.value = null;

    try {
      await authService.logoutAll();
    } catch (err) {
      console.error('Logout all error:', err);
    } finally {
      clearAuth();
      loading.value = false;
    }
  }

  /**
   * Refresh authentication token
   */
  async function refresh() {
    try {
      const response = await authService.refresh();
      
      if (response.success && response.data) {
        storeAuthData(response.data.token, response.data.user);
        
        return response;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Token refresh failed';
      clearAuth();
      throw err;
    }
  }

  /**
   * Request password reset
   */
  async function forgotPassword(email) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.forgotPassword(email);
      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Password reset request failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Reset password with token
   */
  async function resetPassword(data) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.resetPassword(data);
      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Password reset failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Verify email address
   */
  async function verifyEmail(id, hash, queryParams) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.verifyEmail(id, hash, queryParams);
      
      // Refresh user data after verification
      if (response.success) {
        await checkAuth();
      }
      
      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Email verification failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Resend email verification
   */
  async function resendVerification() {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.resendVerification();
      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to resend verification email';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Check if user has a specific permission
   */
  function hasPermission(permission) {
    return userPermissions.value.includes(permission);
  }

  /**
   * Check if user has a specific role
   */
  function hasRole(role) {
    return userRoles.value.includes(role);
  }

  /**
   * Clear authentication state
   */
  function clearAuth() {
    token.value = null;
    user.value = null;
    localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
    localStorage.removeItem(STORAGE_KEYS.AUTH_USER);
  }

  // Initialize on store creation
  initAuth();

  return {
    // State
    user,
    token,
    loading,
    error,
    // Getters
    isAuthenticated,
    userName,
    userEmail,
    userRoles,
    userPermissions,
    // Actions
    checkAuth,
    register,
    login,
    logout,
    logoutAll,
    refresh,
    forgotPassword,
    resetPassword,
    verifyEmail,
    resendVerification,
    hasPermission,
    hasRole,
    clearAuth,
  };
});
