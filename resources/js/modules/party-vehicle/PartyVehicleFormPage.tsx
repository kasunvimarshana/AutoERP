import { useEffect, useState, type ComponentType } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type {
    CreatePartyVehiclePayload,
    PartyVehicleRelationship,
    SupersedePartyVehiclePayload,
    VehicleOwnerType,
} from '@/shared/types/partyVehicle';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { parsePositiveInteger } from '@/shared/utils/routeParams';

interface LookupProps<T> {
    value: T | null;
    onChange: (value: T | null) => void;
    error?: string;
}

interface PartyVehicleFormPageProps<P extends { id: number }, V extends { id: number }> {
    ownerType: Exclude<VehicleOwnerType, 'company'>;
    title: string;
    listPath: string;
    PartyLookup: ComponentType<LookupProps<P>>;
    VehicleLookup: ComponentType<LookupProps<V>>;
    get: (id: number, signal?: AbortSignal) => Promise<PartyVehicleRelationship>;
    create: (payload: CreatePartyVehiclePayload) => Promise<PartyVehicleRelationship>;
    supersede: (id: number, payload: SupersedePartyVehiclePayload) => Promise<PartyVehicleRelationship>;
}

const ownershipOptions: Record<Exclude<VehicleOwnerType, 'company'>, string[]> = {
    customer: ['customer_owned'],
    supplier: ['third_party', 'leased', 'rented'],
};

export function PartyVehicleFormPage<P extends { id: number }, V extends { id: number }>({
    ownerType,
    title,
    listPath,
    PartyLookup,
    VehicleLookup,
    get,
    create,
    supersede,
}: PartyVehicleFormPageProps<P, V>) {
    const rawId = useParams().id;
    const relationshipId = parsePositiveInteger(rawId);
    const invalidRouteId = rawId !== undefined && relationshipId === null;
    const navigate = useNavigate();
    const [party, setParty] = useState<P | null>(null);
    const [vehicle, setVehicle] = useState<V | null>(null);
    const [loadedRelationship, setLoadedRelationship] = useState<PartyVehicleRelationship | null>(null);
    const [ownershipType, setOwnershipType] = useState(ownershipOptions[ownerType][0]);
    const [startedAt, setStartedAt] = useState(businessDateInputValue());
    const [endedAt, setEndedAt] = useState('');
    const [isCurrent, setIsCurrent] = useState(true);
    const [notes, setNotes] = useState('');
    const [correctionReason, setCorrectionReason] = useState('');
    const [loading, setLoading] = useState(relationshipId !== null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (relationshipId === null) return;
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) {
                setLoading(true);
                setError(null);
            }
        });
        void get(relationshipId, controller.signal)
            .then((relationship) => {
                if (relationship.owner_type !== ownerType) {
                    throw new Error(`This relationship belongs to the ${relationship.owner_type} ownership view.`);
                }
                setLoadedRelationship(relationship);
                setParty({ id: relationship.owner.id as number, code: relationship.owner.code, name: relationship.owner.name } as unknown as P);
                setVehicle({ ...relationship.vehicle, vehicle_number: relationship.vehicle.number } as unknown as V);
                setOwnershipType(relationship.ownership_type);
                setStartedAt(relationship.started_at.slice(0, 10));
                setEndedAt(relationship.ended_at?.slice(0, 10) ?? '');
                setIsCurrent(relationship.is_current);
                setNotes(relationship.notes ?? '');
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [get, ownerType, relationshipId]);

    const submit = async () => {
        if (!party || !vehicle || !startedAt || invalidRouteId) return;
        setSaving(true);
        setError(null);
        try {
            const common = {
                owner_type: ownerType,
                owner_id: party.id,
                ownership_type: ownershipType,
                started_at: startedAt,
                ended_at: endedAt || null,
                is_current: isCurrent,
                notes: notes || null,
            } as const;
            if (relationshipId === null) {
                await create({ vehicle_id: vehicle.id, ...common });
            } else if (loadedRelationship !== null) {
                await supersede(relationshipId, {
                    expected_version: loadedRelationship.row_version,
                    correction_reason: correctionReason,
                    ...common,
                });
            }
            navigate(listPath);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    if (invalidRouteId) {
        return <><ContentHeader title={`Invalid ${title}`} description="Open a relationship from its list." actions={<LinkButton variant="secondary" to={listPath}>Back to list</LinkButton>} /><Panel><p className="text-sm text-slate-600">The route does not contain a valid relationship identifier.</p></Panel></>;
    }
    if (loading) return <LoadingState />;

    const isCorrection = relationshipId !== null;
    return <>
        <ContentHeader
            title={`${isCorrection ? 'Supersede' : 'Create'} ${title}`}
            description={isCorrection
                ? 'Historical ownership is never edited in place. This creates a new revision and closes the previous one.'
                : 'Select the owner and vehicle through guided controls. The backend validates active identities and overlapping periods.'}
            actions={<LinkButton variant="secondary" to={listPath}>Back to list</LinkButton>}
        />
        <ErrorAlert error={error} />
        <div className="grid gap-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
            <PartyLookup value={party} onChange={setParty} error={fieldError(error, 'owner_id')} />
            {isCorrection && loadedRelationship ? (
                <div><div className="text-sm font-medium text-slate-700">Vehicle</div><div className="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><div className="font-semibold">{loadedRelationship.vehicle.registration_number ?? loadedRelationship.vehicle.number}</div><div className="text-xs text-slate-500">{[loadedRelationship.vehicle.make, loadedRelationship.vehicle.model].filter(Boolean).join(' · ')}</div></div></div>
            ) : <VehicleLookup value={vehicle} onChange={setVehicle} error={fieldError(error, 'vehicle_id')} />}
            <Select label="Ownership type" value={ownershipType} onChange={(event) => setOwnershipType(event.target.value)} options={ownershipOptions[ownerType].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} error={fieldError(error, 'ownership_type')} />
            <Input type="date" label="Start date" required max={endedAt || undefined} value={startedAt} onChange={(event) => setStartedAt(event.target.value)} error={fieldError(error, 'started_at')} />
            <Input type="date" label="End date" min={startedAt || undefined} value={endedAt} onChange={(event) => setEndedAt(event.target.value)} error={fieldError(error, 'ended_at')} />
            <label className="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" checked={isCurrent} onChange={(event) => setIsCurrent(event.target.checked)} />Set as current relationship</label>
            {isCorrection && <div className="md:col-span-2"><Textarea label="Correction reason" required value={correctionReason} onChange={(event) => setCorrectionReason(event.target.value)} error={fieldError(error, 'correction_reason')} /></div>}
            <div className="md:col-span-2"><Textarea label="Notes" value={notes} onChange={(event) => setNotes(event.target.value)} error={fieldError(error, 'notes')} /></div>
            <div className="md:col-span-2 flex justify-end gap-2"><LinkButton variant="secondary" to={listPath}>Cancel</LinkButton><Button loading={saving} disabled={!party || !vehicle || !startedAt || Boolean(endedAt && endedAt <= startedAt) || (isCorrection && !correctionReason.trim())} onClick={() => void submit()}>{isCorrection ? 'Create replacement revision' : 'Create relationship'}</Button></div>
        </div>
    </>;
}
