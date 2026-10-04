import { useCallback, useState, type FormEvent } from 'react';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { AGREEMENT_API, type Agreement } from './agreements';
import { planVehicleUse, replaceVehicleUse } from './vehicleUseApi';
import { operationalTimeZoneLabel, timestampWithOffset, OPERATIONAL_TIME_STEP_SECONDS, type VehicleUse } from './vehicleUse';
const vehicles = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params).then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.registration_number ?? row.vehicle_number) })) }));
export function VehicleUseEditor({ agreement, replacement, onSaved, onCancel }: { agreement: Agreement; replacement?: VehicleUse; onSaved: () => void; onCancel: () => void }) {
    const [vehicle, setVehicle] = useState<NamedResource | null>(null);
    const [owner, setOwner] = useState<NamedResource | null>(null);
    const [company, setCompany] = useState(false);
    const [oldOdometer, setOldOdometer] = useState(''); const [newOdometer, setNewOdometer] = useState('');
    const [start, setStart] = useState(''); const [end, setEnd] = useState(''); const [notes, setNotes] = useState('');
    const [error, setError] = useState<ApiError | null>(null); const [saving, setSaving] = useState(false);
    const sources = useCallback((params: LookupLoadParams) => requestLookup<NamedResource>(`${AGREEMENT_API}/vehicles/${vehicle?.id}/sources`, params, { starts_at: timestampWithOffset(start), ends_at: end ? timestampWithOffset(end) : '' }), [vehicle?.id, start, end]);
    async function submit(event: FormEvent) {
        event.preventDefault(); if (!vehicle || (!company && !owner)) return;
        setSaving(true); setError(null);
        try { const input = { vehicle_id: vehicle.id, owner_agreement_id: company ? null : owner!.id, starts_at: timestampWithOffset(start), ends_at: end ? timestampWithOffset(end) : null, notes: notes || null }; if (replacement) await replaceVehicleUse(replacement, { ...input, reason: notes, return_odometer: oldOdometer || null, handover_odometer: newOdometer || null }); else await planVehicleUse(agreement, input); onSaved(); }
        catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }
    return <form onSubmit={submit} className="space-y-4 rounded-lg border p-4" aria-label={replacement ? "Replace vehicle" : "Assign vehicle"}>
        <h3 className="font-semibold">{replacement ? `Replace ${replacement.vehicle.label}` : `Assign a vehicle to ${agreement.reference}`}</h3>
        <ErrorAlert error={error} inline />
        <p className="text-sm text-slate-600">Times use {operationalTimeZoneLabel()}. The planned end is the handover boundary for the next use, not a billing day-count rule.</p>
        <fieldset disabled={saving} className="grid gap-4 sm:grid-cols-2">
            <LookupSelect label="Vehicle" value={vehicle} onChange={value => { setVehicle(value); setOwner(null); }} search={vehicles} required error={error?.fields.vehicle_id?.[0]} />
            <Input label={replacement ? "Replacement time" : "Planned handover"} type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={start} onChange={e => { setStart(e.target.value); setOwner(null); }} required error={error?.fields.starts_at?.[0]} />
            <Input label="Planned return (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={end} onChange={e => { setEnd(e.target.value); setOwner(null); }} error={error?.fields.ends_at?.[0]} />
            <label className="flex items-center gap-2"><input type="checkbox" checked={company} onChange={event => { setCompany(event.target.checked); setOwner(null); }} />Company-owned vehicle</label>
            {company && <p className="text-sm text-slate-600">Vehicle ownership records must cover the entire period. No owner payable is created for company supply.</p>}
            {!company && vehicle && !start && <p>Enter the planned period to find owner agreements covering it.</p>}
            {!company && vehicle && start && <LookupSelect key={`${vehicle.id}:${start}:${end}`} label="Owner agreement" value={owner} onChange={setOwner} search={sources} required error={error?.fields.owner_agreement_id?.[0]} />}
            {!end && <p>Without a planned return, the agreement and supply must both be open-ended.</p>}
            {replacement && <><Input label="Old vehicle return odometer (optional)" value={oldOdometer} onChange={e => setOldOdometer(e.target.value)} inputMode="decimal" error={error?.fields.return_odometer?.[0]} /><Input label="Replacement handover odometer (optional)" value={newOdometer} onChange={e => setNewOdometer(e.target.value)} inputMode="decimal" error={error?.fields.handover_odometer?.[0]} /><p className="text-sm text-slate-600 sm:col-span-2">Return and handover are saved together. If either fails, neither is recorded. No replacement charges are calculated.</p></>}
            <div className="sm:col-span-2"><Textarea label={replacement ? "Replacement reason" : "Assignment notes"} required={!!replacement} value={notes} onChange={e => setNotes(e.target.value)} error={error?.fields.reason?.[0] ?? error?.fields.notes?.[0]} /></div>
        </fieldset>
        <div className="flex flex-wrap gap-2"><Button type="submit" loading={saving} disabled={!vehicle || !start || (!company && !owner) || (!!replacement && !notes.trim())}>{replacement ? "Confirm replacement" : "Save assignment"}</Button><Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button></div>
    </form>;
}
