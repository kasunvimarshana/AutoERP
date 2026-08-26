import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Textarea } from '@/shared/components/Textarea';
import { notifySuccess } from '@/shared/notifications/appToast';
import { localDateTimeToOffsetIso, utcDateTimeToLocalInput } from '../rentalDateTime';
import { recordRentalCustody } from '../vehicleRentalApi';
import type { RentalAssignment, RentalCustodyPayload } from '../vehicleRentalTypes';

interface RentalCustodyDialogProps {
    open: boolean;
    assignment: RentalAssignment | null;
    eventType: 'handover' | 'return';
    onClose: () => void;
    onSaved: (assignment: RentalAssignment) => void;
}

export function RentalCustodyDialog(props: RentalCustodyDialogProps) {
    const identity = `${props.open ? 'open' : 'closed'}:${props.assignment?.id ?? 'none'}:${props.assignment?.row_version ?? 0}:${props.eventType}`;
    return <RentalCustodyDialogForm key={identity} {...props} />;
}

function RentalCustodyDialogForm({
    open,
    assignment,
    eventType,
    onClose,
    onSaved,
}: RentalCustodyDialogProps) {
    const [eventAt, setEventAt] = useState(() => eventType === 'handover' ? utcDateTimeToLocalInput(assignment?.starts_at) : '');
    const [odometer, setOdometer] = useState(() => eventType === 'handover' ? assignment?.handover_odometer ?? '' : assignment?.return_odometer ?? '');
    const [fuelLevel, setFuelLevel] = useState('');
    const [conditionNotes, setConditionNotes] = useState('');
    const [damageNotes, setDamageNotes] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const odometerAvailable = assignment?.vehicle?.odometer_reading != null;

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!assignment) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalCustodyPayload = {
                event_type: eventType,
                event_at: localDateTimeToOffsetIso(eventAt),
                odometer: odometerAvailable ? nullable(odometer) : null,
                fuel_level: nullable(fuelLevel),
                condition_notes: nullable(conditionNotes),
                damage_notes: nullable(damageNotes),
                expected_version: assignment.row_version,
            };
            const saved = await recordRentalCustody(assignment.id, payload);
            notifySuccess(eventType === 'handover' ? 'Vehicle handover recorded successfully.' : 'Vehicle return recorded successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} title={`${eventType === 'handover' ? 'Record handover' : 'Record return'} — ${assignment?.agreement?.code ?? ''}`} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-4" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} inline />
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Event time" type="datetime-local" required value={eventAt} error={fieldError(error, 'event_at')} onChange={(event) => setEventAt(event.target.value)} />
                    {odometerAvailable ? (
                        <Input label="Odometer" type="number" min="0" step="0.000001" required value={odometer} error={fieldError(error, 'odometer')} onChange={(event) => setOdometer(event.target.value)} />
                    ) : (
                        <p className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                            This vehicle has no available odometer. No reading is required.
                        </p>
                    )}
                    <Input label="Fuel level" maxLength={50} value={fuelLevel} error={fieldError(error, 'fuel_level')} onChange={(event) => setFuelLevel(event.target.value)} />
                </div>
                <Textarea label="Condition notes" value={conditionNotes} error={fieldError(error, 'condition_notes')} onChange={(event) => setConditionNotes(event.target.value)} />
                <Textarea label="Damage notes" value={damageNotes} error={fieldError(error, 'damage_notes')} onChange={(event) => setDamageNotes(event.target.value)} />
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{eventType === 'handover' ? 'Record handover' : 'Record return'}</Button>
                </div>
            </form>
        </Modal>
    );
}

function nullable(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}
