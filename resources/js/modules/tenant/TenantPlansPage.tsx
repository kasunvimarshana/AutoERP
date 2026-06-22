import { useEffect, useState, type FormEvent } from 'react';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
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
import { useApi } from '@/shared/hooks/useApi';
import { createTenantPlan, deactivateTenantPlan, listTenantPlans, updateTenantPlan } from './tenantApi';
import type { TenantPlan } from './tenantTypes';

export default function TenantPlansPage() {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const plans = useApi((signal) => listTenantPlans({ page, per_page: 20, search: search || undefined }, signal), [page, search]);
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), []);
    const [editing, setEditing] = useState<TenantPlan | null>(null);
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [price, setPrice] = useState('0.000000');
    const [currencyId, setCurrencyId] = useState('');
    const [interval, setInterval] = useState<'month' | 'quarter' | 'year'>('month');
    const [active, setActive] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        setName(editing?.name ?? ''); setSlug(editing?.slug ?? ''); setPrice(editing?.price ?? '0.000000');
        setCurrencyId(editing?.currency_id ? String(editing.currency_id) : ''); setInterval(editing?.billing_interval ?? 'month'); setActive(editing?.is_active ?? true);
    }, [editing]);

    async function save(event: FormEvent) {
        event.preventDefault(); setSaving(true); setError(null);
        const payload = { name: name.trim(), slug: slug.trim(), price, currency_id: currencyId ? Number(currencyId) : null, billing_interval: interval, is_active: active };
        try {
            if (editing) await updateTenantPlan(editing, payload); else await createTenantPlan(payload);
            setEditing(null); setName(''); setSlug(''); plans.reload();
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setSaving(false); }
    }

    async function deactivate(plan: TenantPlan) {
        setSaving(true); setError(null);
        try { await deactivateTenantPlan(plan); if (editing?.id === plan.id) setEditing(null); plans.reload(); }
        catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setSaving(false); }
    }

    return (
        <>
            <ContentHeader title="Tenant plans" description="Manage subscription pricing and billing cadence. Business feature enforcement remains owned by the modules that consume plan entitlements." />
            <div className="space-y-5">
                <ErrorAlert error={plans.error ?? currencies.error ?? error} />
                <Panel title={editing ? `Edit ${editing.name}` : 'Create a subscription plan'}>
                    <form className="grid gap-4 md:grid-cols-2" onSubmit={(event) => void save(event)}>
                        <Input label="Plan name" value={name} onChange={(event) => setName(event.target.value)} required />
                        <Input label="Plan slug" value={slug} onChange={(event) => setSlug(event.target.value.toLowerCase())} placeholder="professional" required />
                        <Input label="Price" type="number" min="0" step="0.000001" value={price} onChange={(event) => setPrice(event.target.value)} required />
                        <Select label="Billing currency" value={currencyId} onChange={(event) => setCurrencyId(event.target.value)} options={(currencies.data ?? []).map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))} placeholder="Select currency" />
                        <Select label="Billing interval" value={interval} onChange={(event) => setInterval(event.target.value as 'month' | 'quarter' | 'year')} options={[{ value: 'month', label: 'Monthly' }, { value: 'quarter', label: 'Quarterly' }, { value: 'year', label: 'Yearly' }]} />
                        <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700"><input type="checkbox" checked={active} onChange={(event) => setActive(event.target.checked)} />Available for tenant assignment</label>
                        <div className="flex justify-end gap-2 md:col-span-2">{editing && <Button variant="secondary" onClick={() => setEditing(null)}>Cancel</Button>}<Button type="submit" loading={saving}>{editing ? 'Save plan' : 'Create plan'}</Button></div>
                    </form>
                </Panel>
                <Panel title="Plan catalogue">
                    <Input label="Search plans" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Plan name or slug" />
                    {plans.loading && !plans.data ? <LoadingState label="Loading tenant plans..." /> : <div className="mt-4 space-y-3">
                        {(plans.data?.data ?? []).map((plan) => <div key={plan.id} className="flex flex-col justify-between gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center">
                            <div><div className="flex items-center gap-2"><p className="font-semibold text-slate-900">{plan.name}</p><StatusBadge status={plan.is_active ? 'active' : 'inactive'} /></div><p className="mt-1 text-sm text-slate-500">{plan.price} {plan.currency?.code ?? ''} · {plan.billing_interval}</p></div>
                            <div className="flex gap-2"><Button variant="secondary" onClick={() => setEditing(plan)}>Edit</Button>{plan.is_active && <Button variant="danger" loading={saving} onClick={() => void deactivate(plan)}>Deactivate</Button>}</div>
                        </div>)}
                    </div>}
                    <Pagination meta={plans.data?.meta} onPageChange={setPage} />
                </Panel>
            </div>
        </>
    );
}
