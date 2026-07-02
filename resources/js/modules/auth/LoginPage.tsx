import { useEffect, useState } from 'react';
import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from '@/app/routePaths';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ApiError, errorDetail, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import type { AuthMode } from '@/shared/api/authSessionStorage';
import { useAuth } from './AuthProvider';
import { isCentralPlatformHost, workspaceLoginUrl } from './platformHost';

export default function LoginPage() {
    const auth = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [authMode, setAuthMode] = useState<AuthMode>('tenant');
    const [loginIdentifier, setLoginIdentifier] = useState('');
    const [password, setPassword] = useState('');
    const [workspaceAddress, setWorkspaceAddress] = useState('');
    const [workspaceAddressError, setWorkspaceAddressError] = useState<string | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        document.title = 'Login - AutoERP';
    }, []);

    if (auth.isLoading && !auth.user) return <LoadingState label="Checking your session..." fullPage />;
    if (auth.isAuthenticated) return <Navigate to={auth.isPlatformOperator ? PLATFORM_HOME_PATH : DASHBOARD_PATH} replace />;

    const requestedPath = location.state && typeof location.state === 'object' && 'from' in location.state
        ? String(location.state.from)
        : null;
    const destination = authMode === 'platform' ? PLATFORM_HOME_PATH : requestedPath ?? DASHBOARD_PATH;
    const tenantLoginRequiresWorkspace = authMode === 'tenant' && isCentralPlatformHost();

    function changeAuthMode(mode: AuthMode) {
        if (mode === authMode) return;
        setAuthMode(mode);
        setLoginIdentifier('');
        setPassword('');
        setWorkspaceAddressError(null);
        setError(null);
    }

    function openWorkspace() {
        const url = workspaceLoginUrl(workspaceAddress);
        if (!url) {
            setWorkspaceAddressError('Enter a valid verified workspace hostname, such as erp.example.com.');
            return;
        }

        window.location.assign(url);
    }

    async function submitLogin() {
        setError(null);
        setSubmitting(true);
        try {
            await auth.login({
                auth_mode: authMode,
                identifier: loginIdentifier,
                password,
            });
            navigate(destination, { replace: true });
        } catch (nextError) {
            const apiError = nextError instanceof ApiError ? nextError : new ApiError('Login failed.', null);
            setError(apiError);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <section className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="mb-6">
                    <p className="text-sm font-semibold uppercase tracking-wide text-sky-600">AutoERP</p>
                    <h1 className="mt-2 text-2xl font-bold text-slate-950">{authMode === 'platform' ? 'Platform administration' : 'Sign in'}</h1>
                    <p className="mt-2 text-sm text-slate-500">
                        {authMode === 'platform'
                            ? 'Use a dedicated platform operator account. Sensitive control-plane actions require recent authentication.'
                            : 'Sign in from your organization’s verified workspace address using your email or username.'}
                    </p>
                </div>

                <div className="mb-5 grid grid-cols-2 rounded-lg bg-slate-100 p-1" role="group" aria-label="Sign-in type">
                    {(['tenant', 'platform'] as const).map((mode) => (
                        <button
                            key={mode}
                            type="button"
                            className={`rounded-md px-3 py-2 text-sm font-medium ${authMode === mode ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-600'}`}
                            onClick={() => changeAuthMode(mode)}
                        >
                            {mode === 'tenant' ? 'Tenant workspace' : 'Platform operator'}
                        </button>
                    ))}
                </div>

                <ErrorAlert error={error} title={loginErrorTitle(error)} />

                {tenantLoginRequiresWorkspace ? (
                    <div className="space-y-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                        <div>
                            <p className="font-semibold">Open your verified tenant workspace</p>
                            <p className="mt-1">Tenant credentials are accepted only from the organization’s verified workspace address. This platform address is reserved for platform operators.</p>
                        </div>
                        <Input
                            label="Tenant workspace hostname"
                            placeholder="erp.example.com"
                            value={workspaceAddress}
                            onChange={(event) => { setWorkspaceAddress(event.target.value); setWorkspaceAddressError(null); }}
                            error={workspaceAddressError ?? undefined}
                        />
                        <Button type="button" className="w-full" onClick={openWorkspace}>Open tenant workspace</Button>
                    </div>
                ) : (
                    <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void submitLogin(); }}>
                        <Input
                            label={authMode === 'platform' ? 'Platform operator email' : 'Email or username'}
                            name="identifier"
                            type={authMode === 'platform' ? 'email' : 'text'}
                            autoComplete="username"
                            value={loginIdentifier}
                            onChange={(event) => setLoginIdentifier(event.target.value)}
                            error={fieldError(error, 'identifier') ?? fieldError(error, 'email')}
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
                )}
            </section>
        </main>
    );
}

export function loginErrorTitle(error: ApiError | null): string {
    if (!error) return 'Unable to sign in';
    const stage = errorDetail<string>(error, 'stage');
    if (stage === 'credentials') return 'Check your sign-in details';
    if (stage === 'organization_access') return 'Organization access is not ready';
    if (error.status !== null && error.status >= 500) return 'Authentication service unavailable';

    return 'Unable to sign in';
}
