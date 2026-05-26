import { Navigate, Outlet } from 'react-router-dom';
import { FullPageState } from '../../../components/feedback/FullPageState';
import { useAuth } from '../../../features/auth/context/AuthContext';

export function GuestRoute() {
    const { status, isAuthenticated } = useAuth();

    if (status === 'loading') {
        return <FullPageState title="Checking session" description="Restoring your AutoERP session." />;
    }

    if (isAuthenticated) {
        return <Navigate to="/" replace />;
    }

    return <Outlet />;
}
