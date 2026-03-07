import useAuthStore from '../store/authStore'

/**
 * Convenience hook that surfaces all auth state and actions from the Zustand
 * authStore. Components should use this hook instead of importing the store
 * directly to keep the API surface consistent.
 *
 * @returns {{
 *   user: Object|null,
 *   token: string|null,
 *   refreshToken: string|null,
 *   tenantId: string|null,
 *   isAuthenticated: boolean,
 *   login: Function,
 *   logout: Function,
 *   setUser: Function,
 *   setTenant: Function,
 *   updateToken: Function,
 * }}
 */
export function useAuth() {
  const user = useAuthStore((state) => state.user)
  const token = useAuthStore((state) => state.token)
  const refreshToken = useAuthStore((state) => state.refreshToken)
  const tenantId = useAuthStore((state) => state.tenantId)
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)

  const login = useAuthStore((state) => state.login)
  const logout = useAuthStore((state) => state.logout)
  const setUser = useAuthStore((state) => state.setUser)
  const setTenant = useAuthStore((state) => state.setTenant)
  const updateToken = useAuthStore((state) => state.updateToken)

  return {
    user,
    token,
    refreshToken,
    tenantId,
    isAuthenticated,
    login,
    logout,
    setUser,
    setTenant,
    updateToken,
  }
}
