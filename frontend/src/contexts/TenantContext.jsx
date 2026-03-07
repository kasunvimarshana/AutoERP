import React, { createContext, useContext, useState, useEffect } from 'react';
import { useAuth } from './AuthContext';

const TenantContext = createContext(null);

export function TenantProvider({ children }) {
  const { user } = useAuth();
  const [tenantId, setTenantId] = useState(null);
  const [tenantName, setTenantName] = useState(null);

  useEffect(() => {
    if (user?.tenantId) {
      setTenantId(user.tenantId);
      setTenantName(user.tenantId);
    }
  }, [user]);

  return (
    <TenantContext.Provider value={{ tenantId, tenantName, setTenantId }}>
      {children}
    </TenantContext.Provider>
  );
}

export const useTenant = () => {
  const ctx = useContext(TenantContext);
  if (!ctx) throw new Error('useTenant must be used within TenantProvider');
  return ctx;
};
