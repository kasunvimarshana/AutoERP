import React, { createContext, useCallback, useEffect, useState } from 'react';
import { authApi } from '@/api/auth';
import { tokenStorage, tenantStorage } from '@/api/axios';
import type { User, LoginCredentials, RegisterData } from '@/types';

export interface AuthContextValue {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  hasRole: (role: string) => boolean;
  hasPermission: (permission: string) => boolean;
  hasAnyRole: (roles: string[]) => boolean;
  hasAnyPermission: (permissions: string[]) => boolean;
}

export const AuthContext = createContext<AuthContextValue | null>(null);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(tokenStorage.get());
  const [isLoading, setIsLoading] = useState<boolean>(true);

  const refreshUser = useCallback(async () => {
    try {
      const me = await authApi.me();
      setUser(me);
    } catch {
      setUser(null);
      setToken(null);
      tokenStorage.remove();
      tenantStorage.remove();
    }
  }, []);

  // Bootstrap: load user if token present
  useEffect(() => {
    const storedToken = tokenStorage.get();
    if (storedToken) {
      setToken(storedToken);
      refreshUser().finally(() => setIsLoading(false));
    } else {
      setIsLoading(false);
    }
  }, [refreshUser]);

  const login = useCallback(async (credentials: LoginCredentials) => {
    const response = await authApi.login(credentials);
    const accessToken = response.token.access_token;
    tokenStorage.set(accessToken);
    if (credentials.tenant_id) {
      tenantStorage.set(String(credentials.tenant_id));
    }
    setToken(accessToken);
    setUser(response.user);
  }, []);

  const register = useCallback(async (data: RegisterData) => {
    const response = await authApi.register(data);
    const accessToken = response.token.access_token;
    tokenStorage.set(accessToken);
    if (data.tenant_id) {
      tenantStorage.set(String(data.tenant_id));
    }
    setToken(accessToken);
    setUser(response.user);
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      // Ignore errors during logout
    } finally {
      tokenStorage.remove();
      tenantStorage.remove();
      setToken(null);
      setUser(null);
    }
  }, []);

  const hasRole = useCallback(
    (role: string) => user?.roles.some((r) => r.name === role) ?? false,
    [user],
  );

  const hasAnyRole = useCallback(
    (roles: string[]) => user?.roles.some((r) => roles.includes(r.name)) ?? false,
    [user],
  );

  const hasPermission = useCallback(
    (permission: string) => user?.permissions.includes(permission) ?? false,
    [user],
  );

  const hasAnyPermission = useCallback(
    (permissions: string[]) =>
      user?.permissions.some((p) => permissions.includes(p)) ?? false,
    [user],
  );

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isAuthenticated: !!token && !!user,
        isLoading,
        login,
        register,
        logout,
        refreshUser,
        hasRole,
        hasPermission,
        hasAnyRole,
        hasAnyPermission,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};
