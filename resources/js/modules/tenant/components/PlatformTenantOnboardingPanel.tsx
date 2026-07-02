import { useMemo, useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { provisionTenantOnboarding } from '../tenantApi';
import type { TenantOnboardingSummary, TenantRecord } from '../tenantTypes';
import { formatTenantDateTime } from '../tenantPresentation';

interface Props {
    tenant: TenantRecord;
    canProvision: boolean;
    disabled?: boolean;
    onTenantChanged: () => void;
}

interface AdministratorForm {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

const emptyAdministratorForm = (tenant: TenantRecord): AdministratorForm => ({
    first_name: '',
    last_name: '',
    email: tenant.onboarding?.initial_admin_email ?? '',
    password: '',
    password_confirmation: '',
});

export function PlatformTenantOnboardingPanel({ tenant, canProvision, disabled = false, onTenantChanged }: Props) {
    const [form, setForm] = useState<AdministratorForm>(() => emptyAdministratorForm(tenant));
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [provisionedState, setProvisionedState] = useState<TenantOnboardingSummary | null>(null);

    const state = provisionedState ?? tenant.onboarding;
    const completedSteps = useMemo(() => new Set(state?.completed_steps ?? []), [state?.completed_steps]);
    const operationDisabled = disabled || saving;
    const administratorCreated = Boolean(state?.administrator_user_id);
    const canCreateAdministrator = canProvision && tenant.status === 'draft' && !administratorCreated;

    function set(patch: Partial<AdministratorForm>) {
        setForm((current) => ({ ...current, ...patch }));
    }

    async function provision(event: FormEvent) {
        event.preventDefault();
        if (!canCreateAdministrator || operationDisabled) return;

        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            const result = await provisionTenantOnboarding(tenant, {
                firstName: form.first_name.trim(),
                lastName: form.last_name.trim() || null,
                email: form.email.trim().toLowerCase(),
                password: form.password,
                passwordConfirmation: form.password_confirmation,
            });
            setProvisionedState(result.state);
            setForm((current) => ({ ...current, password: '', password_confirmation: '' }));
            setSuccess(`Tenant foundation is provisioned and ${result.administrator?.email ?? form.email} is an active Super Admin.`);
            onTenantChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    return (
        <section id="tenant-foundation-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-onboarding-title">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 2</p>
                    <h3 id="tenant-onboarding-title" className="mt-1 font-semibold text-slate-900">Provision tenant foundation</h3>
                    <p className="mt-1 text-sm text-slate-500">Creates the protected root organization, permission catalogue, Super Admin role, authentication provider, and initial administrator account.</p>
                </div>
                <StatusBadge status={state?.status ?? 'pending'} />
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={error} title="Foundation operation failed" />

            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {FOUNDATION_STEPS.map((step) => {
                    const detail = state?.steps?.find((candidate) => candidate.step === step.key);
                    const status = detail?.status ?? (completedSteps.has(step.key) ? 'completed' : 'pending');
                    return (
                        <div key={step.key} className={`rounded-lg border p-3 text-sm ${status === 'completed' ? 'border-emerald-200 bg-emerald-50' : status === 'failed' ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50'}`}>
                            <p className="font-medium text-slate-900">{step.label}</p>
                            <div className="mt-1 flex items-center justify-between gap-2">
                                <StatusBadge status={status} />
                                {detail && detail.attempt_count > 0 ? <span className="text-xs text-slate-500">Attempt {detail.attempt_count}</span> : null}
                            </div>
                            {detail?.error_message ? <p className="mt-2 text-xs text-rose-700">{detail.error_message}</p> : null}
                        </div>
                    );
                })}
            </div>

            {state?.last_error_message ? (
                <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p className="font-semibold">Previous provisioning attempt</p>
                    <p className="mt-1">{state.last_error_message}</p>
                    {state.correlation_id ? <p className="mt-2 text-xs">Support reference: <span className="font-mono">{state.correlation_id}</span></p> : null}
                </div>
            ) : null}

            {canCreateAdministrator ? (
                <form className="space-y-4 rounded-lg border border-slate-200 p-4" onSubmit={provision}>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="First name" required value={form.first_name} error={fieldError(error, 'initial_admin_first_name')} onChange={(event) => set({ first_name: event.target.value })} />
                        <Input label="Last name" value={form.last_name} error={fieldError(error, 'initial_admin_last_name')} onChange={(event) => set({ last_name: event.target.value })} />
                    </div>
                    <Input
                        label="Initial administrator email"
                        type="email"
                        autoComplete="email"
                        required
                        value={form.email}
                        error={fieldError(error, 'initial_admin_email')}
                        onChange={(event) => set({ email: event.target.value })}
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Initial password"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={form.password}
                            error={fieldError(error, 'initial_admin_password')}
                            hint="Use the authentication password policy. Share it with the administrator through a secure channel."
                            onChange={(event) => set({ password: event.target.value })}
                        />
                        <Input
                            label="Confirm password"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={form.password_confirmation}
                            error={fieldError(error, 'initial_admin_password_confirmation')}
                            onChange={(event) => set({ password_confirmation: event.target.value })}
                        />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" loading={saving} disabled={operationDisabled}>{provisionButtonLabel(state?.status)}</Button>
                    </div>
                </form>
            ) : null}

            {administratorCreated ? (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p className="font-semibold">Initial administrator account created</p>
                    <p className="mt-1">{state?.initial_admin_email}</p>
                </div>
            ) : null}

            {!canProvision ? (
                <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    You have read-only access. Foundation provisioning requires the tenant onboarding permission.
                </p>
            ) : null}

            {state?.provisioned_at ? <p className="text-xs text-slate-500">Foundation provisioned {formatTenantDateTime(state.provisioned_at)}.</p> : null}
        </section>
    );
}

const FOUNDATION_STEPS = [
    { key: 'root_organization', label: 'Root organization' },
    { key: 'permission_catalogue', label: 'Permission catalogue' },
    { key: 'super_admin_role', label: 'Super Admin role' },
    { key: 'authentication_provider', label: 'Authentication provider' },
    { key: 'initial_admin_account', label: 'Administrator account' },
] as const;

function provisionButtonLabel(status: TenantOnboardingSummary['status'] | undefined): string {
    if (status === 'failed') return 'Retry failed step';
    if (status === 'provisioning') return 'Resume provisioning';
    return 'Provision foundation';
}
