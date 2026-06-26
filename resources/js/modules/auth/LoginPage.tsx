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
import { authApi } from './authApi';
import { isCentralPlatformHost, workspaceLoginUrl } from './platformHost';
import type { PlatformMfaEnrollment } from './authTypes';

const MFA_ENROLLMENT_REQUIRED = 'AUTH_MFA_ENROLLMENT_REQUIRED';
const MFA_REQUIRED = 'AUTH_MFA_REQUIRED';

export default function LoginPage() {
    const auth = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [authMode, setAuthMode] = useState<AuthMode>('tenant');
    const [loginIdentifier, setLoginIdentifier] = useState('');
    const [password, setPassword] = useState('');
    const [workspaceAddress, setWorkspaceAddress] = useState('');
    const [workspaceAddressError, setWorkspaceAddressError] = useState<string | null>(null);
    const [totpCode, setTotpCode] = useState('');
    const [backupCode, setBackupCode] = useState('');
    const [useBackupCode, setUseBackupCode] = useState(false);
    const [mfaRequired, setMfaRequired] = useState(false);
    const [enrollment, setEnrollment] = useState<PlatformMfaEnrollment | null>(null);
    const [enrollmentCode, setEnrollmentCode] = useState('');
    const [backupCodes, setBackupCodes] = useState<string[] | null>(null);
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

    function resetMfaState() {
        setMfaRequired(false);
        setTotpCode('');
        setBackupCode('');
        setUseBackupCode(false);
        setEnrollment(null);
        setEnrollmentCode('');
        setBackupCodes(null);
    }

    function changeAuthMode(mode: AuthMode) {
        if (mode === authMode) return;
        setAuthMode(mode);
        setLoginIdentifier('');
        setPassword('');
        setWorkspaceAddressError(null);
        resetMfaState();
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
                totp_code: authMode === 'platform' && !useBackupCode ? totpCode.trim() || null : null,
                backup_code: authMode === 'platform' && useBackupCode ? backupCode.trim() || null : null,
            });
            navigate(destination, { replace: true });
        } catch (nextError) {
            const apiError = nextError instanceof ApiError ? nextError : new ApiError('Login failed.', null);
            if (authMode === 'platform' && apiError.code === MFA_REQUIRED) setMfaRequired(true);
            if (authMode === 'platform' && apiError.code === MFA_ENROLLMENT_REQUIRED) {
                const enrollmentProof = errorDetail<string>(apiError, 'enrollment_proof');
                const provisioningUri = errorDetail<string>(apiError, 'provisioning_uri');
                if (enrollmentProof && provisioningUri) {
                    setEnrollment({ enrollment_proof: enrollmentProof, provisioning_uri: provisioningUri });
                }
            }
            setError(apiError);
        } finally {
            setSubmitting(false);
        }
    }

    async function confirmEnrollment() {
        if (!enrollment || enrollmentCode.trim() === '') return;
        setSubmitting(true);
        setError(null);
        try {
            const confirmation = await authApi.confirmPlatformMfaEnrollment(enrollment.enrollment_proof, enrollmentCode.trim());
            setBackupCodes(confirmation.backup_codes);
            setEnrollment(null);
            setEnrollmentCode('');
            setMfaRequired(true);
        } catch (nextError) {
            setError(nextError instanceof ApiError ? nextError : new ApiError('MFA enrollment failed.', null));
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
                            ? 'Use a dedicated platform operator account. Sensitive control-plane actions require MFA and recent authentication.'
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
                ) : backupCodes ? (
                    <div className="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        <p className="font-semibold">Save these one-time backup codes now</p>
                        <div className="mt-3 grid grid-cols-2 gap-2 font-mono text-xs">
                            {backupCodes.map((code) => <code key={code} className="rounded bg-white p-2 text-center">{code}</code>)}
                        </div>
                        <p className="mt-3">They will not be shown again. Store them separately from your password.</p>
                        <Button className="mt-3 w-full" onClick={() => setBackupCodes(null)}>I have stored the codes</Button>
                    </div>
                ) : enrollment ? (
                    <div className="space-y-4">
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                            <p className="font-semibold">Set up multi-factor authentication</p>
                            <p className="mt-2">Add this account to an authenticator app using the setup URI, then enter the current six-digit code.</p>
                            <p className="mt-3 break-all"><strong>Setup URI:</strong> <code className="text-xs">{enrollment.provisioning_uri}</code></p>
                        </div>
                        <Input
                            label="Authenticator code"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            pattern="[0-9]{6}"
                            maxLength={6}
                            value={enrollmentCode}
                            onChange={(event) => setEnrollmentCode(event.target.value.replace(/\D/g, '').slice(0, 6))}
                            required
                        />
                        <Button className="w-full" loading={submitting} disabled={enrollmentCode.length !== 6} onClick={() => void confirmEnrollment()}>Enable MFA</Button>
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
                        {authMode === 'platform' && mfaRequired ? (
                            <>
                                {useBackupCode ? (
                                    <Input label="Backup code" autoComplete="one-time-code" value={backupCode} onChange={(event) => setBackupCode(event.target.value.toUpperCase())} required />
                                ) : (
                                    <Input
                                        label="Authenticator code"
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        pattern="[0-9]{6}"
                                        maxLength={6}
                                        value={totpCode}
                                        onChange={(event) => setTotpCode(event.target.value.replace(/\D/g, '').slice(0, 6))}
                                        required
                                    />
                                )}
                                <button type="button" className="text-sm font-medium text-blue-700 hover:underline" onClick={() => { setUseBackupCode((current) => !current); setTotpCode(''); setBackupCode(''); }}>
                                    {useBackupCode ? 'Use authenticator code' : 'Use a backup code'}
                                </button>
                            </>
                        ) : null}
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
    if (stage === 'mfa_challenge' || stage === 'mfa_enrollment') return 'Complete account security';
    if (stage === 'organization_access') return 'Organization access is not ready';
    if (error.status !== null && error.status >= 500) return 'Authentication service unavailable';

    return 'Unable to sign in';
}
