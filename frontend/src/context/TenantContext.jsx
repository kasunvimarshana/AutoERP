import { createContext, useContext, useState, useEffect, useCallback } from 'react'
import useAuthStore from '../store/authStore'

const TenantContext = createContext(null)

/**
 * TenantProvider wraps the application (or a sub-tree) and makes the current
 * tenant ID available to every descendant via the useTenantContext hook.
 *
 * It derives its initial tenantId from the Zustand auth store so the value is
 * kept in sync with the rest of the auth flow (login / logout / setTenant).
 *
 * @param {{ children: React.ReactNode }} props
 */
export function TenantProvider({ children }) {
  const storeTenantId = useAuthStore((state) => state.tenantId)
  const storSetTenant = useAuthStore((state) => state.setTenant)

  const [isLoading, setIsLoading] = useState(false)

  // Keep a local derived value so we can layer additional loading states on top
  // (e.g. while fetching tenant metadata after a switch).
  const [tenantId, setTenantIdLocal] = useState(storeTenantId)

  // Stay in sync whenever the store changes (e.g. after login / logout).
  useEffect(() => {
    setTenantIdLocal(storeTenantId)
  }, [storeTenantId])

  /**
   * Switch the active tenant.
   * Persists the new tenant through both the local state and the Zustand store
   * (which in turn mirrors it to localStorage for the Axios interceptor).
   *
   * @param {string|null} newTenantId
   */
  const setTenant = useCallback(
    async (newTenantId) => {
      setIsLoading(true)
      try {
        setTenantIdLocal(newTenantId)
        storSetTenant(newTenantId)
      } finally {
        setIsLoading(false)
      }
    },
    [storSetTenant],
  )

  const value = {
    tenantId,
    setTenant,
    isLoading,
  }

  return <TenantContext.Provider value={value}>{children}</TenantContext.Provider>
}

/**
 * Hook to consume the TenantContext.
 * Must be used inside a <TenantProvider>.
 *
 * @returns {{ tenantId: string|null, setTenant: Function, isLoading: boolean }}
 */
export function useTenantContext() {
  const context = useContext(TenantContext)

  if (context === null) {
    throw new Error('useTenantContext must be used within a TenantProvider')
  }

  return context
}

export default TenantContext
