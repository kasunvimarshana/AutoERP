import { create } from 'zustand';

interface AuthUser {
  id: string;
  name: string;
  email: string;
  tenantId: string;
}

interface AuthState {
  user: AuthUser | null;
  token: string | null;
  isAuthenticated: boolean;
  setAuth: (user: AuthUser, token: string) => void;
  clearAuth: () => void;
}

/**
 * Global authentication state using Zustand.
 * Persists token to localStorage for page-refresh resilience.
 */
export const useAuthStore = create<AuthState>((set) => ({
  user:            JSON.parse(localStorage.getItem('auth_user') || 'null'),
  token:           localStorage.getItem('auth_token'),
  isAuthenticated: !!localStorage.getItem('auth_token'),

  setAuth: (user, token) => {
    localStorage.setItem('auth_user',  JSON.stringify(user));
    localStorage.setItem('auth_token', token);
    set({ user, token, isAuthenticated: true });
  },

  clearAuth: () => {
    localStorage.removeItem('auth_user');
    localStorage.removeItem('auth_token');
    set({ user: null, token: null, isAuthenticated: false });
  },
}));
