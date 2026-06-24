import { useEffect, useMemo, useState, type FormEvent } from 'react';
import type { ReferenceRecord } from '@/modules/reference-data/referenceDataTypes';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { TenantRecord } from '../tenantTypes';

interface PlatformTenantFormProps {
    tenant: TenantRecord | null;
    currencies: ReferenceRecord[];
    saving: boolean;
    error: ApiError | null;
    onCancel: () => void;
    onSubmit: (payload: FormData) => Promise<void>;
}

const MAX_LOGO_BYTES = 5 * 1024 * 1024;
const ALLOWED_LOGO_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

export function PlatformTenantForm({
    tenant,
    currencies,
    saving,
    error,
    onCancel,
    onSubmit,
}: PlatformTenantFormProps) {
    const initial = useMemo(() => ({
        code: tenant?.code ?? '',
        name: tenant?.name ?? '',
        slug: tenant?.slug ?? '',
        currencyId: tenant?.base_currency_id ? String(tenant.base_currency_id) : '',
        crossOrg: tenant?.cross_org_transactions ?? false,
    }), [tenant]);
    const [code, setCode] = useState(initial.code);
    const [name, setName] = useState(initial.name);
    const [slug, setSlug] = useState(initial.slug);
    const [currencyId, setCurrencyId] = useState(initial.currencyId);
    const [crossOrg, setCrossOrg] = useState(initial.crossOrg);
    const [logo, setLogo] = useState<File | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [logoError, setLogoError] = useState('');
    const previewUrl = useMemo(() => logo ? URL.createObjectURL(logo) : null, [logo]);
    useEffect(() => () => { if (previewUrl) URL.revokeObjectURL(previewUrl); }, [previewUrl]);

    const dirty = code !== initial.code
        || name !== initial.name
        || slug !== initial.slug
        || currencyId !== initial.currencyId
        || crossOrg !== initial.crossOrg
        || logo !== null
        || removeLogo;
    const confirmDiscard = useUnsavedChanges(dirty && !saving, 'Discard the unsaved tenant identity changes?');
    const stableIdentityLocked = Boolean(tenant && tenant.status !== 'draft');
    const baseCurrencyLocked = Boolean(tenant && tenant.status !== 'draft');
    const currencyOptions = mergeCurrentCurrency(currencies, tenant?.base_currency ?? null);

    function chooseLogo(file: File | null) {
        if (!file) {
            setLogo(null);
            setLogoError('');
            return;
        }
        if (!ALLOWED_LOGO_TYPES.has(file.type)) {
            setLogo(null);
            setLogoError('Choose a JPEG, PNG, or WebP image.');
            return;
        }
        if (file.size > MAX_LOGO_BYTES) {
            setLogo(null);
            setLogoError('Logo must be 5 MB or smaller.');
            return;
        }
        setLogo(file);
        setRemoveLogo(false);
        setLogoError('');
    }

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (logoError) return;
        const payload = new FormData();
        if (tenant) {
            payload.append('expected_version', String(tenant.row_version));
            if (code !== initial.code) payload.append('code', code.trim());
            if (name !== initial.name) payload.append('name', name.trim());
            if (slug !== initial.slug) payload.append('slug', slug.trim());
            if (currencyId !== initial.currencyId) payload.append('base_currency_id', currencyId);
            if (crossOrg !== initial.crossOrg) payload.append('cross_org_transactions', crossOrg ? '1' : '0');
        } else {
            payload.append('code', code.trim());
            payload.append('name', name.trim());
            payload.append('slug', slug.trim());
            payload.append('base_currency_id', currencyId);
            payload.append('cross_org_transactions', crossOrg ? '1' : '0');
        }
        if (logo) payload.append('logo', logo);
        if (removeLogo) payload.append('remove_logo', '1');
        await onSubmit(payload);
    }

    return (
        <form className="grid gap-4 md:grid-cols-2" onSubmit={(event) => void submit(event)}>
            <Input
                label="Tenant code"
                value={code}
                onChange={(event) => setCode(event.target.value.toUpperCase())}
                disabled={stableIdentityLocked || saving}
                error={fieldError(error, 'code')}
                hint="Stable identifier. It is locked after the tenant leaves draft status."
                required
            />
            <Input label="Tenant name" value={name} onChange={(event) => setName(event.target.value)} disabled={saving} error={fieldError(error, 'name')} required />
            <Input
                label="URL slug"
                value={slug}
                onChange={(event) => setSlug(event.target.value.toLowerCase())}
                disabled={stableIdentityLocked || saving}
                error={fieldError(error, 'slug')}
                hint="Lowercase letters, numbers, and internal hyphens only. Locked after draft status."
                required
            />
            <Select
                label="Base accounting currency"
                value={currencyId}
                onChange={(event) => setCurrencyId(event.target.value)}
                disabled={baseCurrencyLocked || saving}
                error={fieldError(error, 'base_currency_id')}
                options={currencyOptions.map((currency) => ({
                    value: currency.id,
                    label: `${currency.code ?? ''} — ${currency.name}${currency.is_active === false ? ' (inactive; current only)' : ''}`,
                }))}
                placeholder="Select base currency"
                hint={baseCurrencyLocked ? 'Base currency is locked after the tenant leaves draft status.' : 'Required before activation.'}
            />

            <div className="space-y-3 md:col-span-2">
                <Input
                    label="Tenant logo"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    error={logoError || fieldError(error, 'logo')}
                    hint="JPEG, PNG, or WebP; maximum 5 MB. Use a square image for consistent navigation display."
                    disabled={saving}
                    onChange={(event) => chooseLogo(event.target.files?.[0] ?? null)}
                />
                {previewUrl ? (
                    <div className="flex items-center gap-4 rounded-lg border border-slate-200 p-3">
                        <img src={previewUrl} alt="Selected tenant logo preview" className="h-16 w-16 rounded-lg border border-slate-200 object-contain" />
                        <div className="min-w-0 text-sm">
                            <p className="truncate font-medium text-slate-900">{logo?.name}</p>
                            <p className="text-slate-500">{logo ? `${(logo.size / 1024).toFixed(1)} KB` : ''}</p>
                            <Button variant="ghost" className="mt-1 px-2" disabled={saving} onClick={() => chooseLogo(null)}>Clear selected file</Button>
                        </div>
                    </div>
                ) : tenant?.has_logo ? (
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={removeLogo}
                            disabled={saving}
                            onChange={(event) => setRemoveLogo(event.target.checked)}
                        />
                        Remove the currently configured tenant logo when saving
                    </label>
                ) : null}
            </div>

            <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 md:col-span-2">
                <input type="checkbox" checked={crossOrg} disabled={saving} onChange={(event) => setCrossOrg(event.target.checked)} />
                Allow approved transactions across organization units
            </label>
            <p className="text-sm text-slate-500 md:col-span-2">
                This form manages tenant identity only. Foundation provisioning, domain verification, subscription assignment, and activation are separate controlled steps.
            </p>
            <div className="flex justify-end gap-2 md:col-span-2">
                <Button type="button" variant="secondary" disabled={saving} onClick={() => { if (confirmDiscard()) onCancel(); }}>Cancel</Button>
                <Button type="submit" loading={saving} disabled={!dirty || Boolean(logoError)}>{tenant ? 'Save tenant identity' : 'Create draft tenant'}</Button>
            </div>
        </form>
    );
}

function mergeCurrentCurrency(currencies: ReferenceRecord[], current: TenantRecord['base_currency']): ReferenceRecord[] {
    if (!current || currencies.some((currency) => currency.id === current.id)) return currencies;
    return [current as ReferenceRecord, ...currencies];
}
