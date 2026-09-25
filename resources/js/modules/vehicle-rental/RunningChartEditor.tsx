import { useState, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { createChart, updateChart } from './runningChartApi';
import { AirConditioningMode, COUNT_LABELS, DISTANCE_LABELS, DriverIdentitySource, type ChartFacts, type RunningChart } from './runningCharts';
import { localTimestampValue, operationalTimeZone, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, type VehicleUse } from './vehicleUse';

const employees = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.hrEmployees}/lookup/available`, params)
    .then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.display_name ?? row.name), code: String(row.employee_number ?? row.code ?? '') })) }));
const driverSourceOptions = [
    { value: '', label: 'Not recorded' },
    { value: DriverIdentitySource.Employee, label: 'Employee driver' },
    { value: DriverIdentitySource.External, label: 'External driver' },
];

export function RunningChartEditor({ use, chart, correction, onSaved, onCancel }: { use: VehicleUse; chart?: RunningChart; correction?: boolean; onSaved: () => void; onCancel: () => void }) {
    const [reference, setReference] = useState(correction ? '' : chart?.reference ?? '');
    const [start, setStart] = useState(chart ? localTimestampValue(chart.starts_at) : ''); const [end, setEnd] = useState(chart ? localTimestampValue(chart.ends_at) : '');
    const [values, setValues] = useState<Record<string, string>>(() => Object.fromEntries([...Object.keys(DISTANCE_LABELS), ...Object.keys(COUNT_LABELS)].map(key => [key, String(chart?.[key as keyof ChartFacts] ?? '')])));
    const [ac, setAc] = useState<AirConditioningMode | ''>(chart?.ac_mode ?? '');
    const [driverSource, setDriverSource] = useState<DriverIdentitySource | ''>(chart?.driver_identity_source ?? '');
    const [driverEmployee, setDriverEmployee] = useState<NamedResource | null>(() => chart?.driver?.source === DriverIdentitySource.Employee && chart.driver.employee_id ? { id: chart.driver.employee_id, name: chart.driver.name, code: chart.driver.reference } : null);
    const [externalDriverName, setExternalDriverName] = useState(chart?.driver?.source === DriverIdentitySource.External ? chart.driver.name : '');
    const [externalDriverReference, setExternalDriverReference] = useState(chart?.driver?.source === DriverIdentitySource.External ? chart.driver.reference : '');
    const [driverObservation, setDriverObservation] = useState(chart?.driver_observation ?? ''); const [notes, setNotes] = useState(chart?.notes ?? '');
    const [error, setError] = useState<ApiError | null>(null); const [saving, setSaving] = useState(false);
    async function submit(event: FormEvent) {
        event.preventDefault(); setSaving(true); setError(null);
        try {
            const facts: ChartFacts = {
                reference, starts_at: timestampWithOffset(start), ends_at: timestampWithOffset(end), start_odometer: values.start_odometer || null, end_odometer: values.end_odometer || null,
                garage_km: values.garage_km || null, commercial_km: values.commercial_km || null,
                normal_ot_minutes: values.normal_ot_minutes === '' ? null : Number(values.normal_ot_minutes), double_ot_minutes: values.double_ot_minutes === '' ? null : Number(values.double_ot_minutes),
                triple_ot_minutes: values.triple_ot_minutes === '' ? null : Number(values.triple_ot_minutes), night_outs: values.night_outs === '' ? null : Number(values.night_outs), ac_mode: ac || null,
                driver_identity_source: driverSource || null,
                driver_employee_id: driverSource === DriverIdentitySource.Employee ? driverEmployee?.id ?? null : null,
                driver_name_snapshot: driverSource === DriverIdentitySource.External ? externalDriverName || null : null,
                driver_reference_snapshot: driverSource === DriverIdentitySource.External ? externalDriverReference || null : null,
                driver_observation: driverObservation || null, notes: notes || null,
            };
            if (chart && !correction) await updateChart(chart, facts); else await createChart(use, facts, correction ? chart?.id : undefined);
            onSaved();
        } catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }
    const numeric = (key: string, label: string) => <Input key={key} label={label} value={values[key]} inputMode="decimal" onChange={e => setValues(v => ({ ...v, [key]: e.target.value }))} error={error?.fields[key]?.[0]} />;
    return <form aria-label="Running Chart" onSubmit={submit} className="space-y-3 rounded border p-4">
        <h4 className="font-semibold">{correction ? `Correct ${chart?.reference}` : chart ? 'Edit draft Running Chart' : 'Record Running Chart'}</h4>
        <p>Record actual usage in {operationalTimeZone}. Leave unknown observations blank; enter zero only when verified.</p><ErrorAlert error={error} inline />
        <fieldset disabled={saving} className="space-y-3">
            <Input label="Chart reference" required value={reference} onChange={e => setReference(e.target.value)} error={error?.fields.reference?.[0]} />
            <Input label="Usage start" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} required value={start} onChange={e => setStart(e.target.value)} error={error?.fields.starts_at?.[0]} />
            <Input label="Usage end" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} required value={end} onChange={e => setEnd(e.target.value)} error={error?.fields.ends_at?.[0]} />
            {numeric('start_odometer', DISTANCE_LABELS.start_odometer)}{numeric('end_odometer', DISTANCE_LABELS.end_odometer)}
            <details><summary>Additional usage observations</summary><div className="space-y-3 pt-3">
                {numeric('garage_km', DISTANCE_LABELS.garage_km)}{numeric('commercial_km', DISTANCE_LABELS.commercial_km)}
                {Object.entries(COUNT_LABELS).map(([key, label]) => numeric(key, label))}
                <label className="block">Air conditioning<select value={ac} onChange={e => setAc(e.target.value as AirConditioningMode | '')} className="block rounded border p-2"><option value="">Not recorded</option><option value={AirConditioningMode.NonAc}>Non-AC</option><option value={AirConditioningMode.Front}>Front AC</option><option value={AirConditioningMode.Dual}>Dual AC</option></select></label>
                <Select label="Driver identity" options={driverSourceOptions} value={driverSource} onChange={e => { const source = e.target.value as DriverIdentitySource | ''; setDriverSource(source); if (source !== DriverIdentitySource.Employee) setDriverEmployee(null); }} error={error?.fields.driver_identity_source?.[0]} />
                {driverSource === DriverIdentitySource.Employee && <LookupSelect label="Employee driver" value={driverEmployee} onChange={setDriverEmployee} search={employees} required error={error?.fields.driver_employee_id?.[0]} />}
                {driverSource === DriverIdentitySource.External && <><Input label="External driver name" value={externalDriverName} onChange={e => setExternalDriverName(e.target.value)} required error={error?.fields.driver_name_snapshot?.[0]} /><Input label="External driver reference" value={externalDriverReference} onChange={e => setExternalDriverReference(e.target.value)} required error={error?.fields.driver_reference_snapshot?.[0]} hint="Use a stable business reference, not a temporary note." /></>}
                <Input label="Driver observation (optional)" value={driverObservation} onChange={e => setDriverObservation(e.target.value)} /><p>This is operational evidence only; employee pay remains owned by HR.</p>
                <Input label="Notes" value={notes} onChange={e => setNotes(e.target.value)} />
            </div></details>
        </fieldset><Button type="submit" loading={saving}>Save draft</Button> <Button variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
    </form>;
}
