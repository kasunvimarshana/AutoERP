import type { ReactNode } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { LoadingState } from '@/shared/components/LoadingState';
import { hasPermission } from './accessControl';
import { useAuth } from './AuthProvider';

interface PermissionRouteProps {
    permission: string;
    children?: ReactNode;
}

export function PermissionRoute({ permission, children }: PermissionRouteProps) {
    const auth = useAuth();
    const location = useLocation();

    if (auth.isLoading || !auth.permissionsLoaded) {
        return <LoadingState label="Checking access..." fullPage />;
    }

    if (!hasPermission(auth, permission)) {
        const deniedFrom = `${location.pathname}${location.search}${location.hash}`;
        return <Navigate to="/dashboard" replace state={{ deniedFrom }} />;
    }

    return children ?? <Outlet />;
}
