import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuthContext } from '../../contexts/AuthContext';
import { Spinner } from '../../shared/components/ui/Spinner';

export function ProtectedRoute() {
    const { isAuthenticated, isLoading } = useAuthContext();
    const location = useLocation();

    if (isLoading) {
        return <div className="flex min-h-screen items-center justify-center bg-slate-50"><Spinner /><span className="ml-3 text-sm font-semibold text-slate-600">Restoring session</span></div>;
    }

    if (!isAuthenticated) {
        return <Navigate replace state={{ from: location }} to="/login" />;
    }

    return <Outlet />;
}
