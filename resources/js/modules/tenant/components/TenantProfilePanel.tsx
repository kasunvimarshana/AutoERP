import { useEffect, useState, type FormEvent } from 'react';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getTenantProfile, updateTenantProfile } from '../tenantApi';

export function TenantProfilePanel({ canManage }: { canManage: boolean }) {
    const profile = useApi((signal) => getTenantProfile(signal), []);
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), []);
    const [name, setName] = useState('');
    const [currencyId, setCurrencyId] = useState('');
    const [crossOrg, setCrossOrg] = useState(false);
    const [logo, setLogo] = useState<File | null>(null);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (!profile.data) return;
        setName(profile.data.name);
        setCurrencyId(profile.data.base_currency_id ? String(profile.data.base_currency_id) : '');
        setCrossOrg(profile.data.cross_org_transactions);
        setLogo(null);
    }, [profile.data]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!profile.data || !canManage) return;
        setSaving(true);
        setSaveError(null);
        try {
            const payload = new FormData();
            payload.append('expected_version', String(profile.data.row_version));
            payload.append('name', name.trim());
            payload.append('base_currency_id', currencyId);
            payload.append('cross_org_transactions', crossOrg ? '1' : '0');
            if (logo) payload.append('logo', logo);
            profile.setData(await updateTenantProfile(payload));
        } catch (error: unknown) {
            setSaveError(toApiError(error));
        } finally {
            setSaving(false);
        }
    }

    if (profile.loading && !profile.data) return <LoadingState label="Loading tenant profile..." />;
    if (!profile.data) return <ErrorAlert error={profile.error ?? new ApiError('Tenant profile is unavailable.', null)} />;

    const tenant = profile.data;
    return (
        <form className="space-y-5" onSubmit={submit}>
            <ErrorAlert error={profile.error ?? saveError} />
            <Panel title="Tenant identity">
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Stable tenant code</p>
                        <p className="mt-1 text-lg font-semibold text-slate-900">{tenant.code}</p>
                    </div>
                    <StatusBadge status={tenant.status} />
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Tenant name" value={name} onChange={(event) => setName(event.target.value)} disabled={!canManage} required />
                    <Select
                        label="Base accounting currency"
                        value={currencyId}
                        onChange={(event) => setCurrencyId(event.target.value)}
                        disabled={!canManage || currencies.loading || tenant.activated_at !== null}
                        options={(currencies.data ?? []).map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))}
                        placeholder="Select base currency"
                    />
                    <Input
                        label="Tenant logo"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        onChange={(event) => setLogo(event.target.files?.[0] ?? null)}
                        disabled={!canManage}
                        hint="PNG, JPEG, or WebP. Maximum 5 MB."
                    />
                    <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" checked={crossOrg} onChange={(event) => setCrossOrg(event.target.checked)} disabled={!canManage} />
                        Allow approved transactions across organization units
                    </label>
                </div>
                <p className="mt-4 text-xs leading-5 text-slate-500">
                    Base accounting currency becomes immutable after the tenant is first activated. Document currencies and exchange rates remain controlled by their owning business modules.
                </p>
            </Panel>
            {canManage && <div className="flex justify-end"><Button type="submit" loading={saving}>Save tenant profile</Button></div>}
        </form>
    );
}
