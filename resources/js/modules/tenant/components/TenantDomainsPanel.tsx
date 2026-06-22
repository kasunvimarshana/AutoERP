import { useState, type FormEvent } from 'react';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import {
    changeTenantDomain,
    createTenantDomain,
    deleteTenantDomain,
    listTenantDomains,
    requestDomainVerification,
    verifyTenantDomain,
} from '../tenantApi';
import type { DomainVerificationChallenge, TenantDomain } from '../tenantTypes';

export function TenantDomainsPanel({ canManage }: { canManage: boolean }) {
    const domains = useApi((signal) => listTenantDomains(signal), []);
    const [newDomain, setNewDomain] = useState('');
    const [busyId, setBusyId] = useState<number | 'new' | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [challenge, setChallenge] = useState<{ domain: string; value: DomainVerificationChallenge } | null>(null);
    const [removeTarget, setRemoveTarget] = useState<TenantDomain | null>(null);

    async function create(event: FormEvent) {
        event.preventDefault();
        if (!canManage || !newDomain.trim()) return;
        setBusyId('new'); setError(null);
        try {
            const created = await createTenantDomain(newDomain);
            domains.setData([...(domains.data ?? []), created]);
            setNewDomain('');
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusyId(null); }
    }

    function replace(updated: TenantDomain) {
        domains.setData((domains.data ?? []).map((domain) => domain.id === updated.id ? updated : domain));
    }

    async function act(domain: TenantDomain, action: 'challenge' | 'verify' | 'primary' | 'disable') {
        setBusyId(domain.id); setError(null);
        try {
            if (action === 'challenge') {
                const result = await requestDomainVerification(domain);
                replace(result.data);
                setChallenge({ domain: result.data.domain, value: result.challenge });
            } else if (action === 'verify') {
                replace(await verifyTenantDomain(domain));
            } else {
                const updated = await changeTenantDomain(domain, action);
                if (action === 'primary') {
                    domains.setData((domains.data ?? []).map((item) => item.id === updated.id ? updated : { ...item, is_primary: false }));
                } else replace(updated);
            }
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusyId(null); }
    }

    async function remove() {
        if (!removeTarget) return;
        setBusyId(removeTarget.id); setError(null);
        try {
            await deleteTenantDomain(removeTarget);
            domains.setData((domains.data ?? []).filter((domain) => domain.id !== removeTarget.id));
            setRemoveTarget(null);
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusyId(null); }
    }

    if (domains.loading && !domains.data) return <LoadingState label="Loading tenant domains..." />;

    return (
        <div className="space-y-5">
            <ErrorAlert error={domains.error ?? error} />
            {canManage && (
                <Panel title="Add a tenant domain">
                    <form className="flex flex-col gap-3 sm:flex-row sm:items-end" onSubmit={create}>
                        <Input className="sm:min-w-96" label="Domain name" value={newDomain} onChange={(event) => setNewDomain(event.target.value)} placeholder="erp.example.com" hint="Enter a hostname only—no protocol or path." required />
                        <Button type="submit" loading={busyId === 'new'}>Add domain</Button>
                    </form>
                </Panel>
            )}
            {challenge && (
                <Panel title={`Verify ${challenge.domain}`}>
                    <p className="text-sm text-slate-600">Create this DNS TXT record, then use Verify after DNS has propagated.</p>
                    <dl className="mt-4 grid gap-3 text-sm">
                        <div><dt className="font-medium text-slate-600">Host</dt><dd className="mt-1 break-all rounded bg-slate-100 p-3 font-mono">{challenge.value.host}</dd></div>
                        <div><dt className="font-medium text-slate-600">Value</dt><dd className="mt-1 break-all rounded bg-slate-100 p-3 font-mono">{challenge.value.value}</dd></div>
                    </dl>
                    <p className="mt-3 text-xs text-slate-500">Challenge expires at {new Date(challenge.value.expires_at).toLocaleString()} and is shown only when generated.</p>
                </Panel>
            )}
            <div className="space-y-3">
                {(domains.data ?? []).map((domain) => (
                    <Panel key={domain.id}>
                        <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                            <div>
                                <div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{domain.domain}</p><StatusBadge status={domain.status} />{domain.is_primary && <span className="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Primary</span>}</div>
                                <p className="mt-1 text-xs text-slate-500">{domain.verified_at ? `Verified ${new Date(domain.verified_at).toLocaleString()}` : 'Ownership verification required before activation or primary use.'}</p>
                            </div>
                            {canManage && <div className="flex flex-wrap gap-2">
                                <Button variant="secondary" loading={busyId === domain.id} onClick={() => void act(domain, 'challenge')}>DNS challenge</Button>
                                {domain.status === 'pending' && <Button variant="secondary" loading={busyId === domain.id} onClick={() => void act(domain, 'verify')}>Verify</Button>}
                                {domain.status === 'active' && !domain.is_primary && <Button variant="secondary" loading={busyId === domain.id} onClick={() => void act(domain, 'primary')}>Make primary</Button>}
                                {domain.status === 'active' && !domain.is_primary && <Button variant="secondary" loading={busyId === domain.id} onClick={() => void act(domain, 'disable')}>Disable</Button>}
                                {domain.status !== 'active' && !domain.is_primary && <Button variant="danger" onClick={() => setRemoveTarget(domain)}>Remove</Button>}
                            </div>}
                        </div>
                    </Panel>
                ))}
                {(domains.data ?? []).length === 0 && <Panel><p className="text-sm text-slate-500">No domains have been added.</p></Panel>}
            </div>
            <ConfirmDialog open={removeTarget !== null} title="Remove domain?" message="The disabled or pending domain will be removed from this tenant." confirmLabel="Remove domain" loading={removeTarget !== null && busyId === removeTarget.id} onCancel={() => setRemoveTarget(null)} onConfirm={() => void remove()} />
        </div>
    );
}
