import React, { createContext, useContext, useState, ReactNode } from 'react';
import { Tenant } from '../types';

interface TenantContextType {
  currentTenant: Tenant | null;
  tenantId: number | null;
  setTenant: (tenant: Tenant) => void;
  clearTenant: () => void;
}

const TenantContext = createContext<TenantContextType | undefined>(undefined);

export const useTenant = () => {
  const ctx = useContext(TenantContext);
  if (!ctx) throw new Error('useTenant must be used within TenantProvider');
  return ctx;
};

const TENANT_KEY = 'kv_tenant';
const TENANT_ID_KEY = 'kv_tenant_id';

export const TenantProvider = ({ children }: { children: ReactNode }) => {
  const [currentTenant, setCurrentTenant] = useState<Tenant | null>(() => {
    const stored = localStorage.getItem(TENANT_KEY);
    return stored ? JSON.parse(stored) : null;
  });

  const [tenantId, setTenantId] = useState<number | null>(() => {
    const stored = localStorage.getItem(TENANT_ID_KEY);
    const envId = process.env.REACT_APP_TENANT_ID;
    if (stored) return parseInt(stored);
    if (envId) return parseInt(envId);
    return null;
  });

  const setTenant = (tenant: Tenant) => {
    setCurrentTenant(tenant);
    setTenantId(tenant.id);
    localStorage.setItem(TENANT_KEY, JSON.stringify(tenant));
    localStorage.setItem(TENANT_ID_KEY, String(tenant.id));
  };

  const clearTenant = () => {
    setCurrentTenant(null);
    setTenantId(null);
    localStorage.removeItem(TENANT_KEY);
    localStorage.removeItem(TENANT_ID_KEY);
  };

  return (
    <TenantContext.Provider value={{ currentTenant, tenantId, setTenant, clearTenant }}>
      {children}
    </TenantContext.Provider>
  );
};

export default TenantContext;
