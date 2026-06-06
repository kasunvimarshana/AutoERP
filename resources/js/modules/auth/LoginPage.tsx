import { useEffect, useState } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from './AuthProvider';

export default function LoginPage() {
    const auth = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [loginIdentifier, setLoginIdentifier] = useState('');
    const [password, setPassword] = useState('');
    const [tenantId, setTenantId] = useState('');
    const [organizationUnitId, setOrganizationUnitId] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        document.title = 'Login - AutoERP';
    }, []);

    if (auth.isLoading && auth.token && !auth.user) {
        return <LoadingState label="Checking your session..." fullPage />;
    }

    if (auth.isAuthenticated) {
        return <Navigate to="/dashboard" replace />;
    }

    const from = location.state && typeof location.state === 'object' && 'from' in location.state
        ? String(location.state.from)
        : '/dashboard';

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <section className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="mb-6">
                    <p className="text-sm font-semibold uppercase tracking-wide text-sky-600">AutoERP</p>
                    <h1 className="mt-2 text-2xl font-bold text-slate-950">Sign in</h1>
                    <p className="mt-2 text-sm text-slate-500">
                        Use your backend API account and tenant context.
                    </p>
                </div>

                <form
                    className="space-y-4"
                    onSubmit={async (event) => {
                        event.preventDefault();
                        setError(null);
                        setSubmitting(true);

                        try {
                            await auth.login({
                                login_identifier: loginIdentifier,
                                password,
                                tenant_id: tenantId ? Number(tenantId) : null,
                                organization_unit_id: organizationUnitId ? Number(organizationUnitId) : null,
                            });
                            navigate(from, { replace: true });
                        } catch (nextError) {
                            setError(nextError instanceof ApiError
                                ? nextError
                                : new ApiError('Login failed.', null));
                        } finally {
                            setSubmitting(false);
                        }
                    }}
                >
                    <ErrorAlert error={error} title="Unable to sign in" />
                    <Input
                        label="Email or username"
                        name="login_identifier"
                        autoComplete="username"
                        value={loginIdentifier}
                        onChange={(event) => setLoginIdentifier(event.target.value)}
                        error={fieldError(error, 'login_identifier') ?? fieldError(error, 'email')}
                        required
                    />
                    <Input
                        label="Password"
                        name="password"
                        type="password"
                        autoComplete="current-password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        error={fieldError(error, 'password')}
                        required
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Tenant ID"
                            name="tenant_id"
                            type="number"
                            min="1"
                            value={tenantId}
                            onChange={(event) => setTenantId(event.target.value)}
                            error={fieldError(error, 'tenant_id')}
                            required
                        />
                        <Input
                            label="Org unit ID"
                            name="organization_unit_id"
                            type="number"
                            min="1"
                            value={organizationUnitId}
                            onChange={(event) => setOrganizationUnitId(event.target.value)}
                            error={fieldError(error, 'organization_unit_id')}
                        />
                    </div>
                    <Button type="submit" className="w-full" loading={submitting || auth.isLoading}>
                        Sign in
                    </Button>
                </form>
            </section>
        </main>
    );
}
