import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/api';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const token = ref(localStorage.getItem('token') || null);
  const loading = ref(false);

  const isAuthenticated = computed(() => !!token.value);

  const setToken = (newToken) => {
    token.value = newToken;
    if (newToken) {
      localStorage.setItem('token', newToken);
      api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
    } else {
      localStorage.removeItem('token');
      delete api.defaults.headers.common['Authorization'];
    }
  };

  const login = async (credentials) => {
    loading.value = true;
    try {
      const response = await api.post('/api/v1/auth/login', credentials);
      const { token: authToken, user: userData } = response.data.data;
      
      user.value = userData;
      setToken(authToken);
      
      return true;
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const register = async (data) => {
    loading.value = true;
    try {
      const response = await api.post('/api/v1/auth/register', data);
      const { token: authToken, user: userData } = response.data.data;
      
      user.value = userData;
      setToken(authToken);
      
      return true;
    } catch (error) {
      console.error('Register error:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const logout = async () => {
    loading.value = true;
    try {
      await api.post('/api/v1/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      user.value = null;
      setToken(null);
      loading.value = false;
    }
  };

  const checkAuth = async () => {
    if (!token.value) return false;
    
    loading.value = true;
    try {
      const response = await api.get('/api/v1/auth/me');
      user.value = response.data.data;
      return true;
    } catch (error) {
      console.error('Check auth error:', error);
      user.value = null;
      setToken(null);
      return false;
    } finally {
      loading.value = false;
    }
  };

  // Initialize token in axios if it exists
  if (token.value) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
    checkAuth,
  };
});
