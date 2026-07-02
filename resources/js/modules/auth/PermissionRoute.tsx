import type { ReactNode } from 'react';
import { Outlet } from 'react-router-dom';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { hasPermission } from './accessControl';
import { useAuth } from './AuthProvider';
import { GuardLoadingState } from './GuardLoadingState';

interface PermissionRouteProps {
    permission?: string;
    anyOf?: readonly string[];
    children?: ReactNode;
}

export function PermissionRoute({ permission, anyOf, children }: PermissionRouteProps) {
    const auth = useAuth();

    if (auth.isLoading || !auth.permissionsLoaded) {
        return <GuardLoadingState label="Checking access..." />;
    }

    const required = anyOf ?? (permission ? [permission] : []);
    const allowed = required.length > 0 && required.some((candidate) => hasPermission(auth, candidate));

    if (!allowed) {
        return <AccessDeniedPage />;
    }

    return children ?? <Outlet />;
}
