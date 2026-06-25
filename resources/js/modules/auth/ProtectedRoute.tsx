import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

export function ProtectedRoute() {
    const auth = useAuth();
    const location = useLocation();

    if (auth.isLoading) {
        return <LoadingState label="Checking access..." fullPage />;
    }

    if (auth.bootstrapError) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
                <section className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <ErrorAlert error={auth.bootstrapError} title="Unable to restore your session" />
                    <p className="mt-3 text-sm text-slate-600">
                        Your credentials were preserved because this may be a temporary network or service problem.
                    </p>
                    <Button className="mt-5" onClick={() => void auth.loadCurrentUser()}>
                        Retry
                    </Button>
                </section>
            </main>
        );
    }

    if (!auth.isAuthenticated) {
        const from = `${location.pathname}${location.search}${location.hash}`;
        return <Navigate to="/login" replace state={{ from }} />;
    }

    return <Outlet />;
}
