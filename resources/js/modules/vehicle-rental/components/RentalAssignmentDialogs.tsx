import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { notifySuccess } from '@/shared/notifications/appToast';
import {
    createRentalAssignment,
    recordRentalCustody,
    replaceRentalAssignment,
} from '../vehicleRentalApi';
import type {
    RentalAssignment,
    RentalAssignmentPayload,
    RentalAssignmentSide,
    RentalCustodyPayload,
    RentalReference,
    RentalReplacementPayload,
} from '../vehicleRentalTypes';
import {
    RentalAgreementLookup,
    RentalAssignmentLookup,
    RentalDriverLookup,
    RentalVehicleLookup,
    type RentalLookupOption,
} from './VehicleRentalLookups';

interface AssignmentFormState {
    side: RentalAssignmentSide;
    agreement: RentalReference | null;
    vehicle: RentalReference | null;
    sourceAssignment: RentalReference | null;
    driver: RentalReference | null;
    startsAt: string;
    endsAt: string;
    handoverOdometer: string;
    selfDrive: boolean;
}

interface RentalAssignmentDialogProps {
    open: boolean;
    agreement?: RentalReference | null;
    side?: RentalAssignmentSide;
    lockAgreement?: boolean;
    onClose: () => void;
    onSaved: (assignment: RentalAssignment) => void;
}

export function RentalAssignmentDialog(props: RentalAssignmentDialogProps) {
    const identity = `${props.open ? 'open' : 'closed'}:${props.side ?? 'customer_use'}:${props.agreement?.id ?? 'none'}:${props.lockAgreement ? 'locked' : 'editable'}`;
    return <RentalAssignmentDialogForm key={identity} {...props} />;
}

function RentalAssignmentDialogForm({
    open,
    agreement = null,
    side = 'customer_use',
    lockAgreement = false,
    onClose,
    onSaved,
}: RentalAssignmentDialogProps) {
    const [state, setState] = useState<AssignmentFormState>(() => initialAssignmentState(side, agreement));
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const setSide = (nextSide: RentalAssignmentSide) => {
        setState((current) => ({
            ...current,
            side: nextSide,
            agreement: null,
            sourceAssignment: null,
        }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalAssignmentPayload = {
                agreement_id: state.agreement?.id ?? 0,
                vehicle_id: state.vehicle?.id ?? 0,
                side: state.side,
                starts_at: state.startsAt,
                ends_at: nullable(state.endsAt),
                source_assignment_id: state.side === 'customer_use' ? state.sourceAssignment?.id ?? null : null,
                handover_odometer: nullable(state.handoverOdometer),
                driver_employee_id: state.selfDrive ? null : state.driver?.id ?? null,
                self_drive: state.selfDrive,
            };
            const saved = await createRentalAssignment(payload);
            notifySuccess('Vehicle rental assignment created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const title = lockAgreement
        ? `Select vehicle — ${state.agreement?.code ?? state.agreement?.name ?? ''}`
        : 'New vehicle rental assignment';

    return (
        <Modal open={open} title={title} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} inline />
                <div className="grid gap-4 md:grid-cols-2">
                    {!lockAgreement && (
                        <Select
                            label="Assignment side"
                            value={state.side}
                            required
                            options={[{ value: 'customer_use', label: 'Customer use' }, { value: 'owner_supply', label: 'Owner supply' }]}
                            error={fieldError(error, 'side')}
                            onChange={(event) => setSide(event.target.value as RentalAssignmentSide)}
                        />
                    )}
                    <RentalAgreementLookup
                        value={state.agreement}
                        kind={state.side === 'customer_use' ? 'customer' : 'owner'}
                        required
                        disabled={lockAgreement}
                        onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, agreement: value }))}
                        error={fieldError(error, 'agreement_id')}
                    />
                    <RentalVehicleLookup
                        value={state.vehicle}
                        required
                        onChange={(value: RentalLookupOption | null) => setState((current) => ({
                            ...current,
                            vehicle: value,
                            sourceAssignment: null,
                        }))}
                        error={fieldError(error, 'vehicle_id')}
                    />
                    <Input
                        label="Starts at"
                        type="datetime-local"
                        required
                        value={state.startsAt}
                        error={fieldError(error, 'starts_at')}
                        onChange={(event) => setState((current) => ({
                            ...current,
                            startsAt: event.target.value,
                            sourceAssignment: null,
                        }))}
                    />
                    <Input
                        label="Planned end"
                        type="datetime-local"
                        min={state.startsAt || undefined}
                        value={state.endsAt}
                        error={fieldError(error, 'ends_at')}
                        onChange={(event) => setState((current) => ({
                            ...current,
                            endsAt: event.target.value,
                            sourceAssignment: null,
                        }))}
                    />
                    {state.side === 'customer_use' && (
                        <RentalAssignmentLookup
                            label="Owner-supply source assignment"
                            side="owner_supply"
                            value={state.sourceAssignment}
                            vehicleId={state.vehicle?.id ?? null}
                            startsAt={state.startsAt}
                            endsAt={state.endsAt}
                            disabled={!state.vehicle || !state.startsAt}
                            onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, sourceAssignment: value }))}
                            error={fieldError(error, 'source_assignment_id')}
                        />
                    )}
                    <Input label="Planned handover odometer" type="number" min="0" step="0.000001" value={state.handoverOdometer} error={fieldError(error, 'handover_odometer')} onChange={(event) => setState((current) => ({ ...current, handoverOdometer: event.target.value }))} />
                    <div className="space-y-3">
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input
                                type="checkbox"
                                checked={state.selfDrive}
                                onChange={(event) => setState((current) => ({ ...current, selfDrive: event.target.checked, driver: event.target.checked ? null : current.driver }))}
                            />
                            Self-drive assignment
                        </label>
                        {!state.selfDrive && (
                            <RentalDriverLookup
                                value={state.driver}
                                onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, driver: value }))}
                                error={fieldError(error, 'driver_employee_id')}
                            />
                        )}
                    </div>
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{lockAgreement ? 'Select vehicle' : 'Create assignment'}</Button>
                </div>
            </form>
        </Modal>
    );
}

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
    const [eventAt, setEventAt] = useState(() => eventType === 'handover' ? toLocalDateTime(assignment?.starts_at) : '');
    const [odometer, setOdometer] = useState(() => eventType === 'handover' ? assignment?.handover_odometer ?? '' : assignment?.return_odometer ?? '');
    const [fuelLevel, setFuelLevel] = useState('');
    const [conditionNotes, setConditionNotes] = useState('');
    const [damageNotes, setDamageNotes] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!assignment) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalCustodyPayload = {
                event_type: eventType,
                event_at: eventAt,
                odometer,
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
                    <Input label="Odometer" type="number" min="0" step="0.000001" required value={odometer} error={fieldError(error, 'odometer')} onChange={(event) => setOdometer(event.target.value)} />
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
    const [vehicle, setVehicle] = useState<RentalReference | null>(null);
    const [sourceAssignment, setSourceAssignment] = useState<RentalReference | null>(null);
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

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!assignment) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalReplacementPayload = {
                vehicle_id: vehicle?.id ?? 0,
                effective_at: effectiveAt,
                old_return_odometer: oldReturnOdometer,
                new_handover_odometer: newHandoverOdometer,
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
                        required
                        onChange={(value) => {
                            setVehicle(value);
                            setSourceAssignment(null);
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
                        }}
                    />
                    {assignment?.side === 'customer_use' && (
                        <RentalAssignmentLookup
                            label="New owner-supply source"
                            side="owner_supply"
                            value={sourceAssignment}
                            vehicleId={vehicle?.id ?? null}
                            startsAt={effectiveAt}
                            endsAt={assignment.ends_at ?? ''}
                            disabled={!vehicle || !effectiveAt}
                            onChange={setSourceAssignment}
                            error={fieldError(error, 'source_assignment_id')}
                        />
                    )}
                    <Input label="Old vehicle return odometer" type="number" min="0" step="0.000001" required value={oldReturnOdometer} error={fieldError(error, 'old_return_odometer')} onChange={(event) => setOldReturnOdometer(event.target.value)} />
                    <Input label="New vehicle handover odometer" type="number" min="0" step="0.000001" required value={newHandoverOdometer} error={fieldError(error, 'new_handover_odometer')} onChange={(event) => setNewHandoverOdometer(event.target.value)} />
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

function initialAssignmentState(side: RentalAssignmentSide, agreement: RentalReference | null): AssignmentFormState {
    return {
        side,
        agreement,
        vehicle: null,
        sourceAssignment: null,
        driver: null,
        startsAt: '',
        endsAt: '',
        handoverOdometer: '',
        selfDrive: false,
    };
}

function nullable(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function toLocalDateTime(value?: string | null): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 16);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
    return local.toISOString().slice(0, 16);
}
