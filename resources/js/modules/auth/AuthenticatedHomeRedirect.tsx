import { Navigate } from 'react-router-dom';
import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from '@/app/routePaths';
import { useAuth } from './AuthProvider';

export function AuthenticatedHomeRedirect() {
    const auth = useAuth();
    return <Navigate to={auth.isPlatformOperator ? PLATFORM_HOME_PATH : DASHBOARD_PATH} replace />;
}
