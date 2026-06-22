import { useEffect, useState, type FormEvent } from 'react';
import type { ReferenceRecord } from '@/modules/reference-data/referenceDataTypes';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { TenantPlan, TenantRecord } from '../tenantTypes';

export function PlatformTenantForm({
    tenant,
    plans,
    currencies,
    saving,
    onCancel,
    onSubmit,
}: {
    tenant: TenantRecord | null;
    plans: TenantPlan[];
    currencies: ReferenceRecord[];
    saving: boolean;
    onCancel: () => void;
    onSubmit: (payload: FormData) => Promise<void>;
}) {
    const [code, setCode] = useState('');
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [planId, setPlanId] = useState('');
    const [currencyId, setCurrencyId] = useState('');
    const [trialEndsAt, setTrialEndsAt] = useState('');
    const [subscriptionEndsAt, setSubscriptionEndsAt] = useState('');
    const [crossOrg, setCrossOrg] = useState(false);
    const [logo, setLogo] = useState<File | null>(null);

    useEffect(() => {
        setCode(tenant?.code ?? '');
        setName(tenant?.name ?? '');
        setSlug(tenant?.slug ?? '');
        setPlanId(tenant?.tenant_plan_id ? String(tenant.tenant_plan_id) : '');
        setCurrencyId(tenant?.base_currency_id ? String(tenant.base_currency_id) : '');
        setTrialEndsAt(toDateTimeLocal(tenant?.trial_ends_at));
        setSubscriptionEndsAt(toDateTimeLocal(tenant?.subscription_ends_at));
        setCrossOrg(tenant?.cross_org_transactions ?? false);
        setLogo(null);
    }, [tenant]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        const payload = new FormData();
        if (tenant) payload.append('expected_version', String(tenant.row_version));
        payload.append('code', code.trim());
        payload.append('name', name.trim());
        payload.append('slug', slug.trim());
        payload.append('tenant_plan_id', planId);
        payload.append('base_currency_id', currencyId);
        payload.append('trial_ends_at', trialEndsAt);
        payload.append('subscription_ends_at', subscriptionEndsAt);
        payload.append('cross_org_transactions', crossOrg ? '1' : '0');
        if (logo) payload.append('logo', logo);
        await onSubmit(payload);
    }

    const stableIdentityLocked = Boolean(tenant && tenant.status !== 'draft');
    const baseCurrencyLocked = Boolean(tenant?.activated_at);

    return (
        <form className="grid gap-4 md:grid-cols-2" onSubmit={(event) => void submit(event)}>
            <Input label="Tenant code" value={code} onChange={(event) => setCode(event.target.value.toUpperCase())} disabled={stableIdentityLocked} hint="Stable identifier. Locked after activation." required />
            <Input label="Tenant name" value={name} onChange={(event) => setName(event.target.value)} required />
            <Input label="URL slug" value={slug} onChange={(event) => setSlug(event.target.value.toLowerCase())} disabled={stableIdentityLocked} hint="Lowercase letters, numbers, and hyphens." />
            <Select label="Subscription plan" value={planId} onChange={(event) => setPlanId(event.target.value)} options={plans.map((plan) => ({ value: plan.id, label: `${plan.name} · ${plan.billing_interval}` }))} placeholder="No plan" />
            <Select label="Base accounting currency" value={currencyId} onChange={(event) => setCurrencyId(event.target.value)} disabled={baseCurrencyLocked} options={currencies.map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))} placeholder="Select base currency" />
            <Input label="Tenant logo" type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => setLogo(event.target.files?.[0] ?? null)} />
            <Input label="Trial ends" type="datetime-local" value={trialEndsAt} onChange={(event) => setTrialEndsAt(event.target.value)} />
            <Input label="Subscription ends" type="datetime-local" value={subscriptionEndsAt} onChange={(event) => setSubscriptionEndsAt(event.target.value)} />
            <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 md:col-span-2">
                <input type="checkbox" checked={crossOrg} onChange={(event) => setCrossOrg(event.target.checked)} />
                Allow approved transactions across organization units
            </label>
            <div className="flex justify-end gap-2 md:col-span-2">
                {tenant && <Button variant="secondary" onClick={onCancel}>Cancel</Button>}
                <Button type="submit" loading={saving}>{tenant ? 'Save tenant' : 'Create draft tenant'}</Button>
            </div>
        </form>
    );
}

function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const offset = date.getTimezoneOffset() * 60_000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}
