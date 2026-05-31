import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthContext } from '../../../contexts/AuthContext';
import { Spinner } from '../../../shared/components/ui/Spinner';

export function LogoutPage() {
    const { logout } = useAuthContext();
    const navigate = useNavigate();

    useEffect(() => {
        let isMounted = true;

        void logout().finally(() => {
            if (isMounted) {
                navigate('/login', {
                    replace: true,
                    state: { message: 'You have been signed out.' },
                });
            }
        });

        return () => {
            isMounted = false;
        };
    }, [logout, navigate]);

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50">
            <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 shadow-sm">
                <Spinner />
                Signing out
            </div>
        </div>
    );
}
