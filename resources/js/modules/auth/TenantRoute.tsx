import { Outlet } from 'react-router-dom';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

export function TenantRoute() {
    const auth = useAuth();

    if (auth.isLoading) {
        return <LoadingState label="Checking tenant access..." fullPage />;
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
