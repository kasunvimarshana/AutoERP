import React, { createContext, useContext, useState, useEffect } from 'react';
import Keycloak from 'keycloak-js';

// Setup Keycloak instance
// Ideally loaded from env variables
const keycloakConfig = {
  url: 'http://localhost:8080',
  realm: 'inventory-system',
  clientId: 'react-frontend',
};

const keycloakInstance = new Keycloak(keycloakConfig);

interface KeycloakContextType {
  keycloak: Keycloak | null;
  authenticated: boolean;
  hasRole: (role: string) => boolean;
  token: string | null;
}

const KeycloakContext = createContext<KeycloakContextType>({
  keycloak: null,
  authenticated: false,
  hasRole: () => false,
  token: null,
});

export const KeycloakProvider = ({ children }: { children: React.ReactNode }) => {
  const [keycloak, setKeycloak] = useState<Keycloak | null>(null);
  const [authenticated, setAuthenticated] = useState<boolean>(false);

  useEffect(() => {
    keycloakInstance.init({ onLoad: 'login-required', checkLoginIframe: false })
      .then((auth) => {
        setKeycloak(keycloakInstance);
        setAuthenticated(auth);
      })
      .catch((error) => console.error('Keycloak init failed', error));
  }, []);

  const hasRole = (role: string) => {
    return keycloakInstance.hasRealmRole(role);
  };

  if (!keycloak) {
    return <div>Loading Authentication...</div>;
  }

  return (
    <KeycloakContext.Provider value={{
      keycloak,
      authenticated,
      hasRole,
      token: keycloak.token || null
    }}>
      {children}
    </KeycloakContext.Provider>
  );
};

export const useKeycloak = () => useContext(KeycloakContext);
