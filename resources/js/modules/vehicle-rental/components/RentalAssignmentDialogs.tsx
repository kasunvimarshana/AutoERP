import { useEffect, useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { notifySuccess } from '@/shared/notifications/appToast';
import {
    agreementDateBoundary,
    clampLocalDateTime,
    earliestLocalDateTime,
    formatLocalDateTime,
    latestLocalDateTime,
    localDateTimeToOffsetIso,
    nullableLocalDateTimeToOffsetIso,
    utcDateTimeToLocalInput,
} from '../rentalDateTime';
import {
    createRentalAssignment,
    getRentalAgreement,
    listRentalAssignmentLookup,
    recordRentalCustody,
    replaceRentalAssignment,
    updateRentalAssignment,
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
    rentalAssignmentOption,
    RentalAgreementLookup,
    RentalAssignmentLookup,
    RentalDriverLookup,
    RentalVehicleLookup,
    type RentalLookupOption,
} from './VehicleRentalLookups';

const SOURCE_LOOKUP_PAGE_SIZE = 100;

interface AssignmentFormState {
    side: RentalAssignmentSide;
    agreement: RentalReference | null;
    vehicle: RentalReference | null;
    sourceAssignment: RentalLookupOption | null;
    driver: RentalReference | null;
    startsAt: string;
    endsAt: string;
    handoverOdometer: string;
    selfDrive: boolean;
}

interface AgreementPeriod {
    startsOn: string;
    endsOn: string;
}

interface AgreementPeriodLookup extends AgreementPeriod {
    agreementId: number | null;
}

interface SourceLookupState {
    vehicleId: number | null;
    candidates: RentalLookupOption[];
    error: string;
}

interface RentalAssignmentDialogProps {
    open: boolean;
    assignment?: RentalAssignment | null;
    agreement?: RentalReference | null;
    side?: RentalAssignmentSide;
    lockAgreement?: boolean;
    onClose: () => void;
    onSaved: (assignment: RentalAssignment) => void;
}

export function RentalAssignmentDialog(props: RentalAssignmentDialogProps) {
    const identity = [
        props.open ? 'open' : 'closed',
        props.assignment?.id ?? 'new',
        props.assignment?.row_version ?? 0,
        props.side ?? 'customer_use',
        props.agreement?.id ?? 'none',
        props.lockAgreement ? 'locked' : 'editable',
    ].join(':');

    return <RentalAssignmentDialogForm key={identity} {...props} />;
}

function RentalAssignmentDialogForm({
    open,
    assignment = null,
    agreement = null,
    side = 'customer_use',
    lockAgreement = false,
    onClose,
    onSaved,
}: RentalAssignmentDialogProps) {
    const [state, setState] = useState<AssignmentFormState>(() => initialAssignmentState(side, agreement, assignment));
    const [agreementLookup, setAgreementLookup] = useState<AgreementPeriodLookup>({ agreementId: null, startsOn: '', endsOn: '' });
    const [sourceLookup, setSourceLookup] = useState<SourceLookupState>({ vehicleId: null, candidates: [], error: '' });
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const agreementId = state.agreement?.id ?? null;
    const selectedVehicleId = state.side === 'customer_use' ? state.vehicle?.id ?? null : null;

    useEffect(() => {
        if (!open || agreementId === null) return;

        const controller = new AbortController();
        const requestedAgreementId = agreementId;
        void getRentalAgreement(requestedAgreementId, controller.signal)
            .then((record) => setAgreementLookup({
                agreementId: requestedAgreementId,
                startsOn: record.starts_on ?? '',
                endsOn: record.ends_on ?? '',
            }))
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [agreementId, open]);

    useEffect(() => {
        if (!open || selectedVehicleId === null) return;

        const controller = new AbortController();
        const requestedVehicleId = selectedVehicleId;
        void listRentalAssignmentLookup('assignment-source', {
            assignment_side: 'owner_supply',
            vehicle_id: requestedVehicleId,
            page: 1,
            per_page: SOURCE_LOOKUP_PAGE_SIZE,
        }, controller.signal)
            .then((result) => setSourceLookup({
                vehicleId: requestedVehicleId,
                candidates: result.data.map(rentalAssignmentOption),
                error: '',
            }))
            .catch((requestError: unknown) => {
                if (controller.signal.aborted) return;
                setSourceLookup({
                    vehicleId: requestedVehicleId,
                    candidates: [],
                    error: toApiError(requestError).message,
                });
            });

        return () => controller.abort();
    }, [open, selectedVehicleId]);

    const agreementPeriod: AgreementPeriod = agreementLookup.agreementId === agreementId
        ? { startsOn: agreementLookup.startsOn, endsOn: agreementLookup.endsOn }
        : { startsOn: '', endsOn: '' };
    const sourceCandidates = selectedVehicleId !== null && sourceLookup.vehicleId === selectedVehicleId
        ? sourceLookup.candidates
        : null;
    const sourceLoadError = sourceLookup.vehicleId === selectedVehicleId ? sourceLookup.error : '';
    const eligibleSources = (sourceCandidates ?? [])
        .filter((candidate) => sourceOverlapsAgreement(candidate, agreementPeriod));
    const selectedSourceCandidate = state.sourceAssignment
        ? eligibleSources.find((candidate) => candidate.id === state.sourceAssignment?.id) ?? null
        : null;
    const resolvedSourceAssignment = selectedSourceCandidate
        ?? (eligibleSources.length === 1 ? eligibleSources[0] : null);
    const bounds = assignmentBounds(agreementPeriod, resolvedSourceAssignment);
    const fittedDates = fitAssignmentDateTimes(state.startsAt, state.endsAt, bounds);
    const sourceLoading = state.side === 'customer_use'
        && selectedVehicleId !== null
        && sourceCandidates === null;

    const setSide = (nextSide: RentalAssignmentSide) => {
        setState((current) => ({
            ...current,
            side: nextSide,
            agreement: null,
            sourceAssignment: null,
            startsAt: '',
            endsAt: '',
        }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (sourceLoading) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalAssignmentPayload = {
                agreement_id: state.agreement?.id ?? 0,
                vehicle_id: state.vehicle?.id ?? 0,
                side: state.side,
                starts_at: localDateTimeToOffsetIso(fittedDates.startsAt),
                ends_at: nullableLocalDateTimeToOffsetIso(fittedDates.endsAt),
                source_assignment_id: state.side === 'customer_use' ? resolvedSourceAssignment?.id ?? null : null,
                handover_odometer: nullable(state.handoverOdometer),
                driver_employee_id: state.selfDrive ? null : state.driver?.id ?? null,
                self_drive: state.selfDrive,
            };
            const saved = assignment
                ? await updateRentalAssignment(assignment.id, payload, assignment.row_version)
                : await createRentalAssignment(payload);
            notifySuccess(assignment
                ? 'Vehicle rental assignment updated successfully.'
                : 'Vehicle rental assignment created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const title = assignment
        ? `Edit vehicle operation — ${assignment.agreement?.code ?? assignment.vehicle?.code ?? assignment.id}`
        : lockAgreement
            ? `Select vehicle — ${state.agreement?.code ?? state.agreement?.name ?? ''}`
            : 'New vehicle rental assignment';
    const submitLabel = assignment
        ? 'Update assignment'
        : lockAgreement
            ? 'Select vehicle'
            : 'Create assignment';

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
                        onChange={(value: RentalLookupOption | null) => setState((current) => ({
                            ...current,
                            agreement: value,
                            sourceAssignment: null,
                            startsAt: '',
                            endsAt: '',
                        }))}
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
                        min={bounds.minimum || undefined}
                        max={bounds.maximum || undefined}
                        value={fittedDates.startsAt}
                        error={fieldError(error, 'starts_at')}
                        onChange={(event) => setState((current) => ({ ...current, startsAt: event.target.value }))}
                    />
                    <Input
                        label="Planned end"
                        type="datetime-local"
                        min={fittedDates.startsAt || bounds.minimum || undefined}
                        max={bounds.maximum || undefined}
                        value={fittedDates.endsAt}
                        error={fieldError(error, 'ends_at')}
                        onChange={(event) => setState((current) => ({ ...current, endsAt: event.target.value }))}
                    />
                    {state.side === 'customer_use' && (
                        <OwnerSourceField
                            candidates={sourceCandidates}
                            eligibleCandidates={eligibleSources}
                            value={resolvedSourceAssignment}
                            loadError={sourceLoadError}
                            disabled={!state.vehicle}
                            error={fieldError(error, 'source_assignment_id')}
                            onChange={(value) => setState((current) => ({ ...current, sourceAssignment: value }))}
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
                    <Button type="submit" loading={submitting} disabled={sourceLoading}>{submitLabel}</Button>
                </div>
            </form>
        </Modal>
    );
}

function OwnerSourceField({
    candidates,
    eligibleCandidates,
    value,
    loadError,
    disabled,
    error,
    onChange,
}: {
    candidates: RentalLookupOption[] | null;
    eligibleCandidates: RentalLookupOption[];
    value: RentalLookupOption | null;
    loadError: string;
    disabled: boolean;
    error?: string;
    onChange: (value: RentalLookupOption | null) => void;
}) {
    if (disabled) {
        return <p className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Select a vehicle to resolve its vehicle owner agreement.</p>;
    }
    if (candidates === null) {
        return <p className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Finding the vehicle owner agreement...</p>;
    }
    if (loadError) {
        return <p className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{loadError}</p>;
    }
    if (candidates.length === 0) {
        return (
            <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                No owner-supplied vehicle source is recorded for this vehicle. Continue without one only when the vehicle is company-owned for the full assignment period.
            </p>
        );
    }
    if (eligibleCandidates.length === 0) {
        return (
            <div className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                <p className="font-medium">The recorded vehicle owner assignment does not overlap this customer agreement period.</p>
                {candidates.map((candidate) => (
                    <p key={candidate.id} className="mt-1">{ownerSourceSummary(candidate)}</p>
                ))}
            </div>
        );
    }
    if (eligibleCandidates.length === 1 && value?.id === eligibleCandidates[0].id) {
        return (
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                <p className="font-medium">Vehicle owner agreement autoloaded</p>
                <p className="mt-1">{ownerSourceSummary(eligibleCandidates[0])}</p>
            </div>
        );
    }

    return (
        <Select
            label="Vehicle owner agreement"
            value={value?.id ? String(value.id) : ''}
            required
            disabled={disabled}
            placeholder="Select the vehicle owner agreement"
            options={eligibleCandidates.map((candidate) => ({
                value: String(candidate.id),
                label: ownerSourceSummary(candidate),
            }))}
            error={error}
            onChange={(event) => onChange(
                eligibleCandidates.find((candidate) => candidate.id === Number(event.target.value)) ?? null,
            )}
        />
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
    const [eventAt, setEventAt] = useState(() => eventType === 'handover' ? utcDateTimeToLocalInput(assignment?.starts_at) : '');
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
                event_at: localDateTimeToOffsetIso(eventAt),
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
                effective_at: localDateTimeToOffsetIso(effectiveAt),
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
                            label="New vehicle owner agreement"
                            side="owner_supply"
                            value={sourceAssignment}
                            vehicleId={vehicle?.id ?? null}
                            startsAt={effectiveAt}
                            endsAt={utcDateTimeToLocalInput(assignment.ends_at)}
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

function initialAssignmentState(
    side: RentalAssignmentSide,
    agreement: RentalReference | null,
    assignment: RentalAssignment | null,
): AssignmentFormState {
    if (assignment) {
        return {
            side: assignment.side,
            agreement: assignment.agreement ?? null,
            vehicle: assignment.vehicle ?? null,
            sourceAssignment: assignment.source_assignment ? {
                id: assignment.source_assignment.id,
                code: assignment.source_assignment.agreement?.code ?? undefined,
                name: assignment.source_assignment.agreement?.name ?? assignment.source_assignment.agreement?.code ?? `#${assignment.source_assignment.id}`,
            } : null,
            driver: assignment.driver ?? null,
            startsAt: utcDateTimeToLocalInput(assignment.starts_at),
            endsAt: utcDateTimeToLocalInput(assignment.ends_at),
            handoverOdometer: assignment.handover_odometer ?? '',
            selfDrive: assignment.self_drive,
        };
    }

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

function assignmentBounds(period: AgreementPeriod, source: RentalLookupOption | null): { minimum: string; maximum: string } {
    return {
        minimum: latestLocalDateTime(
            agreementDateBoundary(period.startsOn, 'start'),
            utcDateTimeToLocalInput(source?.assignmentStartsAt),
        ),
        maximum: earliestLocalDateTime(
            agreementDateBoundary(period.endsOn, 'end'),
            utcDateTimeToLocalInput(source?.assignmentEndsAt),
        ),
    };
}

function fitAssignmentDateTimes(
    startsAt: string,
    endsAt: string,
    bounds: { minimum: string; maximum: string },
): { startsAt: string; endsAt: string } {
    const fittedStart = clampLocalDateTime(startsAt || bounds.minimum, bounds.minimum, bounds.maximum);
    const endSeed = endsAt || bounds.maximum;
    const fittedEnd = endSeed
        ? clampLocalDateTime(endSeed, fittedStart || bounds.minimum, bounds.maximum)
        : '';

    return { startsAt: fittedStart, endsAt: fittedEnd };
}

function sourceOverlapsAgreement(source: RentalLookupOption, period: AgreementPeriod): boolean {
    const sourceStart = utcDateTimeToLocalInput(source.assignmentStartsAt);
    const sourceEnd = utcDateTimeToLocalInput(source.assignmentEndsAt);
    const agreementStart = agreementDateBoundary(period.startsOn, 'start');
    const agreementEnd = agreementDateBoundary(period.endsOn, 'end');

    return (!agreementEnd || !sourceStart || sourceStart <= agreementEnd)
        && (!sourceEnd || !agreementStart || sourceEnd >= agreementStart);
}

function ownerSourceSummary(source: RentalLookupOption): string {
    const owner = source.party?.name || source.party?.code;
    const agreement = source.agreement?.code || source.code || `#${source.id}`;
    const period = `${formatLocalDateTime(source.assignmentStartsAt)} → ${formatLocalDateTime(source.assignmentEndsAt)}`;
    return [owner, agreement, period].filter(Boolean).join(' • ');
}

function nullable(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}
