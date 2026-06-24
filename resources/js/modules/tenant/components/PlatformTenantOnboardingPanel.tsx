import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getTenantOnboardingReadiness, provisionTenantOnboarding } from '../tenantApi';
import type { TenantOnboardingProvisionResult, TenantRecord } from '../tenantTypes';

interface Props {
    tenant: TenantRecord;
    canProvision: boolean;
    disabled?: boolean;
    onTenantChanged: () => void;
}

export function PlatformTenantOnboardingPanel({ tenant, canProvision, disabled = false, onTenantChanged }: Props) {
    const readiness = useApi(
        (signal) => getTenantOnboardingReadiness(tenant.id, signal),
        [tenant.id, tenant.row_version, tenant.onboarding?.row_version],
    );
    const [emailInput, setEmailInput] = useState(tenant.onboarding?.initial_admin_email ?? '');
    const email = tenant.onboarding?.initial_admin_email ?? emailInput;
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [provisioned, setProvisioned] = useState<TenantOnboardingProvisionResult | null>(null);

    async function provision() {
        const normalized = email.trim().toLowerCase();
        if (normalized === '') return;

        setSaving(true);
        setError(null);
        try {
            const result = await provisionTenantOnboarding(tenant, normalized);
            setProvisioned(result);
            readiness.setData(result.readiness);
            onTenantChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            readiness.reload();
            onTenantChanged();
        } finally {
            setSaving(false);
        }
    }

    const state = provisioned?.state ?? tenant.onboarding;
    const currentReadiness = provisioned?.readiness ?? readiness.data;
    const canStart = canProvision && tenant.status === 'draft' && state?.status !== 'completed';

    return (
        <section className="space-y-4" aria-labelledby="tenant-onboarding-title">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 id="tenant-onboarding-title" className="font-semibold text-slate-900">1. Provision tenant foundation</h3>
                    <p className="mt-1 text-sm text-slate-500">Creates the root organization, access catalogue, authentication provider, Super Admin role, and first administrator invitation.</p>
                </div>
                <StatusBadge status={state?.status ?? 'pending'} />
            </div>

            <ErrorAlert error={readiness.error ?? error} />
            {readiness.loading && !currentReadiness ? <LoadingState label="Checking onboarding readiness..." /> : null}

            {canStart ? (
                <div className="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                    <Input
                        label="Initial administrator email"
                        type="email"
                        autoComplete="email"
                        value={email}
                        onChange={(event) => setEmailInput(event.target.value)}
                        disabled={disabled || saving || Boolean(tenant.onboarding?.initial_admin_email)}
                        hint={tenant.onboarding?.initial_admin_email
                            ? 'The initial administrator is fixed after provisioning starts. Revoke the invitation through the authentication workflow before changing it.'
                            : 'A one-time invitation will be created. The raw invitation token is shown only immediately after provisioning.'}
                        required
                    />
                    <Button
                        loading={saving}
                        disabled={disabled || email.trim() === ''}
                        onClick={() => void provision()}
                    >
                        Provision foundation
                    </Button>
                </div>
            ) : null}

            {provisioned?.invitation_token ? (
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                    <p className="font-semibold">Copy this one-time invitation token now</p>
                    <code className="mt-2 block break-all rounded bg-white p-3 font-mono text-xs">{provisioned.invitation_token}</code>
                    <p className="mt-2">Expires: {provisioned.invitation_expires_at ?? 'Not provided'}. It will not be returned again.</p>
                </div>
            ) : null}

            {currentReadiness ? (
                <div className="grid gap-2 sm:grid-cols-2">
                    {Object.entries(currentReadiness.checks).map(([key, passed]) => (
                        <div key={key} className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <span className="capitalize text-slate-700">{key.replaceAll('_', ' ')}</span>
                            <span className={passed ? 'font-medium text-emerald-700' : 'font-medium text-amber-700'}>{passed ? 'Ready' : 'Required'}</span>
                        </div>
                    ))}
                </div>
            ) : null}

            {currentReadiness?.blockers.length ? (
                <ul className="space-y-1 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                    {currentReadiness.blockers.map((blocker) => <li key={blocker.code}>• {blocker.message}</li>)}
                </ul>
            ) : null}
        </section>
    );
}
