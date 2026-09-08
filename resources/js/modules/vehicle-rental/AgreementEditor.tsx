import { saveAgreement } from './agreementApi';
import { useState, type FormEvent } from 'react';
import { lookupApi } from '@/shared/api/lookupApi';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { AgreementKind, DriverMode, RentalBasis, TERM_LABELS, type Agreement, type TermKey } from './agreements';

const currencies = (params: LookupLoadParams) => requestLookup<NamedResource>(endpoints.currencies, params);
const vehicles = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params)
    .then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.registration_number ?? row.vehicle_number), code: String(row.vehicle_number) })) }));
const basisOptions = [{ value: RentalBasis.Daily, label: 'Daily' }, { value: RentalBasis.Monthly, label: 'Monthly' }];
const driverOptions = [{ value: DriverMode.SelfDrive, label: 'Self-drive' }, { value: DriverMode.WithDriver, label: 'With driver' }];

export function AgreementEditor({ kind, record, onSaved, onCancel }: { kind: AgreementKind; record?: Agreement; onSaved: () => void; onCancel: () => void }) {
    const [party, setParty] = useState<NamedResource | null>(record?.party ?? null);
    const [currency, setCurrency] = useState<NamedResource | null>(record?.currency ?? null);
    const [vehicle, setVehicle] = useState<NamedResource | null>(record?.vehicle ? { id: record.vehicle.id, name: record.vehicle.registration_number ?? record.vehicle.vehicle_number } : null);
    const [reference, setReference] = useState(record?.reference ?? '');
    const [agreedOn, setAgreedOn] = useState(record?.agreed_on ?? '');
    const [startsOn, setStartsOn] = useState(record?.starts_on ?? '');
    const [endsOn, setEndsOn] = useState(record?.ends_on ?? '');
    const [basis, setBasis] = useState<RentalBasis | ''>(record?.basis ?? '');
    const [driver, setDriver] = useState<DriverMode | ''>(record?.driver_mode ?? '');
    const [terms, setTerms] = useState<Partial<Record<TermKey, string | null>>>(record?.terms ?? {});
    const [notes, setNotes] = useState(record?.notes ?? '');
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const fieldError = (key: string) => error?.fields[key]?.[0];
    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!party || !currency || (kind === AgreementKind.Owner && !vehicle)) return;
        setSaving(true); setError(null);
        try {
            await saveAgreement(kind, { reference, party_id: party.id, currency_id: currency.id,
                ...(kind === AgreementKind.Owner ? { vehicle_id: vehicle!.id } : {}), agreed_on: agreedOn, starts_on: startsOn,
                ends_on: endsOn || null, basis, driver_mode: driver, terms, notes: notes || null, expected_version: record?.row_version }, record?.id);
            onSaved();
        } catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }
    return <form onSubmit={submit} className="space-y-5 rounded-xl border border-slate-200 bg-white p-5">
        <h2 className="text-xl font-semibold">{record ? 'Edit draft agreement' : 'New agreement'}</h2>
        <ErrorAlert error={error} inline />
        <fieldset disabled={saving} className="grid gap-4 sm:grid-cols-2">
            <Input label="Agreement reference" value={reference} onChange={e => setReference(e.target.value)} required error={fieldError('reference')} />
            <LookupSelect label={kind === AgreementKind.Customer ? 'Customer' : 'Owner / supplier'} value={party} onChange={setParty} search={kind === AgreementKind.Customer ? lookupApi.customers : lookupApi.suppliers} required error={fieldError('party_id')} />
            {kind === AgreementKind.Owner && <LookupSelect label="Vehicle" value={vehicle} onChange={setVehicle} search={vehicles} required error={fieldError('vehicle_id')} />}
            <LookupSelect label="Currency" value={currency} onChange={setCurrency} search={currencies} required error={fieldError('currency_id')} />
            <Input label="Agreement date" type="date" value={agreedOn} onChange={e => setAgreedOn(e.target.value)} required error={fieldError('agreed_on')} />
            <Input label="Start date" type="date" value={startsOn} onChange={e => setStartsOn(e.target.value)} required error={fieldError('starts_on')} />
            <Input label="End date" type="date" value={endsOn} onChange={e => setEndsOn(e.target.value)} error={fieldError('ends_on')} hint="Leave blank if no end date has been agreed." />
            <Select label="Rental basis" options={basisOptions} value={basis} onChange={e => setBasis(e.target.value as RentalBasis)} required error={fieldError('basis')} />
            <Select label="Driver arrangement" options={driverOptions} value={driver} onChange={e => setDriver(e.target.value as DriverMode)} required error={fieldError('driver_mode')} />
        </fieldset>
        <details open={Boolean(error && Object.keys(error.fields).some(key => key.startsWith('terms.')))}>
            <summary className="cursor-pointer font-medium">Agreed rates and allowances</summary>
            <p className="my-3 text-sm text-slate-600">Enter only the terms agreed with this party. Blank means unknown; enter zero only when explicitly agreed.</p>
            <fieldset disabled={saving} className="grid gap-4 sm:grid-cols-2">
                {(Object.keys(TERM_LABELS) as TermKey[]).map(key => <Input key={key} label={TERM_LABELS[key]} inputMode="decimal" value={terms[key] ?? ''} onChange={e => setTerms({ ...terms, [key]: e.target.value || null })} error={fieldError(`terms.${key}`)} />)}
            </fieldset>
        </details>
        <Input label="Notes" value={notes} onChange={e => setNotes(e.target.value)} disabled={saving} error={fieldError('notes')} />
        <div className="flex gap-2"><Button type="submit" loading={saving} disabled={!party || !currency || (kind === AgreementKind.Owner && !vehicle)}>Save draft</Button><Button variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button></div>
    </form>;
}
