import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CopyButton } from '@/shared/components/CopyButton';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { provisionTenantOnboarding } from '../tenantApi';
import type { TenantOnboardingProvisionResult, TenantOnboardingSummary, TenantRecord } from '../tenantTypes';
import { formatTenantDateTime } from '../tenantPresentation';

interface Props {
    tenant: TenantRecord;
    canProvision: boolean;
    disabled?: boolean;
    onTenantChanged: () => void;
}

export function PlatformTenantOnboardingPanel({ tenant, canProvision, disabled = false, onTenantChanged }: Props) {
    const [emailInput, setEmailInput] = useState(tenant.onboarding?.initial_admin_email ?? '');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [provisioned, setProvisioned] = useState<TenantOnboardingProvisionResult | null>(null);
    const [invitationAcknowledged, setInvitationAcknowledged] = useState(false);
    const state = provisioned?.state ?? tenant.onboarding;
    const email = state?.initial_admin_email ?? emailInput;
    const completedSteps = useMemo(() => new Set(state?.completed_steps ?? []), [state?.completed_steps]);
    const canRun = canProvision && tenant.status === 'draft' && state?.status !== 'completed';

    async function provision() {
        const normalized = email.trim().toLowerCase();
        if (normalized === '') return;

        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            const result = await provisionTenantOnboarding(tenant, normalized);
            setProvisioned(result);
            setInvitationAcknowledged(false);
            setSuccess(result.invitation_token
                ? 'Tenant foundation was provisioned. Copy the one-time administrator invitation before continuing.'
                : 'Tenant foundation is complete and ready for domain verification.');
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
                    <p className="mt-1 text-sm text-slate-500">Creates the root organization, permissions, Super Admin role, authentication provider, and first administrator invitation.</p>
                </div>
                <StatusBadge status={state?.status ?? 'pending'} />
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={error} title="Foundation provisioning failed" />

            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {FOUNDATION_STEPS.map((step) => {
                    const completed = completedSteps.has(step.key);
                    return (
                        <div key={step.key} className={`rounded-lg border p-3 text-sm ${completed ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'}`}>
                            <p className="font-medium text-slate-900">{step.label}</p>
                            <p className={`mt-1 text-xs font-semibold ${completed ? 'text-emerald-700' : 'text-slate-500'}`}>{completed ? 'Completed' : 'Pending'}</p>
                        </div>
                    );
                })}
            </div>

            {state?.last_error ? (
                <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p className="font-semibold">Previous provisioning attempt failed</p>
                    <p className="mt-1">{state.last_error}</p>
                </div>
            ) : null}

            {canRun ? (
                <div className="grid gap-3 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_auto] md:items-end">
                    <Input
                        label="Initial administrator email"
                        type="email"
                        autoComplete="email"
                        value={email}
                        onChange={(event) => setEmailInput(event.target.value)}
                        disabled={disabled || saving || Boolean(state?.initial_admin_email)}
                        hint={state?.initial_admin_email
                            ? 'This invitation identity is fixed once provisioning starts. Revoke it through the authentication workflow before changing it.'
                            : 'A single-use invitation is created. The raw token is shown only immediately after provisioning.'}
                        required
                    />
                    <Button
                        loading={saving}
                        disabled={disabled || email.trim() === ''}
                        onClick={() => void provision()}
                    >
                        {provisionButtonLabel(state?.status)}
                    </Button>
                </div>
            ) : null}

            {provisioned?.invitation_token && !invitationAcknowledged ? (
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                    <p className="font-semibold">One-time administrator invitation</p>
                    <p className="mt-1">Copy and store this token securely. It is not returned by later API responses.</p>
                    <code className="mt-3 block break-all rounded bg-white p-3 font-mono text-xs">{provisioned.invitation_token}</code>
                    <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <p>Expires {formatTenantDateTime(provisioned.invitation_expires_at, 'at an unknown time')}.</p>
                        <div className="flex gap-2">
                            <CopyButton value={provisioned.invitation_token} label="Copy invitation token" />
                            <Button variant="secondary" onClick={() => setInvitationAcknowledged(true)}>I stored it</Button>
                        </div>
                    </div>
                </div>
            ) : null}

            {state?.provisioned_at ? (
                <p className="text-xs text-slate-500">Foundation provisioned {formatTenantDateTime(state.provisioned_at)}.</p>
            ) : null}
        </section>
    );
}

const FOUNDATION_STEPS = [
    { key: 'organization_structure', label: 'Root organization' },
    { key: 'permission_catalogue', label: 'Permission catalogue' },
    { key: 'super_admin_role', label: 'Super Admin role' },
    { key: 'authentication_provider', label: 'Authentication provider' },
    { key: 'initial_admin_invitation', label: 'Administrator invitation' },
] as const;

function provisionButtonLabel(status: TenantOnboardingSummary['status'] | undefined): string {
    if (status === 'failed') return 'Retry foundation provisioning';
    if (status === 'provisioning') return 'Resume foundation provisioning';
    if (status === 'awaiting_domain' || status === 'ready') return 'Recheck foundation';
    return 'Provision foundation';
}
