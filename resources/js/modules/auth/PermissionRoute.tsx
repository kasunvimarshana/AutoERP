import type { ReactNode } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

interface PermissionRouteProps {
    permission: string;
    children?: ReactNode;
}

export function PermissionRoute({ permission, children }: PermissionRouteProps) {
    const auth = useAuth();
    const location = useLocation();

    if (auth.isLoading) {
        return <LoadingState label="Checking access..." fullPage />;
    }

    if (!auth.permissions.includes(permission)) {
        return <Navigate to="/dashboard" replace state={{ deniedFrom: location.pathname }} />;
    }

    return children ?? <Outlet />;
}
