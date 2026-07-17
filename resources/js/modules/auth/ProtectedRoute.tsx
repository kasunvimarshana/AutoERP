import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useAuth } from './AuthProvider';
import { GuardLoadingState } from './GuardLoadingState';

export function ProtectedRoute() {
    const auth = useAuth();
    const location = useLocation();

    if (auth.isLoading) {
        return <GuardLoadingState label="Checking access..." />;
    }

    if (auth.bootstrapError) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
                <section className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <ErrorAlert error={auth.bootstrapError} title="Unable to restore your session" inline />
                    <p className="mt-3 text-sm text-slate-600">
                        Your credentials were preserved because this may be a temporary network or service problem.
                    </p>
                    <div className="mt-5 flex flex-wrap gap-2">
                        <Button onClick={() => void auth.loadCurrentUser()}>
                            Retry
                        </Button>
                        <Button variant="secondary" onClick={() => void auth.logout()}>
                            Sign out and clear session
                        </Button>
                        <LinkButton variant="ghost" to="/login">Return to login</LinkButton>
                    </div>
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
