import type { ReactNode } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

interface PlatformOperatorRouteProps {
    children?: ReactNode;
}

export function PlatformOperatorRoute({ children }: PlatformOperatorRouteProps) {
    const auth = useAuth();
    const location = useLocation();

    if (auth.isLoading) {
        return <LoadingState label="Checking platform access..." fullPage />;
    }

    if (!auth.isPlatformOperator) {
        return <Navigate to="/dashboard" replace state={{ deniedFrom: location.pathname }} />;
    }

    return children ?? <Outlet />;
}
