import { useEffect, useState } from 'react';
import { LinkButton, Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { authApi } from './authApi';
import type { InitialAdministratorInvitationInspection } from './authTypes';

export default function InitialAdministratorInvitationPage() {
    const [token] = useState(() => readInvitationToken());
    const [invitation, setInvitation] = useState<InitialAdministratorInvitationInspection | null>(null);
    const [firstName, setFirstName] = useState('');
    const [lastName, setLastName] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState<ApiError | null>(() => token
        ? null
        : new ApiError('This invitation link is incomplete. Request a new invitation from the platform administrator.', 404));
    const [loading, setLoading] = useState(token !== null);
    const [submitting, setSubmitting] = useState(false);
    const [completed, setCompleted] = useState(false);

    useEffect(() => {
        document.title = 'Administrator registration - AutoERP';
        if (!token) return;

        const controller = new AbortController();
        authApi.inspectInitialAdministratorInvitation(token, controller.signal)
            .then(setInvitation)
            .catch((nextError) => setError(toApiError(nextError)))
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, [token]);

    async function submit() {
        if (!token || !invitation) return;
        setError(null);
        setSubmitting(true);
        try {
            await authApi.acceptInitialAdministratorInvitation({
                token,
                first_name: firstName.trim(),
                last_name: lastName.trim() || null,
                password,
                password_confirmation: passwordConfirmation,
            });
            window.history.replaceState(null, '', '/register/invitation');
            setCompleted(true);
        } catch (nextError) {
            setError(toApiError(nextError));
        } finally {
            setSubmitting(false);
        }
    }

    if (loading) return <LoadingState label="Validating your administrator invitation..." fullPage />;

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <section className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p className="text-sm font-semibold uppercase tracking-wide text-sky-600">AutoERP</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-950">Complete administrator registration</h1>
                <p className="mt-2 text-sm text-slate-600">
                    Create the first administrator account for the invited tenant. The invitation can be used only once.
                </p>

                <div className="mt-5">
                    <ErrorAlert error={error} title="Unable to use this invitation" />
                    <SuccessAlert
                        title="Administrator account created"
                        message={completed ? 'Your account is ready. Sign in using the invited email address and the password you just created.' : null}
                    />
                </div>

                {completed ? (
                    <LinkButton className="mt-5 w-full" to="/login">Continue to sign in</LinkButton>
                ) : invitation ? (
                    <form className="mt-5 space-y-4" onSubmit={(event) => { event.preventDefault(); void submit(); }}>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            <p><span className="font-semibold">Tenant:</span> {invitation.tenant_name}</p>
                            <p className="mt-1"><span className="font-semibold">Invited email:</span> {invitation.email}</p>
                            <p className="mt-1"><span className="font-semibold">Expires:</span> {formatDateTime(invitation.expires_at)}</p>
                        </div>
                        <Input
                            label="First name"
                            name="first_name"
                            autoComplete="given-name"
                            value={firstName}
                            onChange={(event) => setFirstName(event.target.value)}
                            error={fieldError(error, 'first_name')}
                            required
                        />
                        <Input
                            label="Last name"
                            name="last_name"
                            autoComplete="family-name"
                            value={lastName}
                            onChange={(event) => setLastName(event.target.value)}
                            error={fieldError(error, 'last_name')}
                        />
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
                        <Button type="submit" className="w-full" loading={submitting}>Create administrator account</Button>
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

    return /^[a-f0-9]{64}$/i.test(value) ? value : null;
}

function formatDateTime(value: string): string {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? 'Unknown' : date.toLocaleString();
}
