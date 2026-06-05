import type { ReactNode } from 'react';
import { AppSettingsContextProvider } from '../../contexts/AppSettingsContext';
import { AuthProvider } from './AuthProvider';
import { PermissionProvider } from './PermissionProvider';
import { TenantProvider } from './TenantProvider';
import { ThemeProvider } from './ThemeProvider';
import { SidebarContextProvider } from '../../contexts/SidebarContext';

export function AppProviders({ children }: { children: ReactNode }) {
    return (
        <ThemeProvider>
            <AuthProvider>
                <TenantProvider>
                    <PermissionProvider>
                        <AppSettingsContextProvider>
                            <SidebarContextProvider>{children}</SidebarContextProvider>
                        </AppSettingsContextProvider>
                    </PermissionProvider>
                </TenantProvider>
            </AuthProvider>
        </ThemeProvider>
    );
}
