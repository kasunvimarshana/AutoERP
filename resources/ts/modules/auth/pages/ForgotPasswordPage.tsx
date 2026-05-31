import { FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { authApi } from '../services/authApi';

export function ForgotPasswordPage() {
    const [message, setMessage] = useState('');
    const [fieldError, setFieldError] = useState('');

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setMessage('');
        setFieldError('');

        const formData = new FormData(event.currentTarget);
        const loginIdentifier = String(formData.get('login_identifier') ?? '').trim();

        if (!loginIdentifier) {
            setFieldError('Email or username is required.');

            return;
        }

        try {
            await authApi.forgotPassword({ loginIdentifier });
        } catch (error) {
            if (error instanceof ApiError && error.code === 'AUTH_ENDPOINT_UNAVAILABLE') {
                setMessage('Password recovery is not enabled in the current backend API yet. Contact an administrator to reset access.');

                return;
            }

            setMessage(error instanceof Error ? error.message : 'Unable to request password recovery.');
        }
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 md:p-8">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Account recovery</p>
            <h2 className="mt-2 text-2xl font-bold tracking-normal text-slate-950">Forgot password</h2>
            <p className="mt-2 text-sm leading-6 text-slate-500">This screen is ready for the backend password recovery endpoint when it is enabled.</p>

            {message ? <div className="mt-6 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{message}</div> : null}

            <form className="mt-6 space-y-5" onSubmit={handleSubmit}>
                <div className="space-y-2">
                    <label className="text-xs font-bold uppercase tracking-wide text-slate-500" htmlFor="login_identifier">
                        Email / username
                    </label>
                    <Input autoComplete="username" id="login_identifier" name="login_identifier" placeholder="name@company.com" />
                    <FieldError message={fieldError} />
                </div>
                <Button className="w-full" type="submit" variant="blue">Request reset</Button>
            </form>

            <div className="mt-6 text-center">
                <Link className="text-sm font-bold text-slate-900 hover:text-blue-700" to="/login">Back to sign in</Link>
            </div>
        </div>
    );
}
