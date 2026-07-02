import { Outlet } from 'react-router-dom';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { useAuth } from './AuthProvider';
import { GuardLoadingState } from './GuardLoadingState';

export function TenantRoute() {
    const auth = useAuth();

    if (auth.isLoading) {
        return <GuardLoadingState label="Checking tenant access..." />;
    }

    if (auth.authMode !== 'tenant' || auth.isPlatformOperator || !auth.tenant) {
        return (
            <AccessDeniedPage
                title="Tenant workspace unavailable"
                message="This page belongs to a tenant workspace. Use a tenant account and verified tenant domain to continue."
            />
        );
    }

    return <Outlet />;
}
