import { useMemo, useState, type FormEvent } from 'react';
import { TENANT_MODULE_CODE, TENANT_MODULES, type TenantModuleCode } from '@/app/access/tenantModules';
import type { ReferenceRecord } from '@/modules/reference-data/referenceDataTypes';
import { ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { compareDecimalStrings, isNonNegativeDecimal } from '@/shared/utils/decimal';
import { formatLimitLabel, formatPlanMoney, humanize } from '../tenantPresentation';
import type { NamedReference, TenantPlan, TenantPlanLimits } from '../tenantTypes';

interface Props {
    plan: TenantPlan | null;
    currencies: ReferenceRecord[];
    saving: boolean;
    error: ApiError | null;
    onCancel: () => void;
    onSubmit: (payload: Record<string, unknown>) => Promise<void>;
}

type LimitFormState = Record<keyof TenantPlanLimits, string>;

const LIMIT_OPTIONS: Array<{ key: keyof TenantPlanLimits; label: string; hint: string }> = [
    { key: 'max_users', label: 'Maximum users', hint: 'Leave empty for no plan limit.' },
    { key: 'max_organization_units', label: 'Maximum organization units', hint: 'Includes branches and departments.' },
    { key: 'max_warehouses', label: 'Maximum warehouses', hint: 'Applied across the tenant.' },
    { key: 'max_storage_mb', label: 'Document storage (MB)', hint: 'Applied to tenant-managed private documents.' },
];

const MODULE_GROUPS: Array<{ label: string; modules: TenantModuleCode[] }> = [
    { label: 'Master data', modules: [TENANT_MODULE_CODE.CUSTOMER, TENANT_MODULE_CODE.SUPPLIER, TENANT_MODULE_CODE.ITEM, TENANT_MODULE_CODE.WAREHOUSE, TENANT_MODULE_CODE.VEHICLE] },
    { label: 'People', modules: [TENANT_MODULE_CODE.HR] },
    { label: 'Operations', modules: [TENANT_MODULE_CODE.INVENTORY, TENANT_MODULE_CODE.PURCHASE, TENANT_MODULE_CODE.VEHICLE_SERVICE] },
    { label: 'Billing and finance', modules: [TENANT_MODULE_CODE.INVOICE, TENANT_MODULE_CODE.PAYMENT, TENANT_MODULE_CODE.FINANCE] },
    { label: 'Insights', modules: [TENANT_MODULE_CODE.REPORTING] },
];

export function TenantPlanEditor({ plan, currencies, saving, error, onCancel, onSubmit }: Props) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const revision = plan?.latest_revision ?? null;
    const initial = useMemo(() => ({
        name: plan?.name ?? '',
        slug: plan?.slug ?? '',
        price: revision?.price ?? '0.000000',
        currencyId: revision?.currency_id ? String(revision.currency_id) : '',
        interval: revision?.billing_interval ?? 'month',
        enabledModules: revision?.features.enabled_modules ?? [],
        limits: {
            max_users: toLimitValue(revision?.limits.max_users),
            max_organization_units: toLimitValue(revision?.limits.max_organization_units),
            max_warehouses: toLimitValue(revision?.limits.max_warehouses),
            max_storage_mb: toLimitValue(revision?.limits.max_storage_mb),
        } satisfies LimitFormState,
    }), [plan, revision]);
    const [name, setName] = useState(initial.name);
    const [slug, setSlug] = useState(initial.slug);
    const [price, setPrice] = useState(initial.price);
    const [currencyId, setCurrencyId] = useState(initial.currencyId);
    const [interval, setInterval] = useState<'month' | 'quarter' | 'year'>(initial.interval);
    const [effectiveAt, setEffectiveAt] = useState('');
    const [revisionNote, setRevisionNote] = useState('');
    const [enabledModules, setEnabledModules] = useState<TenantModuleCode[]>(initial.enabledModules);
    const [limits, setLimits] = useState<LimitFormState>(initial.limits);
    const [clientError, setClientError] = useState<ApiError | null>(null);
    const dirty = name !== initial.name
        || slug !== initial.slug
        || price !== initial.price
        || currencyId !== initial.currencyId
        || interval !== initial.interval
        || effectiveAt !== ''
        || revisionNote !== ''
        || JSON.stringify([...enabledModules].sort()) !== JSON.stringify([...initial.enabledModules].sort())
        || JSON.stringify(limits) !== JSON.stringify(initial.limits);
    const confirmDiscard = useUnsavedChanges(dirty && !saving, 'Discard the unsaved tenant plan changes?');
    const displayError = clientError ?? error;
    const currencyOptions = mergeCurrentCurrency(currencies, revision?.currency ?? null);

    function toggleModule(module: TenantModuleCode) {
        setEnabledModules((current) => current.includes(module)
            ? current.filter((item) => item !== module)
            : [...current, module]);
    }

    async function submit(event: FormEvent) {
        event.preventDefault();
        const validation = validatePlanForm({ name, slug, price, currencyId, effectiveAt, limits });
        if (validation.error) {
            setClientError(validation.error);
            return;
        }
        setClientError(null);

        const draft: DraftValues = {
            name: name.trim(),
            slug: slug.trim(),
            price,
            currencyId: validation.currencyId,
            currencyLabel: currencyLabel(currencyOptions, validation.currencyId),
            interval,
            enabledModules,
            limits: validation.limits,
            effectiveAt: validation.effectiveAt,
            revisionNote: revisionNote.trim(),
        };
        const payload = plan ? buildPlanUpdatePayload(plan, draft) : buildPlanCreatePayload(draft);
        const revisionWillBeCreated = !plan || createsRevision(payload);
        if (revisionWillBeCreated && revisionNote.trim().length < 5) {
            setClientError(new ApiError(
                'Explain why this immutable plan revision is being created.',
                422,
                'PLAN_REVISION_NOTE_REQUIRED',
                'validation',
                { change_note: ['Enter at least 5 characters describing the business reason.'] },
            ));
            return;
        }
        if (plan && Object.keys(payload).length === 0) {
            setClientError(new ApiError('No meaningful plan changes were detected.', 422, 'NO_PLAN_CHANGES', 'validation'));
            return;
        }
        if (plan && createsRevision(payload) && validation.currencyId !== null) {
            const selectedCurrency = currencyOptions.find((currency) => currency.id === validation.currencyId);
            if (selectedCurrency?.is_active === false) {
                setClientError(new ApiError(
                    'A new plan revision cannot use an inactive billing currency. Select an active currency or save identity-only changes.',
                    422,
                    'INACTIVE_PLAN_CURRENCY',
                    'validation',
                    { currency_id: ['Select an active billing currency for the new revision.'] },
                ));
                return;
            }
        }

        const changes = describeChanges(plan, draft);
        if (!await confirm({
            title: plan ? `Review ${plan.name} changes` : 'Create tenant plan',
            message: <PlanChangeReview plan={plan} changes={changes} />,
            confirmLabel: plan ? 'Save plan changes' : 'Create plan',
            danger: false,
        })) return;
        await onSubmit(payload);
    }

    return (
        <>
            <form className="space-y-6" onSubmit={(event) => void submit(event)}>
                {plan ? (
                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        <p className="font-semibold">Immutable revision rules</p>
                        <p className="mt-1">Name and slug update the plan identity. Price, currency, interval, modules, limits, or an explicit effective date create a new revision. Existing subscriptions remain on their assigned revision.</p>
                    </div>
                ) : null}
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Plan name" value={name} error={fieldError(displayError, 'name')} onChange={(event) => setName(event.target.value)} disabled={saving} required />
                    <Input label="Plan slug" value={slug} error={fieldError(displayError, 'slug')} onChange={(event) => setSlug(event.target.value.toLowerCase())} placeholder="professional" disabled={saving} required />
                    <DecimalInput label="Price" value={price} error={fieldError(displayError, 'price')} onChange={(event) => setPrice(event.target.value)} disabled={saving} required />
                    <Select
                        label="Billing currency"
                        value={currencyId}
                        onChange={(event) => setCurrencyId(event.target.value)}
                        options={currencyOptions.map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}${currency.is_active === false ? ' (inactive; current only)' : ''}` }))}
                        placeholder="Select currency for paid plans"
                        error={fieldError(displayError, 'currency_id')}
                        disabled={saving}
                    />
                    <Select
                        label="Billing interval"
                        value={interval}
                        onChange={(event) => setInterval(event.target.value as 'month' | 'quarter' | 'year')}
                        options={[{ value: 'month', label: 'Monthly' }, { value: 'quarter', label: 'Quarterly' }, { value: 'year', label: 'Yearly' }]}
                        disabled={saving}
                    />
                    <Input
                        label="New revision effective at"
                        type="datetime-local"
                        value={effectiveAt}
                        onChange={(event) => setEffectiveAt(event.target.value)}
                        error={fieldError(displayError, 'effective_at')}
                        hint="Optional. Leave empty to use the backend’s immediate effective time. Blank values are omitted, not sent as null."
                        disabled={saving}
                    />
                </div>

                <Textarea
                    label="Revision reason"
                    value={revisionNote}
                    onChange={(event) => setRevisionNote(event.target.value)}
                    error={fieldError(displayError, 'change_note')}
                    hint={plan
                        ? 'Required only when commercial terms, modules, limits, or the effective date create a new immutable revision.'
                        : 'Required for the first immutable revision. Explain the business purpose of this plan.'}
                    placeholder={plan ? 'Example: Increase storage allowance for the 2026 contract cycle.' : 'Example: Initial commercial terms for the Professional plan.'}
                    disabled={saving}
                />

                <fieldset>
                    <legend className="text-sm font-semibold text-slate-900">Enabled commercial modules</legend>
                    <p className="mt-1 text-sm text-slate-500">Foundation capabilities are always available. These controls manage commercial feature access only.</p>
                    <div className="mt-4 grid gap-4 lg:grid-cols-2">
                        {MODULE_GROUPS.map((group) => (
                            <div key={group.label} className="rounded-lg border border-slate-200 p-4">
                                <p className="font-medium text-slate-900">{group.label}</p>
                                <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                    {group.modules.map((code) => {
                                        const module = TENANT_MODULES.find((item) => item.code === code);
                                        if (!module) return null;
                                        return (
                                            <label key={code} className="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                                <input type="checkbox" checked={enabledModules.includes(code)} disabled={saving} onChange={() => toggleModule(code)} />
                                                {module.label}
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>
                    {fieldError(displayError, 'features.enabled_modules') ? <p className="mt-2 text-xs text-rose-600">{fieldError(displayError, 'features.enabled_modules')}</p> : null}
                </fieldset>

                <fieldset>
                    <legend className="text-sm font-semibold text-slate-900">Usage limits</legend>
                    <p className="mt-1 text-sm text-slate-500">Empty values mean unrestricted by this plan. Tenant assignment performs authoritative usage checks before a downgrade.</p>
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
                                error={fieldError(displayError, `limits.${limit.key}`)}
                                hint={limit.hint}
                                disabled={saving}
                            />
                        ))}
                    </div>
                </fieldset>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={saving} onClick={() => { if (confirmDiscard()) onCancel(); }}>Cancel</Button>
                    <Button type="submit" loading={saving} disabled={!dirty}>{plan ? 'Review plan changes' : 'Review new plan'}</Button>
                </div>
            </form>
            {confirmDialog}
        </>
    );
}

interface DraftValues {
    name: string;
    slug: string;
    price: string;
    currencyId: number | null;
    currencyLabel: string;
    interval: 'month' | 'quarter' | 'year';
    enabledModules: TenantModuleCode[];
    limits: TenantPlanLimits;
    effectiveAt: string | null;
    revisionNote: string;
}

interface ChangeSummary {
    identity: string[];
    modulesAdded: string[];
    modulesRemoved: string[];
    limits: string[];
    commercial: string[];
    createsRevision: boolean;
    revisionNote: string | null;
}

function buildPlanCreatePayload(draft: DraftValues): Record<string, unknown> {
    const payload: Record<string, unknown> = {
        name: draft.name,
        slug: draft.slug,
        price: draft.price,
        currency_id: draft.currencyId,
        billing_interval: draft.interval,
        features: { enabled_modules: draft.enabledModules },
        limits: draft.limits,
        change_note: draft.revisionNote,
    };
    if (draft.effectiveAt !== null) payload.effective_at = draft.effectiveAt;
    return payload;
}

function buildPlanUpdatePayload(plan: TenantPlan, draft: DraftValues): Record<string, unknown> {
    const payload: Record<string, unknown> = {};
    const current = plan.latest_revision;
    if (plan.name !== draft.name) payload.name = draft.name;
    if (plan.slug !== draft.slug) payload.slug = draft.slug;

    if (!current || compareDecimalStrings(current.price, draft.price) !== 0) payload.price = draft.price;
    if (!current || current.currency_id !== draft.currencyId) payload.currency_id = draft.currencyId;
    if (!current || current.billing_interval !== draft.interval) payload.billing_interval = draft.interval;
    if (!current || !sameStringSet(current.features.enabled_modules, draft.enabledModules)) {
        payload.features = { enabled_modules: draft.enabledModules };
    }
    if (!current || !sameLimits(current.limits, draft.limits)) payload.limits = draft.limits;
    if (draft.effectiveAt !== null) payload.effective_at = draft.effectiveAt;
    if (createsRevision(payload)) payload.change_note = draft.revisionNote;
    return payload;
}

function createsRevision(payload: Record<string, unknown>): boolean {
    return ['price', 'currency_id', 'billing_interval', 'features', 'limits', 'effective_at']
        .some((field) => Object.hasOwn(payload, field));
}

function sameStringSet(left: string[], right: string[]): boolean {
    return JSON.stringify([...left].sort()) === JSON.stringify([...right].sort());
}

function sameLimits(left: TenantPlanLimits, right: TenantPlanLimits): boolean {
    const keys = [...new Set([...Object.keys(left), ...Object.keys(right)])].sort();
    return keys.every((key) => left[key as keyof TenantPlanLimits] === right[key as keyof TenantPlanLimits]);
}

function describeChanges(plan: TenantPlan | null, draft: DraftValues): ChangeSummary {
    if (!plan?.latest_revision) {
        return {
            identity: [`Create ${draft.name} (${draft.slug})`],
            modulesAdded: draft.enabledModules.map(humanize),
            modulesRemoved: [],
            limits: Object.entries(draft.limits).map(([key, value]) => `${formatLimitLabel(key)}: ${value}`),
            commercial: [`${draft.price} ${draft.currencyLabel} / ${humanize(draft.interval)}`.trim(), draft.effectiveAt ? `Effective ${draft.effectiveAt}` : 'Effective immediately'],
            createsRevision: true,
            revisionNote: draft.revisionNote,
        };
    }
    const current = plan.latest_revision;
    const currentModules = new Set(current.features.enabled_modules);
    const nextModules = new Set(draft.enabledModules);
    const modulesAdded = [...nextModules].filter((module) => !currentModules.has(module)).map(humanize);
    const modulesRemoved = [...currentModules].filter((module) => !nextModules.has(module)).map(humanize);
    const limits = Object.keys({ ...current.limits, ...draft.limits }).flatMap((key) => {
        const before = current.limits[key as keyof TenantPlanLimits];
        const after = draft.limits[key as keyof TenantPlanLimits];
        return before === after ? [] : [`${formatLimitLabel(key)}: ${before ?? 'Unlimited'} → ${after ?? 'Unlimited'}`];
    });
    const commercial: string[] = [];
    if (compareDecimalStrings(current.price, draft.price) !== 0 || current.currency_id !== draft.currencyId) {
        commercial.push(`${formatPlanMoney(current)} → ${draft.price} ${draft.currencyLabel} / ${humanize(draft.interval)}`.trim());
    }
    else if (current.billing_interval !== draft.interval) commercial.push(`Billing interval: ${humanize(current.billing_interval)} → ${humanize(draft.interval)}`);
    if (draft.effectiveAt) commercial.push(`Explicit effective date: ${draft.effectiveAt}`);
    return {
        identity: [
            ...(plan.name !== draft.name ? [`Name: ${plan.name} → ${draft.name}`] : []),
            ...(plan.slug !== draft.slug ? [`Slug: ${plan.slug} → ${draft.slug}`] : []),
        ],
        modulesAdded,
        modulesRemoved,
        limits,
        commercial,
        createsRevision: modulesAdded.length > 0 || modulesRemoved.length > 0 || limits.length > 0 || commercial.length > 0,
        revisionNote: draft.revisionNote || null,
    };
}

function PlanChangeReview({ plan, changes }: { plan: TenantPlan | null; changes: ChangeSummary }) {
    return (
        <div className="space-y-3">
            <p>{plan ? (changes.createsRevision ? 'These changes create a new immutable revision.' : 'Only plan identity changes were detected; no revision is created.') : 'The first immutable revision will be created with this plan.'}</p>
            <ChangeList title="Identity" values={changes.identity} />
            <ChangeList title="Modules added" values={changes.modulesAdded} />
            <ChangeList title="Modules removed" values={changes.modulesRemoved} />
            <ChangeList title="Limit changes" values={changes.limits} />
            <ChangeList title="Commercial changes" values={changes.commercial} />
            {changes.createsRevision && changes.revisionNote ? (
                <div><p className="font-semibold">Revision reason</p><p className="mt-1 text-slate-700">{changes.revisionNote}</p></div>
            ) : null}
            {plan && plan.current_subscription_count > 0 ? <p className="rounded bg-amber-50 p-3 text-amber-900">{plan.current_subscription_count} current subscription(s) remain on their existing revision until explicitly reassigned.</p> : null}
        </div>
    );
}

function ChangeList({ title, values }: { title: string; values: string[] }) {
    if (values.length === 0) return null;
    return <div><p className="font-semibold">{title}</p><ul className="mt-1 list-disc space-y-1 pl-5">{values.map((value) => <li key={value}>{value}</li>)}</ul></div>;
}

function validatePlanForm(values: { name: string; slug: string; price: string; currencyId: string; effectiveAt: string; limits: LimitFormState }): { error: ApiError | null; currencyId: number | null; effectiveAt: string | null; limits: TenantPlanLimits } {
    const fields: Record<string, string[]> = {};
    if (values.name.trim() === '') fields.name = ['Plan name is required.'];
    if (values.slug.trim() === '') fields.slug = ['Plan slug is required.'];
    if (!isNonNegativeDecimal(values.price) || values.price.trim() === '') fields.price = ['Price must be a non-negative decimal amount.'];
    const paid = isNonNegativeDecimal(values.price) && compareDecimalStrings(values.price, '0') > 0;
    const parsedCurrencyId = values.currencyId === '' ? null : Number(values.currencyId);
    if (paid && parsedCurrencyId === null) fields.currency_id = ['A billing currency is required for a paid plan.'];
    else if (parsedCurrencyId !== null && (!Number.isSafeInteger(parsedCurrencyId) || parsedCurrencyId < 1)) fields.currency_id = ['Select a valid billing currency.'];
    let parsedEffectiveAt: string | null = null;
    if (values.effectiveAt !== '') {
        const date = new Date(values.effectiveAt);
        if (Number.isNaN(date.getTime())) fields.effective_at = ['Select a valid effective date.'];
        else parsedEffectiveAt = date.toISOString();
    }
    const parsedLimits: Partial<TenantPlanLimits> = {};
    for (const [key, raw] of Object.entries(values.limits) as Array<[keyof TenantPlanLimits, string]>) {
        if (raw.trim() === '') continue;
        const parsed = Number(raw);
        if (!Number.isSafeInteger(parsed) || parsed < 1) fields[`limits.${key}`] = ['Limit must be a positive whole number.'];
        else parsedLimits[key] = parsed;
    }
    return {
        error: Object.keys(fields).length > 0 ? new ApiError('Please correct the highlighted plan fields.', 422, 'CLIENT_VALIDATION_FAILED', 'validation', fields) : null,
        currencyId: parsedCurrencyId,
        effectiveAt: parsedEffectiveAt,
        limits: parsedLimits as TenantPlanLimits,
    };
}

function toLimitValue(value: number | undefined): string {
    return value === undefined ? '' : String(value);
}

function mergeCurrentCurrency(currencies: ReferenceRecord[], current: NamedReference | null): ReferenceRecord[] {
    if (!current || currencies.some((currency) => currency.id === current.id)) return currencies;
    return [{
        id: current.id,
        code: current.code ?? undefined,
        name: current.name,
        symbol: current.symbol,
        is_active: current.is_active ?? false,
        row_version: 0,
        updated_at: null,
    }, ...currencies];
}

function currencyLabel(currencies: ReferenceRecord[], currencyId: number | null): string {
    if (currencyId === null) return '';
    const currency = currencies.find((candidate) => candidate.id === currencyId);
    return currency?.code ?? currency?.name ?? 'Selected currency';
}
