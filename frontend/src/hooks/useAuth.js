import { useKeycloak } from '@react-keycloak/web';

export function useAuth() {
  const { keycloak, initialized } = useKeycloak();

  const hasRole = (role) => keycloak.hasRealmRole(role);
  const isAdmin   = () => hasRole('admin');
  const isManager = () => hasRole('manager') || hasRole('admin');

  return {
    initialized,
    authenticated: keycloak.authenticated,
    token: keycloak.token,
    user: keycloak.tokenParsed,
    username: keycloak.tokenParsed?.preferred_username,
    email: keycloak.tokenParsed?.email,
    roles: keycloak.tokenParsed?.realm_access?.roles || [],
    hasRole,
    isAdmin,
    isManager,
    login:  () => keycloak.login(),
    logout: () => keycloak.logout({ redirectUri: window.location.origin }),
  };
}
