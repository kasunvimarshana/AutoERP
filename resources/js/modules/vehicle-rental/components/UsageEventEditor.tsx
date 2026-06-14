import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { createRentalUsageEvent } from '../vehicleRentalApi';
import type { RentalUsageEvent } from '../vehicleRentalTypes';

const eventTypes = ['extra_hour', 'extra_km', 'overtime', 'double_overtime', 'night_shift', 'weekend', 'holiday', 'day_out', 'night_out', 'driver', 'outstation', 'waiting', 'pass', 'other'];

export function UsageEventEditor({ agreementId, usageLogId, onSaved }: {
    agreementId: number;
    usageLogId: number;
    onSaved: (event: RentalUsageEvent) => void;
}) {
    const [form, setForm] = useState({ event_type: 'overtime', quantity: '1.000000', remarks: '' });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return (
        <Panel title="Usage event">
            <ErrorAlert error={error} />
            <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-5" onSubmit={async (submitEvent) => {
                submitEvent.preventDefault();
                setBusy(true);
                setError(null);
                try {
                    const saved = await createRentalUsageEvent(agreementId, usageLogId, {
                        ...form,
                        remarks: form.remarks || undefined,
                    });
                    onSaved(saved);
                    setForm((current) => ({ ...current, quantity: '1.000000', remarks: '' }));
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <Select label="Event type" value={form.event_type} options={eventTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, event_type: event.target.value })} />
                <DecimalInput label="Quantity" value={form.quantity} error={fieldError(error, 'quantity')} onChange={(event) => setForm({ ...form, quantity: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy}>Add event</Button></div>
                <div className="md:col-span-2 xl:col-span-2"><Textarea label="Remarks" value={form.remarks} onChange={(event) => setForm({ ...form, remarks: event.target.value })} /></div>
            </form>
        </Panel>
    );
}
