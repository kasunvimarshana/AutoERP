import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

export function ProtectedRoute() {
    const auth = useAuth();
    const location = useLocation();

    if (auth.isLoading) {
        return <LoadingState label="Checking access..." fullPage />;
    }

    if (!auth.isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    return <Outlet />;
}
