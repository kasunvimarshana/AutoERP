import { FormEvent, useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { useAuthContext } from '../../../contexts/AuthContext';

type LoginLocationState = {
    from?: {
        pathname?: string;
    };
    message?: string;
};

function fieldMessage(errors: Record<string, string[]>, field: string): string | undefined {
    return errors[field]?.[0];
}

function loginErrorMessage(error: ApiError): string {
    const codeMessages: Record<string, string> = {
        AUTH_INVALID_CREDENTIALS: 'The email/username or password is incorrect.',
        AUTH_PROVIDER_NOT_FOUND: 'The internal authentication provider is not available for this tenant.',
        AUTH_SESSION_MISSING: 'Your session has expired. Please sign in again.',
        AUTH_TOKEN_INVALID: 'Your session token is invalid. Please sign in again.',
        AUTH_UNAUTHORIZED_ACCESS: 'Authentication could not be completed.',
    };

    return codeMessages[error.code] ?? error.message;
}

export function LoginPage() {
    const { isAuthenticated, isLoading, login, logout, user } = useAuthContext();
    const location = useLocation();
    const state = location.state as LoginLocationState | null;
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState(state?.message ?? '');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showPassword, setShowPassword] = useState(false);

    const devInfo = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return (
            <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500">
                <p className="font-bold uppercase tracking-wide text-slate-400">Local development</p>
                <p className="mt-1">Auth endpoint: /api/auth/login. Fresh local seed uses tenant ID 1; credentials come from AUTH_LOCAL_ADMIN_* env values.</p>
            </div>
        );
    }, []);

    if (isLoading) {
        return (
            <div className="flex items-center justify-center rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                <Spinner />
                <span className="ml-3 text-sm font-semibold text-slate-600">Checking session</span>
            </div>
        );
    }

    if (isAuthenticated && user) {
        return (
            <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 md:p-8">
                <p className="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Signed in</p>
                <h2 className="mt-2 text-2xl font-bold text-slate-950">{user.name}</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">
                    Your authentication session is active. Business application screens are not included in this frontend.
                </p>
                <Button className="mt-6 w-full" onClick={() => void logout()} variant="secondary">
                    Sign out
                </Button>
            </div>
        );
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');

        const formData = new FormData(event.currentTarget);
        const loginIdentifier = String(formData.get('login_identifier') ?? '').trim();
        const password = String(formData.get('password') ?? '');
        const tenantId = String(formData.get('tenant_id') ?? '').trim();
        const validationErrors: Record<string, string[]> = {};

        if (!loginIdentifier) {
            validationErrors.login_identifier = ['Email or username is required.'];
        }

        if (!password) {
            validationErrors.password = ['Password is required.'];
        }

        if (tenantId && (!Number.isInteger(Number(tenantId)) || Number(tenantId) < 1)) {
            validationErrors.tenant_id = ['Tenant ID must be a positive number for the current backend.'];
        }

        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);

            return;
        }

        setIsSubmitting(true);

        try {
            await login({
                loginIdentifier,
                password,
                remember: formData.get('remember') === 'on',
                tenantId: tenantId || undefined,
            });
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(loginErrorMessage(error));
            } else if (error instanceof TypeError) {
                setFormError('Unable to reach the backend auth service. Check the API base URL and network connection.');
            } else {
                setFormError('Unable to sign in due to an unexpected error.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 md:p-8">
            <div>
                <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Welcome back</p>
                <h2 className="mt-2 text-2xl font-bold tracking-normal text-slate-950">Sign in to AutoERP</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">Use your backend user credentials. Tenant ID is only needed for tenant-scoped users.</p>
            </div>

            {formError ? (
                <div className="mt-6 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {formError}
                </div>
            ) : null}

            <form className="mt-6 space-y-5" onSubmit={handleSubmit}>
                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="login_identifier">
                        Email / username
                    </label>
                    <Input
                        autoComplete="username"
                        autoFocus
                        disabled={isSubmitting}
                        id="login_identifier"
                        name="login_identifier"
                        placeholder="name@company.com"
                    />
                    <FieldError message={fieldMessage(errors, 'login_identifier')} />
                </div>

                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="password">
                        Password
                    </label>
                    <div className="relative">
                        <Input
                            autoComplete="current-password"
                            className="pr-16"
                            disabled={isSubmitting}
                            id="password"
                            name="password"
                            placeholder="Password"
                            type={showPassword ? 'text' : 'password'}
                        />
                        <button
                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                            className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                            disabled={isSubmitting}
                            onClick={() => setShowPassword((value) => !value)}
                            type="button"
                        >
                            {showPassword ? 'Hide' : 'Show'}
                        </button>
                    </div>
                    <FieldError message={fieldMessage(errors, 'password')} />
                </div>

                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="tenant_id">
                        Tenant ID <span className="font-semibold normal-case tracking-normal text-slate-400">(optional)</span>
                    </label>
                    <Input
                        disabled={isSubmitting}
                        id="tenant_id"
                        inputMode="numeric"
                        name="tenant_id"
                        placeholder="Leave blank for platform user"
                    />
                    <FieldError message={fieldMessage(errors, 'tenant_id')} />
                </div>

                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm font-semibold text-slate-600">
                        <Checkbox disabled={isSubmitting} name="remember" />
                        Remember me
                    </label>
                </div>

                <Button className="w-full" disabled={isSubmitting} type="submit" variant="blue">
                    {isSubmitting ? (
                        <>
                            <Spinner />
                            Signing in
                        </>
                    ) : (
                        'Sign in'
                    )}
                </Button>
            </form>

            <div className="mt-6 space-y-3">
                {devInfo}
                <p className="text-center text-xs leading-5 text-slate-400">
                    Tokens are stored only for the chosen browser session and are attached to API calls by the shared API client.
                </p>
            </div>
        </div>
    );
}
