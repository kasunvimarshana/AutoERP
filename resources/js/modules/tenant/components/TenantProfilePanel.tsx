import { useState, type FormEvent } from 'react';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getTenantProfile, updateTenantProfile } from '../tenantApi';
import type { TenantRecord } from '../tenantTypes';

type TenantProfilePanelProps = {
    canManage: boolean;
};

export function TenantProfilePanel({ canManage }: TenantProfilePanelProps) {
    const profile = useApi((signal) => getTenantProfile(signal), []);
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), []);

    if (profile.loading && !profile.data) return <LoadingState label="Loading tenant profile..." />;
    if (!profile.data) return <ErrorAlert error={profile.error ?? new ApiError('Tenant profile is unavailable.', null)} />;

    return (
        <TenantProfileEditor
            key={`${profile.data.id}-${profile.data.row_version}`}
            tenant={profile.data}
            currencyOptions={currencies.data ?? []}
            loadError={profile.error ?? currencies.error}
            canManage={canManage}
            onSaved={profile.setData}
        />
    );
}

function TenantProfileEditor({ tenant, currencyOptions, loadError, canManage, onSaved }: {
    tenant: TenantRecord;
    currencyOptions: Array<{ id: number; code?: string | null; name: string }>;
    loadError: ApiError | null;
    canManage: boolean;
    onSaved: (tenant: TenantRecord) => void;
}) {
    const [name, setName] = useState(tenant.name);
    const [currencyId, setCurrencyId] = useState(tenant.base_currency_id ? String(tenant.base_currency_id) : '');
    const [logo, setLogo] = useState<File | null>(null);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<ApiError | null>(null);

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!canManage) return;
        setSaving(true);
        setSaveError(null);
        try {
            const payload = new FormData();
            payload.append('expected_version', String(tenant.row_version));
            payload.append('name', name.trim());
            payload.append('base_currency_id', currencyId);
            if (logo) payload.append('logo', logo);
            onSaved(await updateTenantProfile(payload));
        } catch (error: unknown) {
            setSaveError(toApiError(error));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            <ErrorAlert error={loadError ?? saveError} />
            <Panel title="Tenant identity">
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Stable tenant code</p>
                        <p className="mt-1 text-lg font-semibold text-slate-900">{tenant.code}</p>
                    </div>
                    <StatusBadge status={tenant.status} />
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Tenant name" value={name} error={fieldError(saveError, 'name')} onChange={(event) => setName(event.target.value)} disabled={!canManage} required />
                    <Select
                        label="Base accounting currency"
                        value={currencyId}
                        error={fieldError(saveError, 'base_currency_id')}
                        onChange={(event) => setCurrencyId(event.target.value)}
                        disabled={!canManage || tenant.activated_at !== null}
                        options={currencyOptions.map((currency) => ({ value: currency.id, label: `${currency.code ?? ''} — ${currency.name}` }))}
                        placeholder="Select base currency"
                    />
                    <Input
                        label="Tenant logo"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        error={fieldError(saveError, 'logo')}
                        onChange={(event) => setLogo(event.target.files?.[0] ?? null)}
                        disabled={!canManage}
                        hint="PNG, JPEG, or WebP. Maximum 5 MB."
                    />
                </div>
                <p className="mt-4 text-xs leading-5 text-slate-500">
                    Base accounting currency becomes immutable after the tenant is first activated. Document currencies and exchange rates remain controlled by their owning business modules.
                </p>
            </Panel>
            {canManage && <div className="flex justify-end"><Button type="submit" loading={saving}>Save tenant profile</Button></div>}
        </form>
    );
}
