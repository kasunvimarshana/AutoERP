import { useState, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { createChart, updateChart } from './runningChartApi';
import { AirConditioningMode, COUNT_LABELS, DISTANCE_LABELS, type ChartFacts, type RunningChart } from './runningCharts';
import { localTimestampValue, operationalTimeZone, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, type VehicleUse } from './vehicleUse';
export function RunningChartEditor({ use, chart, correction, onSaved, onCancel }: { use: VehicleUse; chart?: RunningChart; correction?: boolean; onSaved: () => void; onCancel: () => void }) {
    const [reference, setReference] = useState(correction ? '' : chart?.reference ?? '');
    const [start, setStart] = useState(chart ? localTimestampValue(chart.starts_at) : ''); const [end, setEnd] = useState(chart ? localTimestampValue(chart.ends_at) : '');
    const [values, setValues] = useState<Record<string, string>>(() => Object.fromEntries([...Object.keys(DISTANCE_LABELS), ...Object.keys(COUNT_LABELS)].map(key => [key, String(chart?.[key as keyof ChartFacts] ?? '')])));
    const [ac, setAc] = useState<AirConditioningMode | ''>(chart?.ac_mode ?? ''); const [driver, setDriver] = useState(chart?.driver_observation ?? ''); const [notes, setNotes] = useState(chart?.notes ?? '');
    const [error, setError] = useState<ApiError | null>(null); const [saving, setSaving] = useState(false);
    async function submit(event: FormEvent) {
        event.preventDefault(); setSaving(true); setError(null);
        try {
            const facts: ChartFacts = { reference, starts_at: timestampWithOffset(start), ends_at: timestampWithOffset(end), start_odometer: values.start_odometer || null, end_odometer: values.end_odometer || null, garage_km: values.garage_km || null, commercial_km: values.commercial_km || null, normal_ot_minutes: values.normal_ot_minutes === '' ? null : Number(values.normal_ot_minutes), double_ot_minutes: values.double_ot_minutes === '' ? null : Number(values.double_ot_minutes), triple_ot_minutes: values.triple_ot_minutes === '' ? null : Number(values.triple_ot_minutes), night_outs: values.night_outs === '' ? null : Number(values.night_outs), ac_mode: ac || null, driver_observation: driver || null, notes: notes || null };
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
                <Input label="Driver observation (optional)" value={driver} onChange={e => setDriver(e.target.value)} /><p>This records the source observation; it does not assign an employee or calculate pay.</p>
                <Input label="Notes" value={notes} onChange={e => setNotes(e.target.value)} />
            </div></details>
        </fieldset><Button type="submit" loading={saving}>Save draft</Button> <Button variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
    </form>;
}
