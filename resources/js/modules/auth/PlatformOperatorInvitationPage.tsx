import { useEffect, useState } from 'react';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { authApi } from './authApi';
import type { PasswordPolicyRequirements, PlatformOperatorInvitationInspection } from './authTypes';
import { usePlatformOperatorInvitationToken } from './invitationToken';

export default function PlatformOperatorInvitationPage() {
    const token = usePlatformOperatorInvitationToken();
    const [invitation, setInvitation] = useState<PlatformOperatorInvitationInspection | null>(null);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [completed, setCompleted] = useState(false);
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
            await authApi.acceptPlatformOperatorInvitation({
                token,
                password,
                password_confirmation: passwordConfirmation,
            });
            setCompleted(true);
            setPassword('');
            setPasswordConfirmation('');
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
                    Create your password before signing in to the control plane.
                </p>

                <div className="mt-5">
                    <ErrorAlert error={error} title="Unable to complete registration" />
                    <SuccessAlert
                        title="Platform operator account is ready"
                        message={completed ? 'Your password is configured. Continue to sign in.' : null}
                    />
                </div>

                {completed ? (
                    <div className="mt-5 space-y-4">
                        <LinkButton className="w-full" to="/login">Continue to sign in</LinkButton>
                    </div>
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
                            minLength={invitation.password_policy.minimum_length}
                            hint={passwordPolicyHint(invitation.password_policy)}
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
                        <Button type="submit" className="w-full" loading={submitting}>Set password</Button>
                    </form>
                ) : (
                    <LinkButton className="mt-5 w-full" to="/login" variant="secondary">Return to sign in</LinkButton>
                )}
            </section>
        </main>
    );
}

function formatDateTime(value: string): string {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? 'Unknown' : date.toLocaleString();
}

function passwordPolicyHint(policy: PasswordPolicyRequirements): string {
    const requirements = [`at least ${policy.minimum_length} characters`];
    if (policy.mixed_case) requirements.push('uppercase and lowercase letters');
    if (policy.numbers) requirements.push('a number');
    if (policy.symbols) requirements.push('a symbol');

    return `Use ${requirements.join(', ')}. Choose a password you do not use on another service.`;
}
