import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../../contexts/AuthContext';
import { Spinner } from '../../shared/components/ui/Spinner';

function GuardLoadingState() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50">
            <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 shadow-sm">
                <Spinner />
                Checking session
            </div>
        </div>
    );
}

export function AuthGuard({ children }: { children: ReactNode }) {
    const { isAuthenticated, isLoading } = useAuthContext();
    const location = useLocation();

    if (isLoading) {
        return <GuardLoadingState />;
    }

    if (!isAuthenticated) {
        return <Navigate replace state={{ from: location }} to="/login" />;
    }

    return children;
}

export function GuestGuard({ children }: { children: ReactNode }) {
    const { isAuthenticated, isLoading } = useAuthContext();

    if (isLoading) {
        return <GuardLoadingState />;
    }

    if (isAuthenticated) {
        return <Navigate replace to="/dashboard" />;
    }

    return children;
}
