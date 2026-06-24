import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { CopyButton } from '@/shared/components/CopyButton';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { useApi } from '@/shared/hooks/useApi';
import {
    changePlatformTenantDomain,
    createPlatformTenantDomain,
    deletePlatformTenantDomain,
    listPlatformTenantDomains,
    requestPlatformDomainVerification,
    verifyPlatformTenantDomain,
} from '../tenantApi';
import {
    formatTenantDateTime,
    hostnameError,
    isFuture,
    normalizeHostname,
} from '../tenantPresentation';
import type { DomainVerificationChallenge, TenantDomain, TenantRecord } from '../tenantTypes';

interface Props {
    tenant: TenantRecord;
    canManage: boolean;
    disabled?: boolean;
    onChanged: () => void;
}

type DomainAction = 'primary' | 'disable' | 'delete';
type PendingAction = { action: DomainAction; domain: TenantDomain } | null;

export function PlatformTenantDomainsPanel({ tenant, canManage, disabled = false, onChanged }: Props) {
    const domains = useApi((signal) => listPlatformTenantDomains(tenant.id, signal), [tenant.id], true, false);
    const [domainName, setDomainName] = useState('');
    const [busyId, setBusyId] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [challenge, setChallenge] = useState<{ domainId: number; value: DomainVerificationChallenge } | null>(null);
    const [pendingAction, setPendingAction] = useState<PendingAction>(null);
    const normalizedDomain = normalizeHostname(domainName);
    const validationError = domainName.trim() === '' ? null : hostnameError(domainName);
    const mutationDisabled = disabled || busyId !== null || tenant.status === 'archived';

    function replaceDomain(updated: TenantDomain) {
        domains.setData((domains.data ?? []).map((domain) => domain.id === updated.id ? updated : domain));
    }

    async function createDomain() {
        const nextError = hostnameError(domainName);
        if (nextError) return;
        setBusyId(0);
        setError(null);
        setSuccess(null);
        try {
            const created = await createPlatformTenantDomain(tenant.id, normalizedDomain);
            domains.setData([...(domains.data ?? []), created]);
            setDomainName('');
            setSuccess(`${created.domain} was added. Request its DNS challenge before verification.`);
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    }

    async function requestChallenge(domain: TenantDomain) {
        setBusyId(domain.id);
        setError(null);
        setSuccess(null);
        try {
            const result = await requestPlatformDomainVerification(tenant.id, domain);
            replaceDomain(result.data);
            setChallenge({ domainId: domain.id, value: result.challenge });
            setSuccess(`DNS verification instructions were generated for ${domain.domain}.`);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    }

    async function verify(domain: TenantDomain) {
        setBusyId(domain.id);
        setError(null);
        setSuccess(null);
        try {
            const updated = await verifyPlatformTenantDomain(tenant.id, domain);
            replaceDomain(updated);
            setChallenge(null);
            setSuccess(`${updated.domain} was verified successfully.`);
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    }

    async function confirmAction() {
        if (!pendingAction) return;
        const target = pendingAction;
        setPendingAction(null);
        setBusyId(target.domain.id);
        setError(null);
        setSuccess(null);
        try {
            if (target.action === 'delete') {
                await deletePlatformTenantDomain(tenant.id, target.domain);
                domains.setData((domains.data ?? []).filter((domain) => domain.id !== target.domain.id));
                setSuccess(`${target.domain.domain} was deleted.`);
            } else {
                const updated = await changePlatformTenantDomain(tenant.id, target.domain, target.action);
                if (target.action === 'primary') {
                    domains.setData((domains.data ?? []).map((domain) => ({
                        ...domain,
                        is_primary: domain.id === updated.id,
                        row_version: domain.id === updated.id ? updated.row_version : domain.row_version,
                    })));
                    setSuccess(`${updated.domain} is now the primary tenant domain.`);
                } else {
                    replaceDomain(updated);
                    setSuccess(`${updated.domain} was disabled.`);
                }
            }
            if (challenge?.domainId === target.domain.id) setChallenge(null);
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            domains.reload();
        } finally {
            setBusyId(null);
        }
    }

    const pendingMessage = pendingAction ? actionMessage(pendingAction, domains.data ?? [], tenant.status) : null;

    return (
        <section id="tenant-domain-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-domains-title">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 3</p>
                <h3 id="tenant-domains-title" className="mt-1 font-semibold text-slate-900">Verify a primary tenant domain</h3>
                <p className="mt-1 text-sm text-slate-500">Add a hostname, publish its DNS TXT record, verify ownership, then select one verified domain as primary.</p>
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={domains.error} title="Unable to load tenant domains" />
            <ErrorAlert error={error} title="Domain action failed" />

            {canManage && tenant.status !== 'archived' ? (
                <div className="grid gap-3 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_auto] md:items-end">
                    <Input
                        label="Tenant hostname"
                        value={domainName}
                        onChange={(event) => setDomainName(event.target.value)}
                        placeholder="erp.example.com"
                        hint="Hostname only. Protocols, ports, paths, queries, and fragments are not accepted."
                        error={validationError ?? undefined}
                        disabled={mutationDisabled}
                    />
                    <Button
                        disabled={mutationDisabled || normalizedDomain === '' || Boolean(validationError)}
                        loading={busyId === 0}
                        onClick={() => void createDomain()}
                    >
                        Add domain
                    </Button>
                </div>
            ) : null}

            {domains.loading && !domains.data ? <LoadingState label="Loading tenant domains..." /> : null}
            {domains.loading && domains.data ? <p className="text-sm text-slate-500">Refreshing domains...</p> : null}

            <div className="space-y-3">
                {(domains.data ?? []).map((domain) => {
                    const activeChallenge = challenge?.domainId === domain.id || isFuture(domain.verification_expires_at);
                    return (
                        <article key={domain.id} className="rounded-lg border border-slate-200 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-semibold text-slate-900">{domain.domain}</p>
                                        <StatusBadge status={domain.status} />
                                        {domain.is_primary ? <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">Primary access domain</span> : null}
                                    </div>
                                    <p className="mt-2 text-xs text-slate-500">
                                        {domain.verified_at
                                            ? `Verified ${formatTenantDateTime(domain.verified_at)}`
                                            : domain.verification_expires_at
                                                ? `Verification challenge expires ${formatTenantDateTime(domain.verification_expires_at)}`
                                                : 'DNS challenge has not been requested.'}
                                    </p>
                                </div>
                                {canManage && tenant.status !== 'archived' ? (
                                    <div className="flex flex-wrap gap-2">
                                        {domain.status === 'pending' ? (
                                            <Button variant="secondary" disabled={mutationDisabled} loading={busyId === domain.id} onClick={() => void requestChallenge(domain)}>
                                                {activeChallenge ? 'Refresh DNS instructions' : 'Get DNS instructions'}
                                            </Button>
                                        ) : null}
                                        {domain.status === 'pending' ? (
                                            <Button disabled={mutationDisabled || !activeChallenge} loading={busyId === domain.id} onClick={() => void verify(domain)}>
                                                Verify DNS
                                            </Button>
                                        ) : null}
                                        {domain.status === 'active' && !domain.is_primary ? (
                                            <Button variant="secondary" disabled={mutationDisabled} onClick={() => setPendingAction({ action: 'primary', domain })}>Make primary</Button>
                                        ) : null}
                                        {domain.status !== 'disabled' ? (
                                            <Button variant="secondary" disabled={mutationDisabled} onClick={() => setPendingAction({ action: 'disable', domain })}>Disable</Button>
                                        ) : null}
                                        {!domain.is_primary ? (
                                            <Button variant="danger" disabled={mutationDisabled} onClick={() => setPendingAction({ action: 'delete', domain })}>Delete</Button>
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>

                            {challenge?.domainId === domain.id ? (
                                <div className="mt-4 space-y-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                                    <DnsValue label="TXT host" value={challenge.value.host} />
                                    <DnsValue label="TXT value" value={challenge.value.value} />
                                    <p className="text-xs text-slate-500">Expires {formatTenantDateTime(challenge.value.expires_at)}. Publish the record, allow DNS to propagate, then select Verify DNS.</p>
                                </div>
                            ) : null}
                        </article>
                    );
                })}
                {(domains.data ?? []).length === 0 && !domains.loading ? (
                    <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No tenant domain exists. Add and verify one before assigning it as primary.</p>
                ) : null}
            </div>

            <ConfirmDialog
                open={pendingAction !== null}
                title={pendingAction ? actionTitle(pendingAction.action) : 'Confirm domain action'}
                message={pendingMessage}
                confirmLabel={pendingAction ? actionConfirmLabel(pendingAction.action) : 'Confirm'}
                danger={pendingAction?.action !== 'primary'}
                loading={busyId !== null}
                onCancel={() => setPendingAction(null)}
                onConfirm={() => void confirmAction()}
            />
        </section>
    );
}

function DnsValue({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-2 sm:grid-cols-[7rem_1fr_auto] sm:items-center">
            <strong>{label}</strong>
            <code className="break-all rounded bg-white px-3 py-2 font-mono text-xs">{value}</code>
            <CopyButton value={value} label={`Copy ${label.toLowerCase()}`} />
        </div>
    );
}

function actionTitle(action: DomainAction): string {
    if (action === 'primary') return 'Change primary tenant domain';
    if (action === 'disable') return 'Disable tenant domain';
    return 'Delete tenant domain';
}

function actionConfirmLabel(action: DomainAction): string {
    if (action === 'primary') return 'Make primary';
    if (action === 'disable') return 'Disable domain';
    return 'Delete domain';
}

function actionMessage(pending: Exclude<PendingAction, null>, domains: TenantDomain[], tenantStatus: TenantRecord['status']) {
    const verifiedFallback = domains.find((domain) => domain.id !== pending.domain.id && domain.status === 'active');
    if (pending.action === 'primary') {
        return <p>Use <strong>{pending.domain.domain}</strong> as the tenant’s primary access domain? The current primary domain remains verified but no longer routes as primary.</p>;
    }
    if (pending.action === 'delete') {
        return <p>Permanently delete <strong>{pending.domain.domain}</strong>? Any DNS record outside AutoERP remains your responsibility.</p>;
    }
    if (pending.domain.is_primary) {
        return (
            <div className="space-y-2">
                <p>Disable the primary access domain <strong>{pending.domain.domain}</strong>?</p>
                <p>{verifiedFallback
                    ? `${verifiedFallback.domain} can be promoted as the verified fallback.`
                    : tenantStatus === 'active'
                        ? 'No verified fallback exists. The backend will suspend the active tenant to prevent an unreachable workspace.'
                        : 'No verified fallback exists. A new primary domain will be required before activation.'}</p>
            </div>
        );
    }
    return <p>Disable <strong>{pending.domain.domain}</strong>? Users will no longer be able to resolve this hostname to the tenant.</p>;
}
