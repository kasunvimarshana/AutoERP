import { useState } from 'react';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { PlatformTenantForm } from './components/PlatformTenantForm';
import {
    changeTenantStatus,
    createPlatformTenant,
    listPlatformTenants,
    listTenantPlans,
    updatePlatformTenant,
} from './tenantApi';
import type { TenantRecord } from './tenantTypes';

export default function PlatformTenantsPage() {
    const auth = useAuth();
    const canManage = auth.isPlatformOperator;
    const canLifecycle = auth.isPlatformOperator;
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [editing, setEditing] = useState<TenantRecord | null>(null);
    const [selected, setSelected] = useState<TenantRecord | null>(null);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [busyAction, setBusyAction] = useState<string | null>(null);
    const [error, setError] = useState<ApiError | null>(null);

    const tenants = useApi(
        (signal) => listPlatformTenants({ page, per_page: 20, search: search || undefined, status: status || undefined }, signal),
        [page, search, status],
    );
    const plans = useApi((signal) => listTenantPlans({ page: 1, per_page: 100, is_active: true }, signal), []);
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), []);

    async function save(payload: FormData) {
        setSaving(true); setError(null);
        try {
            const saved = editing ? await updatePlatformTenant(editing.id, payload) : await createPlatformTenant(payload);
            setEditing(null); setSelected(saved); tenants.reload();
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setSaving(false); }
    }

    async function transition(action: 'activate' | 'suspend' | 'deactivate' | 'archive') {
        if (!selected || !reason.trim()) return;
        setBusyAction(action); setError(null);
        try {
            const updated = await changeTenantStatus(selected.id, action, selected.row_version, reason.trim());
            setSelected(updated); setReason(''); tenants.reload();
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusyAction(null); }
    }

    return (
        <>
            <ContentHeader title="SaaS tenants" description="Platform-only tenant provisioning and lifecycle controls. Tenant resource IDs never select the active request context." />
            <div className="space-y-5">
                <ErrorAlert error={tenants.error ?? plans.error ?? currencies.error ?? error} />
                {canManage && <Panel title={editing ? `Edit ${editing.name}` : 'Create a tenant'}><PlatformTenantForm tenant={editing} plans={plans.data?.data ?? []} currencies={currencies.data ?? []} saving={saving} onCancel={() => setEditing(null)} onSubmit={save} /></Panel>}
                <Panel title="Tenant directory">
                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
                        <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Name, code, or slug" />
                        <Select label="Status" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} options={['draft','active','inactive','suspended','archived'].map((value) => ({ value, label: value[0].toUpperCase() + value.slice(1) }))} placeholder="All statuses" />
                    </div>
                    {tenants.loading && !tenants.data ? <LoadingState label="Loading tenants..." /> : <div className="space-y-3">
                        {(tenants.data?.data ?? []).map((tenant) => <button key={tenant.id} type="button" onClick={() => setSelected(tenant)} className="flex w-full flex-col justify-between gap-3 rounded-lg border border-slate-200 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50/30 sm:flex-row sm:items-center">
                            <div><p className="font-semibold text-slate-900">{tenant.name}</p><p className="mt-1 text-sm text-slate-500">{tenant.code} · {tenant.plan?.name ?? 'No subscription plan'} · {tenant.base_currency?.code ?? 'Base currency required'}</p></div>
                            <StatusBadge status={tenant.status} />
                        </button>)}
                        {(tenants.data?.data ?? []).length === 0 && <p className="py-8 text-center text-sm text-slate-500">No tenants match the current filters.</p>}
                    </div>}
                    <Pagination meta={tenants.data?.meta} onPageChange={setPage} />
                </Panel>
                {selected && <Panel title={selected.name}>
                    <div className="grid gap-4 lg:grid-cols-[1fr_22rem]">
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <Detail label="Code" value={selected.code} />
                            <Detail label="Status" value={selected.status} />
                            <Detail label="Plan" value={selected.plan?.name ?? 'Not assigned'} />
                            <Detail label="Base currency" value={selected.base_currency ? `${selected.base_currency.code} — ${selected.base_currency.name}` : 'Required before activation'} />
                            <Detail label="Trial ends" value={formatDate(selected.trial_ends_at)} />
                            <Detail label="Subscription ends" value={formatDate(selected.subscription_ends_at)} />
                        </dl>
                        <div className="space-y-3">
                            {canManage && <Button variant="secondary" className="w-full" onClick={() => setEditing(selected)}>Edit tenant</Button>}
                            {canLifecycle && selected.status !== 'archived' && <>
                                <Textarea label="Lifecycle reason" value={reason} onChange={(event) => setReason(event.target.value)} placeholder="Explain why this lifecycle change is required." />
                                <div className="grid grid-cols-2 gap-2">
                                    {['draft','inactive','suspended'].includes(selected.status) && <Button loading={busyAction === 'activate'} disabled={!reason.trim()} onClick={() => void transition('activate')}>Activate</Button>}
                                    {selected.status === 'active' && <Button variant="secondary" loading={busyAction === 'suspend'} disabled={!reason.trim()} onClick={() => void transition('suspend')}>Suspend</Button>}
                                    {['active','suspended'].includes(selected.status) && <Button variant="secondary" loading={busyAction === 'deactivate'} disabled={!reason.trim()} onClick={() => void transition('deactivate')}>Deactivate</Button>}
                                    {['draft','inactive','suspended'].includes(selected.status) && <Button variant="danger" loading={busyAction === 'archive'} disabled={!reason.trim()} onClick={() => void transition('archive')}>Archive</Button>}
                                </div>
                            </>}
                        </div>
                    </div>
                </Panel>}
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return <div className="rounded-lg bg-slate-50 p-3"><dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt><dd className="mt-1 font-medium text-slate-900">{value}</dd></div>;
}
function formatDate(value: string | null): string { return value ? new Date(value).toLocaleString() : 'Not set'; }
