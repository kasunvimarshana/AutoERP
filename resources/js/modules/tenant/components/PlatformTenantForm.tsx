import { useState, type FormEvent } from 'react';
import type { ReferenceRecord } from '@/modules/reference-data/referenceDataTypes';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { TenantRecord } from '../tenantTypes';

interface PlatformTenantFormProps {
    tenant: TenantRecord | null;
    currencies: ReferenceRecord[];
    saving: boolean;
    onCancel: () => void;
    onSubmit: (payload: FormData) => Promise<void>;
}

export function PlatformTenantForm({
    tenant,
    currencies,
    saving,
    onCancel,
    onSubmit,
}: PlatformTenantFormProps) {
    const [code, setCode] = useState(tenant?.code ?? '');
    const [name, setName] = useState(tenant?.name ?? '');
    const [slug, setSlug] = useState(tenant?.slug ?? '');
    const [currencyId, setCurrencyId] = useState(tenant?.base_currency_id ? String(tenant.base_currency_id) : '');
    const [crossOrg, setCrossOrg] = useState(tenant?.cross_org_transactions ?? false);
    const [logo, setLogo] = useState<File | null>(null);

    async function submit(event: FormEvent) {
        event.preventDefault();
        const payload = new FormData();
        if (tenant) payload.append('expected_version', String(tenant.row_version));
        payload.append('code', code.trim());
        payload.append('name', name.trim());
        payload.append('slug', slug.trim());
        payload.append('base_currency_id', currencyId);
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
            <Select label="Base accounting currency" value={currencyId} onChange={(event) => setCurrencyId(event.target.value)} disabled={baseCurrencyLocked} options={currencies.map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))} placeholder="Select base currency" />
            <Input label="Tenant logo" type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => setLogo(event.target.files?.[0] ?? null)} />
            <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 md:col-span-2">
                <input type="checkbox" checked={crossOrg} onChange={(event) => setCrossOrg(event.target.checked)} />
                Allow approved transactions across organization units
            </label>
            <p className="text-sm text-slate-500 md:col-span-2">
                Subscription, onboarding, domain verification, and activation are managed as separate controlled steps after the draft tenant is saved.
            </p>
            <div className="flex justify-end gap-2 md:col-span-2">
                {tenant && <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>}
                <Button type="submit" loading={saving}>{tenant ? 'Save tenant' : 'Create draft tenant'}</Button>
            </div>
        </form>
    );
}
