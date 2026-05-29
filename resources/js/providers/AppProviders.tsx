import type { ReactNode } from 'react';
import { AuthProvider } from '../contexts/AuthContext';
import { TenantProvider } from '../contexts/TenantContext';
import { UiProvider } from '../contexts/UiContext';

export function AppProviders({ children }: { children: ReactNode }) {
    return (
        <AuthProvider>
            <TenantProvider>
                <UiProvider>{children}</UiProvider>
            </TenantProvider>
        </AuthProvider>
    );
}
