import { useEffect, useState } from 'react';
import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from '@/app/routePaths';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import type { AuthMode } from '@/shared/api/authSessionStorage';
import { useAuth } from './AuthProvider';

export default function LoginPage() {
    const auth = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [authMode, setAuthMode] = useState<AuthMode>('tenant');
    const [loginIdentifier, setLoginIdentifier] = useState('');
    const [password, setPassword] = useState('');
    const [tenantCode, setTenantCode] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        document.title = 'Login - AutoERP';
    }, []);

    if (auth.isLoading && auth.token && !auth.user) {
        return <LoadingState label="Checking your session..." fullPage />;
    }

    if (auth.isAuthenticated) {
        return <Navigate to={auth.isPlatformOperator ? PLATFORM_HOME_PATH : DASHBOARD_PATH} replace />;
    }

    const requestedPath = location.state && typeof location.state === 'object' && 'from' in location.state
        ? String(location.state.from)
        : null;
    const destination = authMode === 'platform'
        ? PLATFORM_HOME_PATH
        : requestedPath ?? DASHBOARD_PATH;

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <section className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="mb-6">
                    <p className="text-sm font-semibold uppercase tracking-wide text-sky-600">AutoERP</p>
                    <h1 className="mt-2 text-2xl font-bold text-slate-950">
                        {authMode === 'platform' ? 'Platform administration' : 'Sign in'}
                    </h1>
                    <p className="mt-2 text-sm text-slate-500">
                        {authMode === 'platform'
                            ? 'Use a dedicated platform operator account. Tenant accounts cannot access this control plane.'
                            : 'Use your account to enter an authorized tenant workspace.'}
                    </p>
                </div>

                <div className="mb-5 grid grid-cols-2 rounded-lg bg-slate-100 p-1" role="group" aria-label="Sign-in type">
                    <button
                        type="button"
                        className={`rounded-md px-3 py-2 text-sm font-medium ${authMode === 'tenant' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-600'}`}
                        onClick={() => {
                            setAuthMode('tenant');
                            setError(null);
                        }}
                    >
                        Tenant workspace
                    </button>
                    <button
                        type="button"
                        className={`rounded-md px-3 py-2 text-sm font-medium ${authMode === 'platform' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-600'}`}
                        onClick={() => {
                            setAuthMode('platform');
                            setTenantCode('');
                            setError(null);
                        }}
                    >
                        Platform operator
                    </button>
                </div>

                <form
                    className="space-y-4"
                    onSubmit={async (event) => {
                        event.preventDefault();
                        setError(null);
                        setSubmitting(true);

                        try {
                            await auth.login({
                                auth_mode: authMode,
                                login_identifier: loginIdentifier,
                                password,
                                tenant_code: authMode === 'tenant' ? tenantCode.trim() || null : null,
                            });
                            navigate(destination, { replace: true });
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
                    {authMode === 'tenant' ? (
                        <Input
                            label="Workspace code"
                            name="tenant_code"
                            autoComplete="organization"
                            value={tenantCode}
                            onChange={(event) => setTenantCode(event.target.value.toUpperCase())}
                            error={fieldError(error, 'tenant_code')}
                            hint="Optional on a verified tenant domain. Required on the central SaaS address."
                        />
                    ) : null}
                    <Input
                        label={authMode === 'platform' ? 'Platform operator email' : 'Email or username'}
                        name="login_identifier"
                        type={authMode === 'platform' ? 'email' : 'text'}
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
                    <Button type="submit" className="w-full" loading={submitting || auth.isLoading}>
                        {authMode === 'platform' ? 'Open platform administration' : 'Sign in'}
                    </Button>
                </form>
            </section>
        </main>
    );
}
