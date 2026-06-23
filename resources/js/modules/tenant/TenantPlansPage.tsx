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
import type { TenantModuleCode, TenantPlan, TenantPlanLimits } from './tenantTypes';

const MODULE_OPTIONS: Array<{ value: TenantModuleCode; label: string }> = [
    { value: 'customer', label: 'Customers' },
    { value: 'supplier', label: 'Suppliers' },
    { value: 'item', label: 'Items' },
    { value: 'warehouse', label: 'Warehouses' },
    { value: 'inventory', label: 'Inventory' },
    { value: 'purchase', label: 'Purchasing' },
    { value: 'sales', label: 'Sales' },
    { value: 'vehicle', label: 'Vehicles' },
    { value: 'vehicle-service', label: 'Vehicle service' },
    { value: 'vehicle-rental', label: 'Vehicle rental' },
    { value: 'invoice', label: 'Invoicing' },
    { value: 'payment', label: 'Payments' },
    { value: 'finance', label: 'Finance' },
    { value: 'reporting', label: 'Reporting' },
];

const LIMIT_OPTIONS: Array<{ key: keyof TenantPlanLimits; label: string; hint: string }> = [
    { key: 'max_users', label: 'Maximum users', hint: 'Leave empty for no plan limit.' },
    { key: 'max_organization_units', label: 'Maximum organization units', hint: 'Includes all branches and departments.' },
    { key: 'max_warehouses', label: 'Maximum warehouses', hint: 'Applied across the tenant.' },
    { key: 'max_storage_mb', label: 'Document storage (MB)', hint: 'Applied to tenant-managed private documents.' },
];

type LimitFormState = Record<keyof TenantPlanLimits, string>;

const emptyLimits = (): LimitFormState => ({
    max_users: '',
    max_organization_units: '',
    max_warehouses: '',
    max_storage_mb: '',
});

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
    const [enabledModules, setEnabledModules] = useState<TenantModuleCode[]>([]);
    const [limits, setLimits] = useState<LimitFormState>(emptyLimits);
    const [active, setActive] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        setName(editing?.name ?? '');
        setSlug(editing?.slug ?? '');
        setPrice(editing?.price ?? '0.000000');
        setCurrencyId(editing?.currency_id ? String(editing.currency_id) : '');
        setInterval(editing?.billing_interval ?? 'month');
        setEnabledModules(editing?.features?.enabled_modules ?? []);
        setLimits({
            max_users: toLimitValue(editing?.limits?.max_users),
            max_organization_units: toLimitValue(editing?.limits?.max_organization_units),
            max_warehouses: toLimitValue(editing?.limits?.max_warehouses),
            max_storage_mb: toLimitValue(editing?.limits?.max_storage_mb),
        });
        setActive(editing?.is_active ?? true);
    }, [editing]);

    async function save(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        const payload = {
            name: name.trim(),
            slug: slug.trim(),
            price,
            currency_id: currencyId ? Number(currencyId) : null,
            billing_interval: interval,
            features: { enabled_modules: enabledModules },
            limits: normalizeLimits(limits),
            is_active: active,
        };

        try {
            if (editing) await updateTenantPlan(editing, payload);
            else await createTenantPlan(payload);
            resetForm();
            plans.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function deactivate(plan: TenantPlan) {
        setSaving(true);
        setError(null);
        try {
            await deactivateTenantPlan(plan);
            if (editing?.id === plan.id) resetForm();
            plans.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    function resetForm() {
        setEditing(null);
        setName('');
        setSlug('');
        setPrice('0.000000');
        setCurrencyId('');
        setInterval('month');
        setEnabledModules([]);
        setLimits(emptyLimits());
        setActive(true);
    }

    function toggleModule(module: TenantModuleCode) {
        setEnabledModules((current) => current.includes(module)
            ? current.filter((item) => item !== module)
            : [...current, module]);
    }

    return (
        <>
            <ContentHeader
                title="Tenant plans"
                description="Define subscription pricing, enabled modules, and enforceable tenant limits. Empty limits mean unrestricted by the plan."
            />
            <div className="space-y-5">
                <ErrorAlert error={plans.error ?? currencies.error ?? error} />
                <Panel title={editing ? `Edit ${editing.name}` : 'Create a subscription plan'}>
                    <form className="space-y-6" onSubmit={(event) => void save(event)}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input label="Plan name" value={name} onChange={(event) => setName(event.target.value)} required />
                            <Input label="Plan slug" value={slug} onChange={(event) => setSlug(event.target.value.toLowerCase())} placeholder="professional" required />
                            <Input label="Price" type="number" min="0" step="0.000001" value={price} onChange={(event) => setPrice(event.target.value)} required />
                            <Select
                                label="Billing currency"
                                value={currencyId}
                                onChange={(event) => setCurrencyId(event.target.value)}
                                options={(currencies.data ?? []).map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))}
                                placeholder="Select currency for paid plans"
                            />
                            <Select
                                label="Billing interval"
                                value={interval}
                                onChange={(event) => setInterval(event.target.value as 'month' | 'quarter' | 'year')}
                                options={[
                                    { value: 'month', label: 'Monthly' },
                                    { value: 'quarter', label: 'Quarterly' },
                                    { value: 'year', label: 'Yearly' },
                                ]}
                            />
                            <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input type="checkbox" checked={active} onChange={(event) => setActive(event.target.checked)} />
                                Available for tenant assignment
                            </label>
                        </div>

                        <fieldset>
                            <legend className="text-sm font-semibold text-slate-900">Enabled modules</legend>
                            <p className="mt-1 text-sm text-slate-500">Only selected business modules are accessible to tenants assigned to this plan.</p>
                            <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {MODULE_OPTIONS.map((module) => (
                                    <label key={module.value} className="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            checked={enabledModules.includes(module.value)}
                                            onChange={() => toggleModule(module.value)}
                                        />
                                        {module.label}
                                    </label>
                                ))}
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend className="text-sm font-semibold text-slate-900">Usage limits</legend>
                            <p className="mt-1 text-sm text-slate-500">Limits are enforced by the backend during resource creation, including concurrent requests.</p>
                            <div className="mt-3 grid gap-4 md:grid-cols-2">
                                {LIMIT_OPTIONS.map((limit) => (
                                    <Input
                                        key={limit.key}
                                        label={limit.label}
                                        type="number"
                                        min="1"
                                        step="1"
                                        value={limits[limit.key]}
                                        onChange={(event) => setLimits((current) => ({ ...current, [limit.key]: event.target.value }))}
                                        hint={limit.hint}
                                    />
                                ))}
                            </div>
                        </fieldset>

                        <div className="flex justify-end gap-2">
                            {editing && <Button variant="secondary" onClick={resetForm}>Cancel</Button>}
                            <Button type="submit" loading={saving}>{editing ? 'Save plan' : 'Create plan'}</Button>
                        </div>
                    </form>
                </Panel>

                <Panel title="Plan catalogue">
                    <Input label="Search plans" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Plan name or slug" />
                    {plans.loading && !plans.data ? <LoadingState label="Loading tenant plans..." /> : (
                        <div className="mt-4 space-y-3">
                            {(plans.data?.data ?? []).map((plan) => (
                                <div key={plan.id} className="flex flex-col justify-between gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <p className="font-semibold text-slate-900">{plan.name}</p>
                                            <StatusBadge status={plan.is_active ? 'active' : 'inactive'} />
                                        </div>
                                        <p className="mt-1 text-sm text-slate-500">{plan.price} {plan.currency?.code ?? ''} · {plan.billing_interval}</p>
                                        <p className="mt-1 text-xs text-slate-500">
                                            {plan.features?.enabled_modules.length ?? 0} modules · {formatLimits(plan.limits)}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button variant="secondary" onClick={() => setEditing(plan)}>Edit</Button>
                                        {plan.is_active && <Button variant="danger" loading={saving} onClick={() => void deactivate(plan)}>Deactivate</Button>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                    <Pagination meta={plans.data?.meta} onPageChange={setPage} />
                </Panel>
            </div>
        </>
    );
}

function normalizeLimits(values: LimitFormState): TenantPlanLimits {
    return Object.fromEntries(
        Object.entries(values)
            .filter(([, value]) => value.trim() !== '')
            .map(([key, value]) => [key, Number(value)]),
    ) as TenantPlanLimits;
}

function toLimitValue(value: number | undefined): string {
    return value === undefined ? '' : String(value);
}

function formatLimits(limits: TenantPlanLimits | null): string {
    if (!limits || Object.keys(limits).length === 0) return 'no plan limits';
    return Object.entries(limits)
        .map(([key, value]) => `${key.replaceAll('_', ' ')}: ${value}`)
        .join(' · ');
}
