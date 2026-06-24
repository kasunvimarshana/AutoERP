import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import {
    changePlatformTenantDomain,
    createPlatformTenantDomain,
    deletePlatformTenantDomain,
    listPlatformTenantDomains,
    requestPlatformDomainVerification,
    verifyPlatformTenantDomain,
} from '../tenantApi';
import type { DomainVerificationChallenge, TenantDomain, TenantRecord } from '../tenantTypes';

interface Props {
    tenant: TenantRecord;
    canManage: boolean;
    disabled?: boolean;
    onChanged: () => void;
}

type PendingAction = { action: 'disable' | 'delete'; domain: TenantDomain } | null;

export function PlatformTenantDomainsPanel({ tenant, canManage, disabled = false, onChanged }: Props) {
    const domains = useApi((signal) => listPlatformTenantDomains(tenant.id, signal), [tenant.id]);
    const [domainName, setDomainName] = useState('');
    const [busyId, setBusyId] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [challenge, setChallenge] = useState<{ domainId: number; value: DomainVerificationChallenge } | null>(null);
    const [pendingAction, setPendingAction] = useState<PendingAction>(null);

    async function createDomain() {
        const normalized = domainName.trim().toLowerCase();
        if (normalized === '') return;
        setBusyId(0);
        setError(null);
        try {
            await createPlatformTenantDomain(tenant.id, normalized);
            setDomainName('');
            domains.reload();
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    }

    async function run(domain: TenantDomain, action: 'challenge' | 'verify' | 'primary') {
        setBusyId(domain.id);
        setError(null);
        try {
            if (action === 'challenge') {
                const result = await requestPlatformDomainVerification(tenant.id, domain);
                setChallenge({ domainId: domain.id, value: result.challenge });
            } else if (action === 'verify') {
                await verifyPlatformTenantDomain(tenant.id, domain);
            } else {
                await changePlatformTenantDomain(tenant.id, domain, 'primary');
            }
            domains.reload();
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            domains.reload();
        } finally {
            setBusyId(null);
        }
    }

    async function confirmDestructiveAction() {
        if (!pendingAction) return;
        const target = pendingAction;
        setPendingAction(null);
        setBusyId(target.domain.id);
        setError(null);
        try {
            if (target.action === 'disable') {
                await changePlatformTenantDomain(tenant.id, target.domain, 'disable');
            } else {
                await deletePlatformTenantDomain(tenant.id, target.domain);
            }
            if (challenge?.domainId === target.domain.id) setChallenge(null);
            domains.reload();
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            domains.reload();
        } finally {
            setBusyId(null);
        }
    }

    return (
        <section className="space-y-4" aria-labelledby="tenant-domains-title">
            <div>
                <h3 id="tenant-domains-title" className="font-semibold text-slate-900">2. Verify tenant domain</h3>
                <p className="mt-1 text-sm text-slate-500">Add a hostname, publish the DNS TXT challenge, verify it, then select one verified domain as primary.</p>
            </div>
            <ErrorAlert error={domains.error ?? error} />

            {canManage ? (
                <div className="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                    <Input
                        label="Tenant hostname"
                        value={domainName}
                        onChange={(event) => setDomainName(event.target.value)}
                        placeholder="erp.example.com"
                        hint="Enter a hostname only; do not include https:// or a path."
                        disabled={disabled || busyId !== null}
                    />
                    <Button disabled={disabled || busyId !== null || domainName.trim() === ''} loading={busyId === 0} onClick={() => void createDomain()}>Add domain</Button>
                </div>
            ) : null}

            {domains.loading && !domains.data ? <LoadingState label="Loading tenant domains..." /> : null}
            <div className="space-y-3">
                {(domains.data ?? []).map((domain) => (
                    <article key={domain.id} className="rounded-lg border border-slate-200 p-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-semibold text-slate-900">{domain.domain}</p>
                                    <StatusBadge status={domain.status} />
                                    {domain.is_primary ? <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">Primary</span> : null}
                                </div>
                                <p className="mt-1 text-xs text-slate-500">{domain.verified_at ? `Verified ${domain.verified_at}` : 'Not verified yet'}</p>
                            </div>
                            {canManage ? (
                                <div className="flex flex-wrap gap-2">
                                    {domain.status === 'pending' ? <Button variant="secondary" disabled={disabled || busyId !== null} onClick={() => void run(domain, 'challenge')}>DNS instructions</Button> : null}
                                    {domain.status === 'pending' ? <Button disabled={disabled || busyId !== null} loading={busyId === domain.id} onClick={() => void run(domain, 'verify')}>Verify</Button> : null}
                                    {domain.status === 'active' && !domain.is_primary ? <Button variant="secondary" disabled={disabled || busyId !== null} onClick={() => void run(domain, 'primary')}>Make primary</Button> : null}
                                    {domain.status !== 'disabled' ? <Button variant="secondary" disabled={disabled || busyId !== null} onClick={() => setPendingAction({ action: 'disable', domain })}>Disable</Button> : null}
                                    {!domain.is_primary ? <Button variant="danger" disabled={disabled || busyId !== null} onClick={() => setPendingAction({ action: 'delete', domain })}>Delete</Button> : null}
                                </div>
                            ) : null}
                        </div>
                        {challenge?.domainId === domain.id ? (
                            <div className="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                <p><strong>TXT host:</strong> <code className="break-all">{challenge.value.host}</code></p>
                                <p className="mt-2"><strong>TXT value:</strong> <code className="break-all">{challenge.value.value}</code></p>
                                <p className="mt-2 text-xs text-slate-500">Challenge expires at {challenge.value.expires_at}. Publish the record, wait for DNS propagation, then select Verify.</p>
                            </div>
                        ) : null}
                    </article>
                ))}
                {(domains.data ?? []).length === 0 && !domains.loading ? <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No tenant domains have been added.</p> : null}
            </div>

            <ConfirmDialog
                open={pendingAction !== null}
                title={pendingAction?.action === 'delete' ? 'Delete tenant domain' : 'Disable tenant domain'}
                message={pendingAction ? <p>Confirm {pendingAction.action} for <strong>{pendingAction.domain.domain}</strong>. The primary domain cannot be deleted.</p> : null}
                confirmLabel={pendingAction?.action === 'delete' ? 'Delete domain' : 'Disable domain'}
                danger
                loading={busyId !== null}
                onCancel={() => setPendingAction(null)}
                onConfirm={() => void confirmDestructiveAction()}
            />
        </section>
    );
}
