import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { FullPageState } from '../../../components/feedback/FullPageState';
import { useAuth } from '../../../features/auth/context/AuthContext';

export function ProtectedRoute() {
    const location = useLocation();
    const { status, isAuthenticated } = useAuth();

    if (status === 'loading') {
        return <FullPageState title="Loading workspace" description="Preparing the authenticated app shell." />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return <Outlet />;
}
