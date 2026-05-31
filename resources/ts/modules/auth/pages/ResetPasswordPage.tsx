import { FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { authApi } from '../services/authApi';

export function ResetPasswordPage() {
    const [message, setMessage] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setMessage('');
        setErrors({});

        const formData = new FormData(event.currentTarget);
        const token = String(formData.get('token') ?? '').trim();
        const password = String(formData.get('password') ?? '');
        const passwordConfirmation = String(formData.get('password_confirmation') ?? '');
        const validationErrors: Record<string, string> = {};

        if (!token) {
            validationErrors.token = 'Reset token is required.';
        }

        if (!password) {
            validationErrors.password = 'New password is required.';
        }

        if (password !== passwordConfirmation) {
            validationErrors.password_confirmation = 'Password confirmation must match.';
        }

        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);

            return;
        }

        try {
            await authApi.resetPassword({ password, passwordConfirmation, token });
        } catch (error) {
            if (error instanceof ApiError && error.code === 'AUTH_ENDPOINT_UNAVAILABLE') {
                setMessage('Password reset is not enabled in the current backend API yet. Contact an administrator to reset access.');

                return;
            }

            setMessage(error instanceof Error ? error.message : 'Unable to reset password.');
        }
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 md:p-8">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Account recovery</p>
            <h2 className="mt-2 text-2xl font-bold tracking-normal text-slate-950">Reset password</h2>
            <p className="mt-2 text-sm leading-6 text-slate-500">This placeholder will submit to the backend reset endpoint once that route exists.</p>

            {message ? <div className="mt-6 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{message}</div> : null}

            <form className="mt-6 space-y-5" onSubmit={handleSubmit}>
                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="token">Reset token</label>
                    <Input id="token" name="token" placeholder="Paste reset token" />
                    <FieldError message={errors.token} />
                </div>
                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="password">New password</label>
                    <Input autoComplete="new-password" id="password" name="password" type="password" />
                    <FieldError message={errors.password} />
                </div>
                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="password_confirmation">Confirm password</label>
                    <Input autoComplete="new-password" id="password_confirmation" name="password_confirmation" type="password" />
                    <FieldError message={errors.password_confirmation} />
                </div>
                <Button className="w-full" type="submit" variant="blue">Reset password</Button>
            </form>

            <div className="mt-6 text-center">
                <Link className="text-sm font-bold text-slate-900 hover:text-blue-700" to="/login">Back to sign in</Link>
            </div>
        </div>
    );
}
