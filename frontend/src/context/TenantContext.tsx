import React, { createContext, useCallback, useEffect, useState } from 'react';
import { tenantsApi } from '@/api/tenants';
import { tenantStorage } from '@/api/axios';
import type { Tenant } from '@/types';

export interface TenantContextValue {
  currentTenant: Tenant | null;
  isLoading: boolean;
  switchTenant: (tenantId: number) => Promise<void>;
  clearTenant: () => void;
}

export const TenantContext = createContext<TenantContextValue | null>(null);

export const TenantProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [currentTenant, setCurrentTenant] = useState<Tenant | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const loadTenant = useCallback(async (id: number) => {
    setIsLoading(true);
    try {
      const tenant = await tenantsApi.get(id);
      setCurrentTenant(tenant);
    } catch {
      setCurrentTenant(null);
      tenantStorage.remove();
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Restore tenant from storage on boot
  useEffect(() => {
    const stored = tenantStorage.get();
    if (stored) {
      loadTenant(Number(stored));
    }
  }, [loadTenant]);

  const switchTenant = useCallback(
    async (tenantId: number) => {
      tenantStorage.set(String(tenantId));
      await loadTenant(tenantId);
    },
    [loadTenant],
  );

  const clearTenant = useCallback(() => {
    tenantStorage.remove();
    setCurrentTenant(null);
  }, []);

  return (
    <TenantContext.Provider value={{ currentTenant, isLoading, switchTenant, clearTenant }}>
      {children}
    </TenantContext.Provider>
  );
};
