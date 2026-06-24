import { useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { compareDecimalStrings, isNonNegativeDecimal } from '@/shared/utils/decimal';
import { TENANT_MODULES, type TenantModuleCode } from '@/app/access/tenantModules';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { createTenantPlan, deactivateTenantPlan, listTenantPlans, updateTenantPlan } from './tenantApi';
import type { TenantPlan, TenantPlanLimits } from './tenantTypes';

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
    const auth = useAuth();
    const canManage = hasPermission(auth, PLATFORM_PERMISSION.plansManage);
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search);
    const plans = useApi((signal) => listTenantPlans({ page, per_page: 20, search: debouncedSearch || undefined }, signal), [page, debouncedSearch]);
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), []);
    const [editing, setEditing] = useState<TenantPlan | null>(null);
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [price, setPrice] = useState('0.000000');
    const [currencyId, setCurrencyId] = useState('');
    const [interval, setInterval] = useState<'month' | 'quarter' | 'year'>('month');
    const [effectiveAt, setEffectiveAt] = useState('');
    const [enabledModules, setEnabledModules] = useState<TenantModuleCode[]>([]);
    const [limits, setLimits] = useState<LimitFormState>(emptyLimits);
    const [active, setActive] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [deactivateTarget, setDeactivateTarget] = useState<TenantPlan | null>(null);

    function startEditing(plan: TenantPlan) {
        const revision = plan.latest_revision;
        setEditing(plan);
        setName(plan.name);
        setSlug(plan.slug);
        setPrice(revision?.price ?? '0.000000');
        setCurrencyId(revision?.currency_id ? String(revision.currency_id) : '');
        setInterval(revision?.billing_interval ?? 'month');
        setEffectiveAt('');
        setEnabledModules(revision?.features.enabled_modules ?? []);
        setLimits({
            max_users: toLimitValue(revision?.limits.max_users),
            max_organization_units: toLimitValue(revision?.limits.max_organization_units),
            max_warehouses: toLimitValue(revision?.limits.max_warehouses),
            max_storage_mb: toLimitValue(revision?.limits.max_storage_mb),
        });
        setActive(plan.is_active);
        setError(null);
    }

    async function save(event: FormEvent) {
        event.preventDefault();
        const validation = validatePlanForm({ name, slug, price, currencyId, effectiveAt, limits });
        if (validation.error) {
            setError(validation.error);
            return;
        }

        setSaving(true);
        setError(null);
        const payload = {
            name: name.trim(),
            slug: slug.trim(),
            price,
            currency_id: validation.currencyId,
            billing_interval: interval,
            effective_at: validation.effectiveAt,
            features: { enabled_modules: enabledModules },
            limits: validation.limits,
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

    async function deactivate() {
        const plan = deactivateTarget;
        if (!plan) return;
        setDeactivateTarget(null);
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
        setEffectiveAt('');
        setEnabledModules([]);
        setLimits(emptyLimits());
        setActive(true);
        setError(null);
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
                description="Plan identities are mutable, while every commercial change creates an immutable revision so existing subscriptions keep their historical contract."
            />
            <div className="space-y-5">
                <ErrorAlert error={plans.error ?? currencies.error ?? error} />
                {canManage ? (
                    <Panel title={editing ? `Revise ${editing.name}` : 'Create a subscription plan'}>
                        <form className="space-y-6" onSubmit={(event) => void save(event)}>
                            {editing ? (
                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                                    Identity changes update the plan record. Pricing, modules, limits, currency, or interval changes create a new immutable revision; existing tenant subscriptions stay on their assigned revision until explicitly replaced.
                                </div>
                            ) : null}
                            <div className="grid gap-4 md:grid-cols-2">
                                <Input label="Plan name" value={name} error={fieldError(error, 'name')} onChange={(event) => setName(event.target.value)} required />
                                <Input label="Plan slug" value={slug} error={fieldError(error, 'slug')} onChange={(event) => setSlug(event.target.value.toLowerCase())} placeholder="professional" required />
                                <DecimalInput label="Price" value={price} error={fieldError(error, 'price')} onChange={(event) => setPrice(event.target.value)} required />
                                <Select
                                    label="Billing currency"
                                    value={currencyId}
                                    onChange={(event) => setCurrencyId(event.target.value)}
                                    options={(currencies.data ?? []).map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))}
                                    placeholder="Select currency for paid plans"
                                    error={fieldError(error, 'currency_id')}
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
                                <Input
                                    label="Revision effective at"
                                    type="datetime-local"
                                    value={effectiveAt}
                                    onChange={(event) => setEffectiveAt(event.target.value)}
                                    error={fieldError(error, 'effective_at')}
                                    hint="Leave empty to make the revision effective immediately."
                                />
                                <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" checked={active} onChange={(event) => setActive(event.target.checked)} />
                                    Available for new tenant assignments
                                </label>
                            </div>

                            <fieldset>
                                <legend className="text-sm font-semibold text-slate-900">Enabled modules</legend>
                                <p className="mt-1 text-sm text-slate-500">Only selected business modules are accessible under this revision.</p>
                                <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {TENANT_MODULES.map((module) => (
                                        <label key={module.code} className="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                            <input type="checkbox" checked={enabledModules.includes(module.code)} onChange={() => toggleModule(module.code)} />
                                            {module.label}
                                        </label>
                                    ))}
                                </div>
                            </fieldset>

                            <fieldset>
                                <legend className="text-sm font-semibold text-slate-900">Usage limits</legend>
                                <p className="mt-1 text-sm text-slate-500">Empty values are unrestricted by the plan. Assignment checks actual tenant usage before a downgrade.</p>
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
                                            error={fieldError(error, `limits.${limit.key}`)}
                                            hint={limit.hint}
                                        />
                                    ))}
                                </div>
                            </fieldset>

                            <div className="flex justify-end gap-2">
                                {editing ? <Button variant="secondary" onClick={resetForm}>Cancel</Button> : null}
                                <Button type="submit" loading={saving}>{editing ? 'Save and create revision if needed' : 'Create plan'}</Button>
                            </div>
                        </form>
                    </Panel>
                ) : null}

                <Panel title="Plan catalogue">
                    <Input label="Search plans" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Plan name or slug" />
                    {plans.loading && !plans.data ? <LoadingState label="Loading tenant plans..." /> : (
                        <div className="mt-4 space-y-3">
                            {(plans.data?.data ?? []).map((plan) => {
                                const revision = plan.latest_revision;
                                return (
                                    <div key={plan.id} className="flex flex-col justify-between gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <p className="font-semibold text-slate-900">{plan.name}</p>
                                                <StatusBadge status={plan.is_active ? 'active' : 'inactive'} />
                                            </div>
                                            <p className="mt-1 text-sm text-slate-500">
                                                {revision ? `${revision.price} ${revision.currency?.code ?? ''} · ${revision.billing_interval} · revision ${revision.revision_number}` : 'No plan revision'}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-500">
                                                {revision?.features.enabled_modules.length ?? 0} modules · {formatLimits(revision?.limits ?? null)}
                                            </p>
                                        </div>
                                        {canManage ? (
                                            <div className="flex gap-2">
                                                <Button variant="secondary" disabled={saving} onClick={() => startEditing(plan)}>Revise</Button>
                                                {plan.is_active ? <Button variant="danger" disabled={saving} onClick={() => setDeactivateTarget(plan)}>Deactivate</Button> : null}
                                            </div>
                                        ) : null}
                                    </div>
                                );
                            })}
                            {(plans.data?.data ?? []).length === 0 ? <p className="py-8 text-center text-sm text-slate-500">No plans match the search.</p> : null}
                        </div>
                    )}
                    <Pagination meta={plans.data?.meta} onPageChange={setPage} />
                </Panel>
            </div>

            <ConfirmDialog
                open={deactivateTarget !== null}
                title="Deactivate tenant plan"
                message={deactivateTarget ? <p>Deactivate <strong>{deactivateTarget.name}</strong>? It will stop new assignments, while historical subscriptions remain intact.</p> : null}
                confirmLabel="Deactivate plan"
                danger
                loading={saving}
                onCancel={() => setDeactivateTarget(null)}
                onConfirm={() => void deactivate()}
            />
        </>
    );
}

function validatePlanForm(values: {
    name: string;
    slug: string;
    price: string;
    currencyId: string;
    effectiveAt: string;
    limits: LimitFormState;
}): { error: ApiError | null; currencyId: number | null; effectiveAt: string | null; limits: TenantPlanLimits } {
    const fields: Record<string, string[]> = {};
    if (values.name.trim() === '') fields.name = ['Plan name is required.'];
    if (values.slug.trim() === '') fields.slug = ['Plan slug is required.'];
    if (!isNonNegativeDecimal(values.price) || values.price.trim() === '') fields.price = ['Price must be a non-negative decimal amount.'];

    const paidPlan = isNonNegativeDecimal(values.price) && values.price.trim() !== '' && compareDecimalStrings(values.price, '0') > 0;
    const parsedCurrencyId = values.currencyId === '' ? null : Number(values.currencyId);
    if (paidPlan && parsedCurrencyId === null) fields.currency_id = ['A billing currency is required for a paid plan.'];
    else if (parsedCurrencyId !== null && (!Number.isSafeInteger(parsedCurrencyId) || parsedCurrencyId < 1)) fields.currency_id = ['Select a valid billing currency.'];

    let parsedEffectiveAt: string | null = null;
    if (values.effectiveAt !== '') {
        const date = new Date(values.effectiveAt);
        if (Number.isNaN(date.getTime())) fields.effective_at = ['Select a valid effective date.'];
        else parsedEffectiveAt = date.toISOString();
    }

    const parsedLimits: Partial<TenantPlanLimits> = {};
    for (const [key, rawValue] of Object.entries(values.limits) as Array<[keyof TenantPlanLimits, string]>) {
        const value = rawValue.trim();
        if (value === '') continue;
        const parsed = Number(value);
        if (!Number.isSafeInteger(parsed) || parsed < 1) fields[`limits.${key}`] = ['Limit must be a positive whole number.'];
        else parsedLimits[key] = parsed;
    }

    return {
        error: Object.keys(fields).length > 0
            ? new ApiError('Please correct the highlighted plan fields.', 422, 'CLIENT_VALIDATION_FAILED', 'validation', fields)
            : null,
        currencyId: parsedCurrencyId,
        effectiveAt: parsedEffectiveAt,
        limits: parsedLimits as TenantPlanLimits,
    };
}

function toLimitValue(value: number | undefined): string {
    return value === undefined ? '' : String(value);
}

function formatLimits(limits: TenantPlanLimits | null): string {
    if (!limits || Object.keys(limits).length === 0) return 'no plan limits';
    return Object.entries(limits).map(([key, value]) => `${key.replaceAll('_', ' ')}: ${value}`).join(' · ');
}
