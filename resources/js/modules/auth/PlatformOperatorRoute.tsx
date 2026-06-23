import type { ReactNode } from 'react';
import { Outlet } from 'react-router-dom';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

interface PlatformOperatorRouteProps {
    children?: ReactNode;
}

export function PlatformOperatorRoute({ children }: PlatformOperatorRouteProps) {
    const auth = useAuth();

    if (auth.isLoading) {
        return <LoadingState label="Checking platform access..." fullPage />;
    }

    if (auth.authMode !== 'platform' || !auth.isPlatformOperator) {
        return (
            <AccessDeniedPage
                title="Platform access required"
                message="This page belongs to the SaaS control plane and requires an authorized platform operator account."
            />
        );
    }

    return children ?? <Outlet />;
}
