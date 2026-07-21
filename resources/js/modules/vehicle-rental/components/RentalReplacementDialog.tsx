import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Textarea } from '@/shared/components/Textarea';
import { notifySuccess } from '@/shared/notifications/appToast';
import { localDateTimeToOffsetIso, utcDateTimeToLocalInput } from '../rentalDateTime';
import { replaceRentalAssignment } from '../vehicleRentalApi';
import type { RentalAssignment, RentalReference, RentalReplacementPayload } from '../vehicleRentalTypes';
import {
    RentalAssignmentLookup,
    RentalDriverLookup,
    RentalVehicleLookup,
    type RentalLookupOption,
} from './VehicleRentalLookups';

interface RentalReplacementDialogProps {
    open: boolean;
    assignment: RentalAssignment | null;
    onClose: () => void;
    onSaved: (assignment: RentalAssignment) => void;
}

export function RentalReplacementDialog(props: RentalReplacementDialogProps) {
    const identity = `${props.open ? 'open' : 'closed'}:${props.assignment?.id ?? 'none'}:${props.assignment?.row_version ?? 0}`;
    return <RentalReplacementDialogForm key={identity} {...props} />;
}

function RentalReplacementDialogForm({
    open,
    assignment,
    onClose,
    onSaved,
}: RentalReplacementDialogProps) {
    const [vehicle, setVehicle] = useState<RentalLookupOption | null>(null);
    const [sourceAssignment, setSourceAssignment] = useState<RentalLookupOption | null>(null);
    const [driver, setDriver] = useState<RentalReference | null>(() => assignment?.driver ?? null);
    const [effectiveAt, setEffectiveAt] = useState('');
    const [oldReturnOdometer, setOldReturnOdometer] = useState(() => assignment?.return_odometer ?? '');
    const [newHandoverOdometer, setNewHandoverOdometer] = useState('');
    const [selfDrive, setSelfDrive] = useState(() => assignment?.self_drive ?? false);
    const [reason, setReason] = useState('');
    const [oldFuelLevel, setOldFuelLevel] = useState('');
    const [newFuelLevel, setNewFuelLevel] = useState('');
    const [oldConditionNotes, setOldConditionNotes] = useState('');
    const [newConditionNotes, setNewConditionNotes] = useState('');
    const [oldDamageNotes, setOldDamageNotes] = useState('');
    const [newDamageNotes, setNewDamageNotes] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const replacementEndsAt = utcDateTimeToLocalInput(assignment?.ends_at);
    const ownerReplacementContext = assignment?.side === 'owner_supply';
    const oldOdometerAvailable = assignment?.vehicle?.odometer_reading != null;
    const newOdometerAvailable = vehicle?.odometerAvailable === true;

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!assignment) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalReplacementPayload = {
                vehicle_id: vehicle?.id ?? 0,
                effective_at: localDateTimeToOffsetIso(effectiveAt),
                old_return_odometer: oldOdometerAvailable ? nullable(oldReturnOdometer) : null,
                new_handover_odometer: newOdometerAvailable ? nullable(newHandoverOdometer) : null,
                source_assignment_id: assignment.side === 'customer_use' ? sourceAssignment?.id ?? null : null,
                driver_employee_id: selfDrive ? null : driver?.id ?? null,
                self_drive: selfDrive,
                reason,
                old_fuel_level: nullable(oldFuelLevel),
                new_fuel_level: nullable(newFuelLevel),
                old_condition_notes: nullable(oldConditionNotes),
                new_condition_notes: nullable(newConditionNotes),
                old_damage_notes: nullable(oldDamageNotes),
                new_damage_notes: nullable(newDamageNotes),
                expected_version: assignment.row_version,
            };
            const saved = await replaceRentalAssignment(assignment.id, payload);
            notifySuccess('Rental vehicle replacement recorded successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} title={`Replace vehicle — ${assignment?.agreement?.code ?? ''}`} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} inline />
                <div className="grid gap-4 md:grid-cols-2">
                    <RentalVehicleLookup
                        value={vehicle}
                        ownerAgreementId={ownerReplacementContext ? assignment?.agreement?.id ?? null : null}
                        startsAt={ownerReplacementContext ? effectiveAt : undefined}
                        endsAt={ownerReplacementContext ? replacementEndsAt : undefined}
                        required
                        onChange={(value) => {
                            setVehicle(value);
                            setSourceAssignment(null);
                            setNewHandoverOdometer('');
                        }}
                        error={fieldError(error, 'vehicle_id')}
                    />
                    <Input
                        label="Effective at"
                        type="datetime-local"
                        required
                        value={effectiveAt}
                        error={fieldError(error, 'effective_at')}
                        onChange={(event) => {
                            setEffectiveAt(event.target.value);
                            setSourceAssignment(null);
                            if (ownerReplacementContext) {
                                setVehicle(null);
                                setNewHandoverOdometer('');
                            }
                        }}
                    />
                    {assignment?.side === 'customer_use' && (
                        <RentalAssignmentLookup
                            label="New vehicle owner agreement"
                            side="owner_supply"
                            value={sourceAssignment}
                            vehicleId={vehicle?.id ?? null}
                            startsAt={effectiveAt}
                            endsAt={replacementEndsAt}
                            disabled={!vehicle || !effectiveAt}
                            onChange={setSourceAssignment}
                            error={fieldError(error, 'source_assignment_id')}
                        />
                    )}
                    {oldOdometerAvailable ? (
                        <Input label="Old vehicle return odometer" type="number" min="0" step="0.000001" required value={oldReturnOdometer} error={fieldError(error, 'old_return_odometer')} onChange={(event) => setOldReturnOdometer(event.target.value)} />
                    ) : (
                        <OdometerUnavailable label="Old vehicle" />
                    )}
                    {vehicle && (newOdometerAvailable ? (
                        <Input label="New vehicle handover odometer" type="number" min="0" step="0.000001" required value={newHandoverOdometer} error={fieldError(error, 'new_handover_odometer')} onChange={(event) => setNewHandoverOdometer(event.target.value)} />
                    ) : (
                        <OdometerUnavailable label="Replacement vehicle" />
                    ))}
                    <div className="space-y-3">
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" checked={selfDrive} onChange={(event) => { setSelfDrive(event.target.checked); if (event.target.checked) setDriver(null); }} />
                            Self-drive replacement
                        </label>
                        {!selfDrive && <RentalDriverLookup value={driver} onChange={setDriver} error={fieldError(error, 'driver_employee_id')} />}
                    </div>
                    <Input label="Old fuel level" maxLength={50} value={oldFuelLevel} error={fieldError(error, 'old_fuel_level')} onChange={(event) => setOldFuelLevel(event.target.value)} />
                    <Input label="New fuel level" maxLength={50} value={newFuelLevel} error={fieldError(error, 'new_fuel_level')} onChange={(event) => setNewFuelLevel(event.target.value)} />
                </div>
                <Input label="Replacement reason" required maxLength={255} value={reason} error={fieldError(error, 'reason')} onChange={(event) => setReason(event.target.value)} />
                <div className="grid gap-4 md:grid-cols-2">
                    <Textarea label="Old vehicle condition" value={oldConditionNotes} error={fieldError(error, 'old_condition_notes')} onChange={(event) => setOldConditionNotes(event.target.value)} />
                    <Textarea label="New vehicle condition" value={newConditionNotes} error={fieldError(error, 'new_condition_notes')} onChange={(event) => setNewConditionNotes(event.target.value)} />
                    <Textarea label="Old vehicle damage" value={oldDamageNotes} error={fieldError(error, 'old_damage_notes')} onChange={(event) => setOldDamageNotes(event.target.value)} />
                    <Textarea label="New vehicle damage" value={newDamageNotes} error={fieldError(error, 'new_damage_notes')} onChange={(event) => setNewDamageNotes(event.target.value)} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>Replace vehicle</Button>
                </div>
            </form>
        </Modal>
    );
}

function OdometerUnavailable({ label }: { label: string }) {
    return (
        <p className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
            {label} has no available odometer. No reading is required.
        </p>
    );
}

function nullable(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}
