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
import type { PartyVehiclePayload, PartyVehicleRelationship } from '@/shared/types/partyVehicle';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { parsePositiveInteger } from '@/shared/utils/routeParams';

interface LookupProps<T> {
    value: T | null;
    onChange: (value: T | null) => void;
    error?: string;
}

interface PartyVehicleFormPageProps<P extends { id: number }, V extends { id: number }> {
    partyKey: 'customer' | 'supplier';
    title: string;
    listPath: string;
    PartyLookup: ComponentType<LookupProps<P>>;
    VehicleLookup: ComponentType<LookupProps<V>>;
    get: (id: number, signal?: AbortSignal) => Promise<PartyVehicleRelationship>;
    create: (payload: PartyVehiclePayload) => Promise<PartyVehicleRelationship>;
    update: (id: number, payload: Partial<PartyVehiclePayload>) => Promise<PartyVehicleRelationship>;
}

export function PartyVehicleFormPage<P extends { id: number }, V extends { id: number }>({
    partyKey,
    title,
    listPath,
    PartyLookup,
    VehicleLookup,
    get,
    create,
    update,
}: PartyVehicleFormPageProps<P, V>) {
    const rawId = useParams().id;
    const relationshipId = parsePositiveInteger(rawId);
    const invalidRouteId = rawId !== undefined && relationshipId === null;
    const navigate = useNavigate();
    const [party, setParty] = useState<P | null>(null);
    const [vehicle, setVehicle] = useState<V | null>(null);
    const [relationshipType, setRelationshipType] = useState(
        partyKey === 'customer' ? 'customer_owned' : 'third_party',
    );
    const [startedAt, setStartedAt] = useState(businessDateInputValue());
    const [endedAt, setEndedAt] = useState('');
    const [isCurrent, setIsCurrent] = useState(true);
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(relationshipId !== null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (relationshipId === null) return;

        const controller = new AbortController();
        setLoading(true);
        setError(null);
        void get(relationshipId, controller.signal)
            .then((relationship) => {
                setParty((relationship[partyKey] ?? null) as P | null);
                setVehicle(relationship.vehicle as unknown as V);
                setRelationshipType(
                    relationship.relationship_type
                    ?? (partyKey === 'customer' ? 'customer_owned' : 'third_party'),
                );
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
    }, [get, partyKey, relationshipId]);

    const submit = async () => {
        if (!party || !vehicle || !startedAt || invalidRouteId) return;

        setSaving(true);
        setError(null);
        try {
            const payload: PartyVehiclePayload = {
                vehicle_id: vehicle.id,
                [`${partyKey}_id`]: party.id,
                relationship_type: relationshipType,
                started_at: startedAt,
                ended_at: endedAt || null,
                notes: notes || null,
                ...(relationshipId === null ? { is_current: isCurrent } : {}),
            };

            if (relationshipId !== null) {
                await update(relationshipId, {
                    relationship_type: payload.relationship_type,
                    started_at: payload.started_at,
                    ended_at: payload.ended_at,
                    notes: payload.notes,
                });
            } else {
                await create(payload);
            }
            navigate(listPath);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    if (invalidRouteId) {
        return (
            <>
                <ContentHeader
                    title={`Invalid ${title}`}
                    description="This edit route requires a valid positive relationship identifier."
                    actions={<LinkButton variant="secondary" to={listPath}>Back to list</LinkButton>}
                />
                <Panel>
                    <p className="text-sm text-slate-600">Open the relationship from its list instead of editing an invalid URL.</p>
                </Panel>
            </>
        );
    }

    if (loading) return <LoadingState />;

    return (
        <>
            <ContentHeader
                title={`${relationshipId !== null ? 'Edit' : 'Create'} ${title}`}
                description="Select the party and vehicle using guided controls. Current-relationship and date invariants are enforced by the backend."
                actions={<LinkButton variant="secondary" to={listPath}>Back to list</LinkButton>}
            />
            <ErrorAlert error={error} />
            <div className="grid gap-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
                <PartyLookup
                    value={party}
                    onChange={setParty}
                    error={fieldError(error, `${partyKey}_id`)}
                />
                <VehicleLookup
                    value={vehicle}
                    onChange={setVehicle}
                    error={fieldError(error, 'vehicle_id')}
                />
                <Select
                    label="Relationship type"
                    value={relationshipType}
                    onChange={(event) => setRelationshipType(event.target.value)}
                    options={(partyKey === 'customer'
                        ? ['customer_owned']
                        : ['third_party', 'leased', 'rented']
                    ).map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                    error={fieldError(error, 'relationship_type')}
                />
                <Input
                    type="date"
                    label="Start date"
                    required
                    max={endedAt || undefined}
                    value={startedAt}
                    onChange={(event) => setStartedAt(event.target.value)}
                    error={fieldError(error, 'started_at')}
                />
                <Input
                    type="date"
                    label="End date"
                    min={startedAt || undefined}
                    value={endedAt}
                    onChange={(event) => setEndedAt(event.target.value)}
                    error={fieldError(error, 'ended_at')}
                />
                {relationshipId === null && (
                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            checked={isCurrent}
                            onChange={(event) => setIsCurrent(event.target.checked)}
                        />
                        Set as current relationship
                    </label>
                )}
                <div className="md:col-span-2">
                    <Textarea
                        label="Notes"
                        value={notes}
                        onChange={(event) => setNotes(event.target.value)}
                        error={fieldError(error, 'notes')}
                    />
                </div>
                <div className="md:col-span-2 flex justify-end gap-2">
                    <LinkButton variant="secondary" to={listPath}>Cancel</LinkButton>
                    <Button
                        loading={saving}
                        disabled={!party || !vehicle || !startedAt || Boolean(endedAt && endedAt < startedAt)}
                        onClick={() => void submit()}
                    >
                        Save relationship
                    </Button>
                </div>
            </div>
        </>
    );
}
