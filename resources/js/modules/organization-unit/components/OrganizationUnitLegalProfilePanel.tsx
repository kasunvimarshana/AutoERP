import { useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import {
    organizationUnitApi,
    type OrganizationUnitLegalProfile,
    type OrganizationUnitLegalProfilePayload,
    type OrganizationUnitSummary,
} from '../organizationUnitApi';

interface LegalProfileForm {
    legal_name: string;
    tin: string;
    vat_registration_number: string;
    svat_registration_number: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone: string;
    email: string;
}

export function OrganizationUnitLegalProfilePanel({
    unit,
    canManage,
}: {
    unit: OrganizationUnitSummary;
    canManage: boolean;
}) {
    const profile = useApi(
        (signal) => organizationUnitApi.getLegalProfile(unit.id, signal),
        [unit.id],
    );

    return (
        <Panel>
            <h3 className="font-semibold text-slate-900">Legal and tax profile</h3>
            <p className="mt-1 text-sm text-slate-600">
                These details are snapshotted when financial documents are created. Later profile edits do not change issued invoices.
            </p>
            <ErrorAlert error={profile.error} />
            {profile.loading && profile.data === null ? <LoadingState label="Loading legal profile…" /> : (
                <LegalProfileEditor
                    key={`${unit.id}:${profile.data?.row_version ?? 'new'}`}
                    unit={unit}
                    profile={profile.data}
                    canManage={canManage}
                    onSaved={profile.setData}
                />
            )}
        </Panel>
    );
}

function LegalProfileEditor({
    unit,
    profile,
    canManage,
    onSaved,
}: {
    unit: OrganizationUnitSummary;
    profile: OrganizationUnitLegalProfile | null;
    canManage: boolean;
    onSaved: (saved: OrganizationUnitLegalProfile) => void;
}) {
    const [form, setForm] = useState<LegalProfileForm>(() => (
        profile ? profileForm(profile) : emptyForm(unit.name)
    ));
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const change = (field: keyof LegalProfileForm, value: string) => {
        setForm((current) => ({ ...current, [field]: value }));
    };

    const save = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setActionError(null);
        try {
            const payload: OrganizationUnitLegalProfilePayload = {
                ...(profile ? { expected_version: profile.row_version } : {}),
                legal_name: form.legal_name.trim(),
                tin: nullable(form.tin),
                vat_registration_number: nullable(form.vat_registration_number),
                svat_registration_number: nullable(form.svat_registration_number),
                address_line_1: form.address_line_1.trim(),
                address_line_2: nullable(form.address_line_2),
                city: nullable(form.city),
                state: nullable(form.state),
                postal_code: nullable(form.postal_code),
                country: nullable(form.country),
                phone: nullable(form.phone),
                email: nullable(form.email),
            };
            onSaved(await organizationUnitApi.saveLegalProfile(unit.id, payload));
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <ErrorAlert error={actionError} />
            <form className="mt-4 space-y-4" onSubmit={(event) => void save(event)}>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Input label="Legal name" value={form.legal_name} maxLength={255} onChange={(event) => change('legal_name', event.target.value)} required disabled={!canManage} />
                    <Input label="TIN" value={form.tin} maxLength={150} onChange={(event) => change('tin', event.target.value)} disabled={!canManage} />
                    <Input label="VAT registration number" value={form.vat_registration_number} maxLength={150} onChange={(event) => change('vat_registration_number', event.target.value)} disabled={!canManage} />
                    <Input label="SVAT registration number" value={form.svat_registration_number} maxLength={150} onChange={(event) => change('svat_registration_number', event.target.value)} disabled={!canManage} />
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Input label="Registered address line 1" value={form.address_line_1} maxLength={255} onChange={(event) => change('address_line_1', event.target.value)} required disabled={!canManage} />
                    <Input label="Registered address line 2" value={form.address_line_2} maxLength={255} onChange={(event) => change('address_line_2', event.target.value)} disabled={!canManage} />
                    <Input label="City" value={form.city} maxLength={255} onChange={(event) => change('city', event.target.value)} disabled={!canManage} />
                    <Input label="State / Province" value={form.state} maxLength={255} onChange={(event) => change('state', event.target.value)} disabled={!canManage} />
                    <Input label="Postal code" value={form.postal_code} maxLength={50} onChange={(event) => change('postal_code', event.target.value)} disabled={!canManage} />
                    <Input label="Country" value={form.country} maxLength={255} onChange={(event) => change('country', event.target.value)} disabled={!canManage} />
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Input label="Telephone" value={form.phone} maxLength={100} onChange={(event) => change('phone', event.target.value)} disabled={!canManage} />
                    <Input label="Email" type="email" value={form.email} maxLength={255} onChange={(event) => change('email', event.target.value)} disabled={!canManage} />
                </div>
                {canManage && <div className="flex justify-end"><Button type="submit" loading={saving}>Save legal profile</Button></div>}
            </form>
        </>
    );
}

function emptyForm(legalName: string): LegalProfileForm {
    return {
        legal_name: legalName,
        tin: '',
        vat_registration_number: '',
        svat_registration_number: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state: '',
        postal_code: '',
        country: '',
        phone: '',
        email: '',
    };
}

function profileForm(profile: OrganizationUnitLegalProfile): LegalProfileForm {
    return {
        legal_name: profile.legal_name,
        tin: profile.tin ?? '',
        vat_registration_number: profile.vat_registration_number ?? '',
        svat_registration_number: profile.svat_registration_number ?? '',
        address_line_1: profile.address_line_1,
        address_line_2: profile.address_line_2 ?? '',
        city: profile.city ?? '',
        state: profile.state ?? '',
        postal_code: profile.postal_code ?? '',
        country: profile.country ?? '',
        phone: profile.phone ?? '',
        email: profile.email ?? '',
    };
}

function nullable(value: string): string | null {
    const normalized = value.trim();
    return normalized === '' ? null : normalized;
}
