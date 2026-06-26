import { useEffect, useState } from 'react';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { authApi } from './authApi';
import type { PlatformMfaEnrollment, PlatformOperatorInvitationInspection } from './authTypes';

export default function PlatformOperatorInvitationPage() {
    const [token] = useState(() => readInvitationToken());
    const [invitation, setInvitation] = useState<PlatformOperatorInvitationInspection | null>(null);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [enrollment, setEnrollment] = useState<PlatformMfaEnrollment | null>(null);
    const [enrollmentCode, setEnrollmentCode] = useState('');
    const [backupCodes, setBackupCodes] = useState<string[] | null>(null);
    const [error, setError] = useState<ApiError | null>(() => token
        ? null
        : new ApiError('This invitation link is incomplete. Ask a platform manager to send a new invitation.', 404));
    const [loading, setLoading] = useState(token !== null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        document.title = 'Platform operator registration - AutoERP';
        if (!token) return;

        const controller = new AbortController();
        authApi.inspectPlatformOperatorInvitation(token, controller.signal)
            .then(setInvitation)
            .catch((nextError) => setError(toApiError(nextError)))
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, [token]);

    async function activateAccount() {
        if (!token || !invitation) return;
        setError(null);
        setSubmitting(true);
        try {
            const acceptance = await authApi.acceptPlatformOperatorInvitation({
                token,
                password,
                password_confirmation: passwordConfirmation,
            });
            window.history.replaceState(null, '', '/register/platform-operator');
            if (!acceptance.mfa_enrollment) {
                throw new ApiError(
                    'The platform account was activated, but MFA enrollment could not be started. Contact a platform manager.',
                    503,
                    'AUTH_MFA_ENROLLMENT_UNAVAILABLE',
                    'infrastructure',
                );
            }
            setEnrollment(acceptance.mfa_enrollment);
            setPassword('');
            setPasswordConfirmation('');
        } catch (nextError) {
            setError(toApiError(nextError));
        } finally {
            setSubmitting(false);
        }
    }

    async function confirmMfa() {
        if (!enrollment || enrollmentCode.length !== 6) return;
        setError(null);
        setSubmitting(true);
        try {
            const confirmation = await authApi.confirmPlatformMfaEnrollment(
                enrollment.enrollment_proof,
                enrollmentCode,
            );
            setBackupCodes(confirmation.backup_codes);
            setEnrollment(null);
            setEnrollmentCode('');
        } catch (nextError) {
            setError(toApiError(nextError));
        } finally {
            setSubmitting(false);
        }
    }

    if (loading) return <LoadingState label="Validating your platform operator invitation..." fullPage />;

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <section className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p className="text-sm font-semibold uppercase tracking-wide text-sky-600">AutoERP platform</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-950">Complete platform operator registration</h1>
                <p className="mt-2 text-sm text-slate-600">
                    Create your password and secure the control-plane account with an authenticator before signing in.
                </p>

                <div className="mt-5">
                    <ErrorAlert error={error} title="Unable to complete registration" />
                    <SuccessAlert
                        title="Platform operator security is ready"
                        message={backupCodes ? 'Your password and MFA are configured. Store the backup codes before continuing.' : null}
                    />
                </div>

                {backupCodes ? (
                    <div className="mt-5 space-y-4">
                        <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                            <p className="font-semibold">Save these one-time backup codes now</p>
                            <div className="mt-3 grid grid-cols-2 gap-2 font-mono text-xs">
                                {backupCodes.map((code) => (
                                    <code key={code} className="rounded bg-white p-2 text-center">{code}</code>
                                ))}
                            </div>
                            <p className="mt-3">They will not be shown again. Store them separately from your password.</p>
                        </div>
                        <LinkButton className="w-full" to="/login">I stored the codes — continue to sign in</LinkButton>
                    </div>
                ) : enrollment ? (
                    <form className="mt-5 space-y-4" onSubmit={(event) => { event.preventDefault(); void confirmMfa(); }}>
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                            <p className="font-semibold">Set up multi-factor authentication</p>
                            <p className="mt-2">Add this account to an authenticator app, then enter the current six-digit code.</p>
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
                            error={fieldError(error, 'code')}
                            required
                        />
                        <Button type="submit" className="w-full" loading={submitting} disabled={enrollmentCode.length !== 6}>
                            Enable MFA
                        </Button>
                    </form>
                ) : invitation ? (
                    <form className="mt-5 space-y-4" onSubmit={(event) => { event.preventDefault(); void activateAccount(); }}>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            <p><span className="font-semibold">Operator:</span> {invitation.operator_name}</p>
                            <p className="mt-1"><span className="font-semibold">Email:</span> {invitation.email}</p>
                            <p className="mt-1"><span className="font-semibold">Expires:</span> {formatDateTime(invitation.expires_at)}</p>
                        </div>
                        <Input
                            label="Create password"
                            name="password"
                            type="password"
                            autoComplete="new-password"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            error={fieldError(error, 'password')}
                            hint="Use a strong password that you do not use on another service."
                            required
                        />
                        <Input
                            label="Confirm password"
                            name="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={passwordConfirmation}
                            onChange={(event) => setPasswordConfirmation(event.target.value)}
                            error={fieldError(error, 'password_confirmation')}
                            required
                        />
                        <Button type="submit" className="w-full" loading={submitting}>Set password and continue to MFA</Button>
                    </form>
                ) : (
                    <LinkButton className="mt-5 w-full" to="/login" variant="secondary">Return to sign in</LinkButton>
                )}
            </section>
        </main>
    );
}

function readInvitationToken(): string | null {
    const hash = new URLSearchParams(window.location.hash.replace(/^#/, '')).get('token');
    const query = new URLSearchParams(window.location.search).get('token');
    const value = (hash ?? query ?? '').trim();

    return /^[A-Za-z0-9]{72}$/.test(value) ? value : null;
}

function formatDateTime(value: string): string {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? 'Unknown' : date.toLocaleString();
}
