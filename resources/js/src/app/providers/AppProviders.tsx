import type { PropsWithChildren } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from '../../features/auth/context/AuthContext';
import { TenantProvider } from '../../features/auth/context/TenantContext';
import { ToastProvider } from './ToastProvider';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
            staleTime: 30_000,
        },
    },
});

export function AppProviders({ children }: PropsWithChildren) {
    return (
        <QueryClientProvider client={queryClient}>
            <TenantProvider>
                <AuthProvider>
                    <ToastProvider>
                        <BrowserRouter>{children}</BrowserRouter>
                    </ToastProvider>
                </AuthProvider>
            </TenantProvider>
        </QueryClientProvider>
    );
}
