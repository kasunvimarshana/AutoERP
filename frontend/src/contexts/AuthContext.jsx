import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import Keycloak from 'keycloak-js';

const AuthContext = createContext(null);

const keycloakConfig = {
  url: process.env.REACT_APP_KEYCLOAK_URL || 'http://localhost:8080',
  realm: process.env.REACT_APP_KEYCLOAK_REALM || 'saas-inventory',
  clientId: process.env.REACT_APP_KEYCLOAK_CLIENT_ID || 'inventory-spa',
};

let keycloakInstance = null;

export function AuthProvider({ children }) {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!keycloakInstance) {
      keycloakInstance = new Keycloak(keycloakConfig);
    }

    keycloakInstance.init({
      onLoad: 'check-sso',
      silentCheckSsoRedirectUri: window.location.origin + '/silent-check-sso.html',
      pkceMethod: 'S256',
    }).then((authenticated) => {
      setIsAuthenticated(authenticated);
      if (authenticated) {
        setToken(keycloakInstance.token);
        setUser({
          id: keycloakInstance.subject,
          username: keycloakInstance.tokenParsed?.preferred_username,
          email: keycloakInstance.tokenParsed?.email,
          firstName: keycloakInstance.tokenParsed?.given_name,
          lastName: keycloakInstance.tokenParsed?.family_name,
          roles: keycloakInstance.tokenParsed?.realm_access?.roles || [],
          tenantId: keycloakInstance.tokenParsed?.tenant_id,
        });

        // Auto-refresh token
        setInterval(() => {
          keycloakInstance.updateToken(60).then((refreshed) => {
            if (refreshed) setToken(keycloakInstance.token);
          }).catch(() => keycloakInstance.logout());
        }, 30000);
      }
      setLoading(false);
    }).catch(() => {
      setLoading(false);
    });
  }, []);

  const login = useCallback(() => keycloakInstance?.login(), []);
  const logout = useCallback(() => keycloakInstance?.logout({ redirectUri: window.location.origin + '/login' }), []);

  const hasRole = useCallback((role) => user?.roles?.includes(role) ?? false, [user]);
  const hasAnyRole = useCallback((roles) => roles.some((r) => hasRole(r)), [hasRole]);

  return (
    <AuthContext.Provider value={{ isAuthenticated, user, token, loading, login, logout, hasRole, hasAnyRole }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
};
