import type { ReactNode } from 'react';
import { Outlet } from 'react-router-dom';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { useAuth } from './AuthProvider';
import { GuardLoadingState } from './GuardLoadingState';

interface PlatformOperatorRouteProps {
    children?: ReactNode;
}

export function PlatformOperatorRoute({ children }: PlatformOperatorRouteProps) {
    const auth = useAuth();

    if (auth.isLoading) {
        return <GuardLoadingState label="Checking platform access..." />;
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
