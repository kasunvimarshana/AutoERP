import { useState } from 'react';
import { errorDetail, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { changeTenantStatus, getTenantOnboardingReadiness } from '../tenantApi';
import type { TenantOnboardingReadiness, TenantRecord } from '../tenantTypes';
import { TenantReadinessSummary } from './TenantReadinessSummary';

interface Props {
    tenant: TenantRecord;
    canActivate: boolean;
    disabled?: boolean;
    onChanged: (tenant: TenantRecord) => void;
}

const ACTIVATABLE_STATUSES = new Set<TenantRecord['status']>(['draft', 'inactive', 'suspended']);

export function PlatformTenantActivationPanel({ tenant, canActivate, disabled = false, onChanged }: Props) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const readiness = useApi(
        (signal) => getTenantOnboardingReadiness(tenant.id, signal),
        [tenant.id, tenant.row_version, tenant.onboarding?.row_version],
        tenant.status !== 'archived',
        false,
    );
    const [reason, setReason] = useState('');
    const [activating, setActivating] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const canAttempt = canActivate
        && ACTIVATABLE_STATUSES.has(tenant.status)
        && readiness.data?.ready === true
        && reason.trim().length >= 10;

    async function activate() {
        if (!canAttempt) return;
        const confirmed = await confirm({
            title: 'Activate tenant workspace',
            message: (
                <div className="space-y-2">
                    <p>Activate <strong>{tenant.name}</strong> using the routing mode confirmed by the backend?</p>
                    <p>The lifecycle reason recorded in the audit log is: “{reason.trim()}”.</p>
                </div>
            ),
            confirmLabel: 'Activate tenant',
            danger: false,
        });
        if (!confirmed) return;

        setActivating(true);
        setActionError(null);
        setSuccess(null);
        try {
            const updated = await changeTenantStatus(tenant.id, 'activate', tenant.row_version, reason.trim());
            setReason('');
            setSuccess(`${updated.name} is active. Tenant routing is available through the backend-confirmed route.`);
            onChanged(updated);
        } catch (requestError: unknown) {
            const nextError = toApiError(requestError);
            const latestReadiness = errorDetail<TenantOnboardingReadiness>(nextError, 'readiness');
            if (latestReadiness) readiness.setData(latestReadiness);
            setActionError(nextError);
        } finally {
            setActivating(false);
        }
    }

    return (
        <section id="tenant-activation-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-activation-title">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 5</p>
                <h3 id="tenant-activation-title" className="mt-1 font-semibold text-slate-900">Final readiness and activation</h3>
                <p className="mt-1 text-sm text-slate-500">Review every requirement here. Activation remains unavailable until the backend confirms all checks.</p>
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={readiness.error} title="Unable to check activation readiness" />
            <ErrorAlert error={actionError} title="Activation could not be completed" />
            {readiness.loading && !readiness.data ? <LoadingState label="Checking activation readiness..." /> : null}
            {readiness.data ? <TenantReadinessSummary readiness={readiness.data} /> : null}

            {tenant.status === 'active' ? (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p className="font-semibold">Tenant is active</p>
                    <p className="mt-1">Users can access the workspace through {tenant.primary_domain?.domain ?? (readiness.data?.routing.mode === 'local_fallback' ? 'the configured local/testing route' : 'the backend-confirmed tenant route')}.</p>
                </div>
            ) : tenant.status === 'archived' ? (
                <p className="rounded-lg bg-slate-100 p-4 text-sm text-slate-600">Archived tenants cannot be activated or modified.</p>
            ) : canActivate ? (
                <div className="space-y-3 rounded-lg border border-slate-200 p-4">
                    <Textarea
                        label="Activation reason"
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                        disabled={disabled || activating}
                        placeholder="Example: Onboarding, DNS verification, and subscription approval completed by Platform Operations."
                        hint="Enter at least 10 characters. This reason is retained in the platform audit trail."
                    />
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-slate-500">
                            {readiness.data?.ready
                                ? 'All checks passed. Review the reason and activate the tenant.'
                                : 'Complete the requirements above before activation.'}
                        </p>
                        <Button
                            loading={activating}
                            disabled={disabled || !canAttempt}
                            onClick={() => void activate()}
                        >
                            Activate tenant
                        </Button>
                    </div>
                </div>
            ) : (
                <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    You have read-only access. Tenant activation requires the tenant lifecycle permission.
                </p>
            )}
            {confirmDialog}
        </section>
    );
}
