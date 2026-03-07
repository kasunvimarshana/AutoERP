import React, { createContext, useContext, useState, useEffect } from 'react';
import type { ReactNode } from 'react';
import type { User, AuthState, LoginCredentials } from '../types';
import { authApi } from '../api/auth';

interface AuthContextType extends AuthState {
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: () => Promise<void>;
  hasRole: (role: string) => boolean;
  hasPermission: (permission: string) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(localStorage.getItem('auth_token'));

  useEffect(() => {
    if (token) {
      authApi.me()
        .then((res) => setUser(res.data.data))
        .catch(() => {
          setToken(null);
          localStorage.removeItem('auth_token');
        });
    }
  }, [token]);

  const login = async (credentials: LoginCredentials) => {
    const res = await authApi.login(credentials);
    const { access_token, user: loggedInUser } = res.data;
    setToken(access_token);
    setUser(loggedInUser);
    localStorage.setItem('auth_token', access_token);
    if (loggedInUser.tenant_id) {
      localStorage.setItem('tenant_id', String(loggedInUser.tenant_id));
    }
  };

  const logout = async () => {
    try {
      await authApi.logout();
    } finally {
      setToken(null);
      setUser(null);
      localStorage.removeItem('auth_token');
      localStorage.removeItem('tenant_id');
    }
  };

  const hasRole = (role: string) =>
    user?.roles?.some((r) => r.name === role) ?? false;

  const hasPermission = (permission: string) =>
    user?.permissions?.some((p) => p.name === permission) ?? false;

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isAuthenticated: !!token && !!user,
        login,
        logout,
        hasRole,
        hasPermission,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
};
