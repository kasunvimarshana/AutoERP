import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import http from '@/services/http';
import type { AuthUser, LoginResponse } from '@/types/index';

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('jwt_token') ?? null);
  const user = ref<AuthUser | null>(null);

  const isAuthenticated = computed<boolean>(() => token.value !== null);

  const hasPermission = (permission: string): boolean => {
    if (!user.value) return false;
    // super-admin bypass
    if (user.value.roles?.some((r) => r.name === 'super-admin')) return true;
    return user.value.permissions?.includes(permission) ?? false;
  };

  const hasRole = (role: string): boolean => {
    return user.value?.roles?.some((r) => r.name === role) ?? false;
  };

  function setToken(jwt: string | null): void {
    token.value = jwt;
    if (jwt) {
      localStorage.setItem('jwt_token', jwt);
    } else {
      localStorage.removeItem('jwt_token');
    }
  }

  async function login(email: string, password: string): Promise<LoginResponse> {
    const { data } = await http.post<LoginResponse>('/auth/login', { email, password });
    setToken(data.access_token);
    user.value = data.user ?? null;
    return data;
  }

  async function logout(): Promise<void> {
    try {
      await http.post('/auth/logout');
    } finally {
      setToken(null);
      user.value = null;
    }
  }

  async function fetchMe(): Promise<void> {
    if (!token.value) return;
    const { data } = await http.get<AuthUser>('/auth/me');
    user.value = data;
  }

  async function refresh(): Promise<LoginResponse> {
    const { data } = await http.post<LoginResponse>('/auth/refresh');
    setToken(data.access_token);
    return data;
  }

  return {
    token,
    user,
    isAuthenticated,
    hasPermission,
    hasRole,
    login,
    logout,
    fetchMe,
    refresh,
  };
});
