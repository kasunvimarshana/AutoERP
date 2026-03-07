import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { User, AuthTokens, RegisterData } from '../types';
import { authApi } from '../api/auth';
import toast from 'react-hot-toast';

interface AuthContextType {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string, tenantId: number) => Promise<void>;
  logout: () => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  hasPermission: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
  refreshToken: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
};

const TOKEN_KEY = 'kv_access_token';
const REFRESH_KEY = 'kv_refresh_token';
const USER_KEY = 'kv_user';

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(() => {
    const stored = localStorage.getItem(USER_KEY);
    return stored ? JSON.parse(stored) : null;
  });
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY));
  const [isLoading, setIsLoading] = useState(false);

  const isAuthenticated = !!token && !!user;

  const saveTokens = (tokens: AuthTokens) => {
    localStorage.setItem(TOKEN_KEY, tokens.access_token);
    if (tokens.refresh_token) {
      localStorage.setItem(REFRESH_KEY, tokens.refresh_token);
    }
    setToken(tokens.access_token);
  };

  const clearAuth = () => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(REFRESH_KEY);
    localStorage.removeItem(USER_KEY);
    setToken(null);
    setUser(null);
  };

  const login = async (email: string, password: string, tenantId: number) => {
    setIsLoading(true);
    try {
      const response = await authApi.login({ email, password, tenant_id: tenantId });
      saveTokens(response.tokens);
      setUser(response.user);
      localStorage.setItem(USER_KEY, JSON.stringify(response.user));
      toast.success(`Welcome back, ${response.user.name}!`);
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    try {
      if (token) await authApi.logout();
    } catch {}
    clearAuth();
    toast.success('Logged out successfully');
  };

  const register = async (data: RegisterData) => {
    setIsLoading(true);
    try {
      const response = await authApi.register(data);
      saveTokens(response.tokens);
      setUser(response.user);
      localStorage.setItem(USER_KEY, JSON.stringify(response.user));
      toast.success('Account created successfully!');
    } finally {
      setIsLoading(false);
    }
  };

  const refreshToken = useCallback(async () => {
    const storedRefresh = localStorage.getItem(REFRESH_KEY);
    if (!storedRefresh) { clearAuth(); return; }
    try {
      const response = await authApi.refresh(storedRefresh);
      saveTokens(response.tokens);
    } catch {
      clearAuth();
    }
  }, []);

  const hasPermission = (permission: string): boolean => {
    if (!user) return false;
    if (user.role === 'admin') return true;
    return user.permissions.includes(permission);
  };

  const hasRole = (role: string): boolean => {
    if (!user) return false;
    return user.role === role;
  };

  useEffect(() => {
    if (!isAuthenticated) return;
    const interval = setInterval(refreshToken, 25 * 60 * 1000);
    return () => clearInterval(interval);
  }, [isAuthenticated, refreshToken]);

  return (
    <AuthContext.Provider value={{ user, token, isAuthenticated, isLoading, login, logout, register, hasPermission, hasRole, refreshToken }}>
      {children}
    </AuthContext.Provider>
  );
};

export default AuthContext;
