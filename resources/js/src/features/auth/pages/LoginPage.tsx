import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useLocation, useNavigate } from 'react-router-dom';
import { ValidationError } from '../../../api/client';
import { Input } from '../../../components/forms/Input';
import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';
import { useAuth } from '../context/AuthContext';
import { useTenant } from '../context/TenantContext';

const loginSchema = z.object({
    tenant_id: z.preprocess((value) => {
        if (value === '' || value === null || value === undefined) {
            return undefined;
        }

        if (typeof value === 'number') {
            return value;
        }

        if (typeof value === 'string') {
            const parsed = Number(value);
            return Number.isNaN(parsed) ? value : parsed;
        }

        return value;
    }, z.number().int().positive('Tenant ID must be a positive number.').optional()),
    email: z.string().email('Enter a valid email address.'),
    password: z.string().min(1, 'Password is required.'),
});

type LoginFormValues = z.infer<typeof loginSchema>;
type LoginFormInput = z.input<typeof loginSchema>;

export function LoginPage() {
    const navigate = useNavigate();
    const location = useLocation();
    const { login } = useAuth();
    const { setTenantId, tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
        setError,
    } = useForm<LoginFormInput, unknown, LoginFormValues>({
        resolver: zodResolver(loginSchema),
        defaultValues: {
            tenant_id: tenantId,
            email: '',
            password: '',
        },
    });

    async function onSubmit(values: LoginFormValues) {
        setFormError(null);

        try {
            if (values.tenant_id) {
                setTenantId(values.tenant_id);
            }
            await login(values);

            const nextRoute = typeof location.state === 'object' && location.state && 'from' in location.state
                ? (location.state.from as { pathname?: string }).pathname ?? '/'
                : '/';

            navigate(nextRoute, { replace: true });
        } catch (error) {
            if (error instanceof ValidationError) {
                for (const [field, messages] of Object.entries(error.errors)) {
                    const message = messages[0];
                    if (!message) {
                        continue;
                    }

                    setError(field as keyof LoginFormValues, { message });
                }

                return;
            }

            setFormError(error instanceof Error ? error.message : 'Login failed.');
        }
    }

    return (
        <main className="flex min-h-screen items-center justify-center px-6 py-10">
            <Card className="w-full max-w-md p-8">
                <div className="space-y-2">
                    <p className="text-sm font-medium uppercase tracking-[0.18em] text-stone-500">AutoERP</p>
                    <h1 className="text-2xl font-semibold text-stone-950">Sign in</h1>
                    <p className="text-sm leading-6 text-stone-600">
                        Phase 1A is focused on wiring the app foundation. Use your Passport token login credentials to verify
                        the frontend and API connection.
                    </p>
                </div>

                <form className="mt-8 space-y-4" onSubmit={handleSubmit(onSubmit)}>
                    <Input
                        id="tenant_id"
                        label="Tenant ID"
                        type="number"
                        autoComplete="off"
                        placeholder="Leave blank for super admin"
                        error={errors.tenant_id?.message}
                        {...register('tenant_id', {
                            setValueAs: (value) => (value === '' ? undefined : Number(value)),
                        })}
                    />

                    <Input
                        id="email"
                        label="Email"
                        type="email"
                        autoComplete="email"
                        error={errors.email?.message}
                        {...register('email')}
                    />

                    <Input
                        id="password"
                        label="Password"
                        type="password"
                        autoComplete="current-password"
                        error={errors.password?.message}
                        {...register('password')}
                    />

                    {formError ? (
                        <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {formError}
                        </div>
                    ) : null}

                    <Button className="w-full" disabled={isSubmitting} type="submit">
                        {isSubmitting ? 'Signing in...' : 'Sign in'}
                    </Button>
                </form>
            </Card>
        </main>
    );
}
