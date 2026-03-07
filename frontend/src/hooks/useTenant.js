import useAuthStore from '../store/authStore'

/**
 * Convenience hook for tenant-specific state and actions.
 * Exposes the current tenantId and a setTenant action from the auth store.
 *
 * @returns {{
 *   tenantId: string|null,
 *   setTenant: (tenantId: string|null) => void,
 * }}
 */
export function useTenant() {
  const tenantId = useAuthStore((state) => state.tenantId)
  const setTenant = useAuthStore((state) => state.setTenant)

  return { tenantId, setTenant }
}
