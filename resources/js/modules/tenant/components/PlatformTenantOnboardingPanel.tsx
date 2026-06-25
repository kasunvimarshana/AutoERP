import { useMemo, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import {
    getInitialAdministratorInvitation,
    provisionTenantOnboarding,
    replaceInitialAdministratorInvitation,
    resendInitialAdministratorInvitation,
    revokeInitialAdministratorInvitation,
} from '../tenantApi';
import type {
    TenantAdministratorInvitationState,
    TenantOnboardingSummary,
    TenantRecord,
} from '../tenantTypes';
import { formatTenantDateTime } from '../tenantPresentation';

interface Props {
    tenant: TenantRecord;
    canProvision: boolean;
    disabled?: boolean;
    onTenantChanged: () => void;
}

type RecoveryAction = 'replace' | 'revoke' | null;

export function PlatformTenantOnboardingPanel({ tenant, canProvision, disabled = false, onTenantChanged }: Props) {
    const invitationRequest = useApi(
        (signal) => getInitialAdministratorInvitation(tenant.id, signal),
        [tenant.id, tenant.onboarding?.row_version],
        tenant.onboarding !== null,
        false,
    );
    const [emailInput, setEmailInput] = useState(tenant.onboarding?.initial_admin_email ?? '');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [provisionedState, setProvisionedState] = useState<TenantAdministratorInvitationState | null>(null);
    const [recoveryAction, setRecoveryAction] = useState<RecoveryAction>(null);
    const [replacementEmail, setReplacementEmail] = useState('');
    const [reason, setReason] = useState('');

    const state = provisionedState?.onboarding ?? invitationRequest.data?.onboarding ?? tenant.onboarding;
    const invitation = provisionedState?.invitation ?? invitationRequest.data?.invitation ?? null;
    const completedSteps = useMemo(() => new Set(state?.completed_steps ?? []), [state?.completed_steps]);
    const canRun = canProvision && tenant.status === 'draft' && state?.status !== 'completed';
    const operationDisabled = disabled || saving;

    async function run(operation: () => Promise<void>) {
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            await operation();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    function provision() {
        const normalized = emailInput.trim().toLowerCase();
        if (normalized === '') return;
        void run(async () => {
            const result = await provisionTenantOnboarding(tenant, normalized);
            setProvisionedState({ onboarding: result.state, invitation: result.invitation });
            setSuccess(result.invitation
                ? 'Tenant foundation is provisioned. The administrator invitation is being delivered through the configured mail channel.'
                : 'Tenant foundation is provisioned. Readiness has been recalculated.');
            invitationRequest.reload();
            onTenantChanged();
        });
    }

    function resendInvitation() {
        if (!invitation) return;
        void run(async () => {
            const updated = await resendInitialAdministratorInvitation(tenant.id, invitation);
            setProvisionedState(updated);
            setSuccess('Administrator invitation delivery was queued again.');
        });
    }

    function submitRecovery(event: FormEvent) {
        event.preventDefault();
        if (!invitationRequest.data && !provisionedState) return;
        const current = provisionedState ?? invitationRequest.data;
        if (!current || !recoveryAction) return;

        void run(async () => {
            const updated = recoveryAction === 'replace'
                ? await replaceInitialAdministratorInvitation(tenant.id, current, replacementEmail.trim().toLowerCase(), reason.trim())
                : await revokeInitialAdministratorInvitation(tenant.id, current, reason.trim());
            setProvisionedState(updated);
            setRecoveryAction(null);
            setReason('');
            setReplacementEmail('');
            setEmailInput(updated.onboarding.initial_admin_email ?? '');
            setSuccess(recoveryAction === 'replace'
                ? 'The previous invitation was revoked and a replacement invitation was queued.'
                : 'The administrator invitation was revoked. A new email can now be used when provisioning resumes.');
            onTenantChanged();
        });
    }

    return (
        <section id="tenant-foundation-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-onboarding-title">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 2</p>
                    <h3 id="tenant-onboarding-title" className="mt-1 font-semibold text-slate-900">Provision tenant foundation</h3>
                    <p className="mt-1 text-sm text-slate-500">Creates the root organization, permission catalogue, fully granted Super Admin role, authentication provider, and first administrator invitation.</p>
                </div>
                <StatusBadge status={state?.status ?? 'pending'} />
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={error ?? invitationRequest.error} title="Foundation operation failed" />

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

            {canRun ? (
                <div className="grid gap-3 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_auto] md:items-end">
                    <Input
                        label="Initial administrator email"
                        type="email"
                        autoComplete="email"
                        value={state?.initial_admin_email ?? emailInput}
                        onChange={(event) => setEmailInput(event.target.value)}
                        disabled={operationDisabled || Boolean(state?.initial_admin_email)}
                        hint={state?.initial_admin_email
                            ? 'This address belongs to the current invitation. Use Replace or Revoke to recover safely.'
                            : 'The invitation is delivered by email. Raw invitation tokens are never shown in Platform Administration.'}
                        required
                    />
                    <Button loading={saving} disabled={operationDisabled || emailInput.trim() === ''} onClick={provision}>
                        {provisionButtonLabel(state?.status)}
                    </Button>
                </div>
            ) : null}

            {invitation ? (
                <div className="space-y-3 rounded-lg border border-slate-200 p-4 text-sm">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="font-semibold text-slate-900">Initial administrator invitation</p>
                            <p className="mt-1 text-slate-600">{invitation.email}</p>
                        </div>
                        <div className="flex gap-2"><StatusBadge status={invitation.status} /><StatusBadge status={invitation.delivery_status} /></div>
                    </div>
                    <dl className="grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                        <div><dt className="font-medium text-slate-800">Delivery requested</dt><dd>{formatTenantDateTime(invitation.delivery_requested_at, 'Not requested')}</dd></div>
                        <div><dt className="font-medium text-slate-800">Delivered</dt><dd>{formatTenantDateTime(invitation.delivered_at, 'Not confirmed')}</dd></div>
                        <div><dt className="font-medium text-slate-800">Expires</dt><dd>{formatTenantDateTime(invitation.expires_at, 'No expiry recorded')}</dd></div>
                        <div><dt className="font-medium text-slate-800">Delivery attempts</dt><dd>{invitation.delivery_attempt_count}</dd></div>
                    </dl>
                    {invitation.delivery_error_message ? <p className="rounded bg-rose-50 p-3 text-rose-800">{invitation.delivery_error_message}</p> : null}
                    {invitation.status === 'pending' && canProvision ? (
                        <div className="flex flex-wrap justify-end gap-2">
                            <Button variant="secondary" disabled={operationDisabled} onClick={resendInvitation}>Resend invitation</Button>
                            <Button variant="secondary" disabled={operationDisabled} onClick={() => { setReplacementEmail(invitation.email); setRecoveryAction('replace'); }}>Replace email</Button>
                            <Button variant="danger" disabled={operationDisabled} onClick={() => setRecoveryAction('revoke')}>Revoke invitation</Button>
                        </div>
                    ) : null}
                </div>
            ) : null}

            {state?.provisioned_at ? <p className="text-xs text-slate-500">Foundation provisioned {formatTenantDateTime(state.provisioned_at)}.</p> : null}

            <Modal open={recoveryAction !== null} title={recoveryAction === 'replace' ? 'Replace administrator invitation' : 'Revoke administrator invitation'} onClose={() => { if (!saving) setRecoveryAction(null); }} closeDisabled={saving}>
                <form className="space-y-4" onSubmit={submitRecovery}>
                    {recoveryAction === 'replace' ? (
                        <Input label="New administrator email" type="email" value={replacementEmail} onChange={(event) => setReplacementEmail(event.target.value)} disabled={saving} required />
                    ) : null}
                    <Textarea label="Reason" value={reason} onChange={(event) => setReason(event.target.value)} disabled={saving} hint="At least 10 characters. This reason is retained in the platform audit trail." required />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" disabled={saving} onClick={() => setRecoveryAction(null)}>Cancel</Button>
                        <Button type="submit" variant={recoveryAction === 'revoke' ? 'danger' : 'primary'} loading={saving} disabled={reason.trim().length < 10 || (recoveryAction === 'replace' && replacementEmail.trim() === '')}>
                            {recoveryAction === 'replace' ? 'Replace invitation' : 'Revoke invitation'}
                        </Button>
                    </div>
                </form>
            </Modal>
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
    if (status === 'failed') return 'Retry failed step';
    if (status === 'provisioning') return 'Resume provisioning';
    if (status === 'awaiting_administrator') return 'Recheck foundation';
    if (status === 'awaiting_domain' || status === 'ready') return 'Refresh readiness';
    return 'Provision foundation';
}
