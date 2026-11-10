import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { apiClient } from '@/shared/api/apiClient';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import { toApiError, ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Textarea } from '@/shared/components/Textarea';
import { formatBusinessDateTime, businessDateInputValue, businessDateTimeInputValueWithSeconds, toHourFloor } from '@/shared/utils/businessDate';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { AGREEMENT_API, type Agreement } from './agreements';
import { planVehicleUse, replaceVehicleUse } from './vehicleUseApi';
import { operationalTimeZoneLabel, localTimestampValue, timestampWithOffset, OPERATIONAL_TIME_STEP_SECONDS, type VehicleUse } from './vehicleUse';
const vehicles = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params).then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.registration_number || row.vehicle_number) })) }));
export function VehicleUseEditor({ agreement, replacement, initialVehicleId, onSaved, onCancel }: { agreement: Agreement; replacement?: VehicleUse; initialVehicleId?: number; onSaved: () => void; onCancel: () => void }) {
    const [vehicle, setVehicle] = useState<NamedResource | null>(null);
    const [owner, setOwner] = useState<NamedResource | null>(null);
    const [company, setCompany] = useState(false);
    const [sourceCount, setSourceCount] = useState<number | null>(null);
    const [oldOdometer, setOldOdometer] = useState(''); const [newOdometer, setNewOdometer] = useState('');
    const replacementHandoverLocal = (() => {
        if (!replacement?.handed_over_at) return undefined;
        try {
            return localTimestampValue(replacement.handed_over_at);
        } catch {
            return replacement.handed_over_at.slice(0, 19);
        }
    })();
    const [start, setStart] = useState(() => {
        if (replacement) {
            try {
                return businessDateTimeInputValueWithSeconds();
            } catch {
                return new Date().toISOString().slice(0, 19);
            }
        }
        return agreement.starts_on ? `${agreement.starts_on}T00:00` : '';
    });
    const [end, setEnd] = useState('');
    const [notes, setNotes] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const isSameReplacementVehicle = Boolean(replacement && vehicle && vehicle.id === replacement.vehicle.id);
    const minStart = replacementHandoverLocal
        ? toHourFloor(replacementHandoverLocal)
        : (agreement.starts_on ? `${agreement.starts_on}T00:00` : undefined);
    const maxEnd = agreement.ends_on ? `${agreement.ends_on}T23:59` : undefined;
    const todayEnd = `${businessDateInputValue()}T23:59:59`;
    const maxStart = replacement ? (end && end < todayEnd ? end : todayEnd) : (end || maxEnd);

    useEffect(() => {
        if (!replacement && initialVehicleId && !vehicle) {
            const controller = new AbortController();
            vehicles({ search: '', page: 1, perPage: 100, signal: controller.signal })
                .then(result => {
                    const found = result.data.find(v => v.id === initialVehicleId);
                    if (found) {
                        setVehicle(found);
                    } else {
                        apiClient.get<{ data: { id: number; registration_number?: string; vehicle_number: string } }>(
                            `${endpoints.vehicles}/${initialVehicleId}`,
                            { signal: controller.signal },
                        ).then(res => {
                            if (res.data?.data) {
                                const d = res.data.data;
                                setVehicle({ id: Number(d.id), name: String(d.registration_number || d.vehicle_number) });
                            }
                        }).catch(() => {});
                    }
                })
                .catch(() => {});
            return () => controller.abort();
        }
    }, [initialVehicleId, replacement, vehicle]);
    const sources = useCallback((params: LookupLoadParams) => {
        if (!vehicle?.id || !start) {
            return Promise.resolve({ data: [], links: {}, meta: undefined });
        }
        let startIso = '';
        let endIso = '';
        try {
            startIso = timestampWithOffset(start);
            if (end) endIso = timestampWithOffset(end);
        } catch {
            return Promise.resolve({ data: [], links: {}, meta: undefined });
        }
        return requestLookup<NamedResource>(
            `${AGREEMENT_API}/vehicles/${vehicle.id}/sources`,
            params,
            { starts_at: startIso, ends_at: endIso, customer_agreement_id: agreement.id },
        );
    }, [vehicle, start, end, agreement.id]);
    useEffect(() => {
        if (!company && vehicle && start && !owner) {
            let active = true;
            const controller = new AbortController();
            let startIso = '';
            let endIso = '';
            try {
                startIso = timestampWithOffset(start);
                if (end) endIso = timestampWithOffset(end);
            } catch {
                return;
            }
            requestLookup<NamedResource>(
                `${AGREEMENT_API}/vehicles/${vehicle.id}/sources`,
                { search: '', page: 1, perPage: 10, signal: controller.signal },
                { starts_at: startIso, ends_at: endIso, customer_agreement_id: agreement.id },
            ).then((res) => {
                if (active) {
                    setSourceCount(res.data.length);
                    if (res.data.length === 1) {
                        setOwner(res.data[0]);
                    }
                }
            }).catch(() => {});

            return () => {
                active = false;
                controller.abort();
            };
        }
    }, [company, vehicle, start, end, owner, agreement.id]);

    async function submit(event: FormEvent) {
        event.preventDefault(); if (!vehicle || !start || (!company && !owner)) return;
        if (replacement) {
            let startIso = '';
            try {
                startIso = timestampWithOffset(start);
            } catch {
                return;
            }
            if (replacement.handed_over_at && startIso <= replacement.handed_over_at) {
                setError(new ApiError(
                    "Replacement time must follow the previous vehicle's handover time.",
                    422,
                    'VALIDATION_FAILED',
                    'validation',
                    { starts_at: ["Replacement time must follow the previous vehicle's handover time."] },
                ));
                return;
            }
            const parsedStart = new Date(startIso);
            if (parsedStart.getTime() > Date.now() + 120_000) {
                setError(new ApiError(
                    'Replacement time cannot be in the future.',
                    422,
                    'VALIDATION_FAILED',
                    'validation',
                    { starts_at: ['Replacement time cannot be in the future.'] },
                ));
                return;
            }
        }
        setSaving(true); setError(null);
        try {
            const input = {
                vehicle_id: vehicle.id,
                owner_agreement_id: company ? null : owner!.id,
                starts_at: timestampWithOffset(start),
                ends_at: end ? timestampWithOffset(end) : null,
                notes: notes.trim() || null,
            };
            if (replacement) {
                await replaceVehicleUse(replacement, {
                    ...input,
                    reason: notes.trim(),
                    return_odometer: oldOdometer.trim() || null,
                    handover_odometer: newOdometer.trim() || null,
                });
            } else {
                await planVehicleUse(agreement, input);
            }
            onSaved();
        }
        catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }
    return <form onSubmit={submit} className="space-y-4 rounded-lg border p-4" aria-label={replacement ? "Replace vehicle" : "Assign vehicle"}>
        <h3 className="font-semibold">{replacement ? `Replace ${replacement.vehicle.label}` : `Assign a vehicle to ${agreement.reference}`}</h3>
        <ErrorAlert error={error} inline />
        <p className="text-sm text-slate-600">Times use {operationalTimeZoneLabel()}. The planned end is the handover boundary for the next use, not a billing day-count rule.</p>
        <fieldset disabled={saving} className="grid gap-4 sm:grid-cols-2">
            <LookupSelect
                label="Vehicle"
                value={vehicle}
                onChange={value => { setVehicle(value); setOwner(null); setSourceCount(null); }}
                search={vehicles}
                loadOnOpen
                minSearchLength={0}
                required
                error={isSameReplacementVehicle ? 'Select a different physical vehicle.' : error?.fields.vehicle_id?.[0]}
            />
            <div className="space-y-1">
                <Input
                    label={replacement ? "Replacement time" : "Planned handover"}
                    type="datetime-local"
                    step={OPERATIONAL_TIME_STEP_SECONDS}
                    min={minStart}
                    max={maxStart}
                    value={start}
                    onChange={e => { setStart(e.target.value); setOwner(null); setSourceCount(null); }}
                    required
                    hint={replacement?.handed_over_at
                        ? `Replacement time must follow previous handover (${formatBusinessDateTime(replacement.handed_over_at)}) and cannot be in the future.`
                        : (minStart ? `Agreement starts: ${agreement.starts_on}` : undefined)
                    }
                    error={error?.fields.starts_at?.[0]}
                />
                {replacement && (
                    <div className="flex flex-wrap gap-2 text-xs">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                try {
                                    setStart(businessDateTimeInputValueWithSeconds());
                                } catch {
                                    setStart(new Date().toISOString().slice(0, 19));
                                }
                                setOwner(null);
                                setSourceCount(null);
                            }}
                        >
                            Use current time
                        </Button>
                    </div>
                )}
            </div>
            <Input
                label="Planned return (optional)"
                type="datetime-local"
                step={OPERATIONAL_TIME_STEP_SECONDS}
                min={start ? toHourFloor(start) : (minStart ? toHourFloor(minStart) : undefined)}
                max={maxEnd}
                value={end}
                onChange={e => { setEnd(e.target.value); setOwner(null); setSourceCount(null); }}
                hint={agreement.ends_on ? `Agreement ends: ${agreement.ends_on}` : undefined}
                error={error?.fields.ends_at?.[0]}
            />
            <label className="flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"><input className="h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500" type="checkbox" checked={company} onChange={event => { setCompany(event.target.checked); setOwner(null); setSourceCount(null); }} /><span>Company-owned vehicle</span></label>
            {company && <p className="text-sm text-slate-600 sm:col-span-2">Vehicle ownership records must cover the entire period. No owner payable is created for company supply.</p>}
            {!company && vehicle && !start && <p className="text-sm text-slate-600 sm:col-span-2">Enter planned handover to find owner agreements covering it.</p>}
            {!company && vehicle && start && (
                <div className="space-y-1 sm:col-span-2">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-slate-700">Owner agreement</span>
                        {!owner && (
                            <button
                                type="button"
                                className="text-xs font-medium text-blue-600 hover:text-blue-800 underline disabled:opacity-50"
                                onClick={async () => {
                                    try {
                                        const res = await sources({ search: '', page: 1, perPage: 10, signal: new AbortController().signal });
                                        if (res.data.length > 0) {
                                            setOwner(res.data[0]);
                                        }
                                    } catch {}
                                }}
                            >
                                Auto-select owner agreement
                            </button>
                        )}
                    </div>
                    {!owner && sourceCount === 0 && (
                        <p className="rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-800">
                            No active owner agreement covers this vehicle on the handover date. If this vehicle is owned by the company, select &quot;Company-owned vehicle&quot; above.
                        </p>
                    )}
                    <LookupSelect
                        label="Owner agreement"
                        value={owner}
                        onChange={setOwner}
                        search={sources}
                        loadOnOpen
                        minSearchLength={0}
                        required
                        error={error?.fields.owner_agreement_id?.[0]}
                    />
                </div>
            )}
            {!end && <p className="text-xs text-slate-500 sm:col-span-2">Planned return is optional. When left blank, vehicle assignment continues under the covering owner agreement until returned or replaced.</p>}
            {replacement && (
                <>
                    <Input
                        label="Old vehicle return odometer (optional)"
                        value={oldOdometer}
                        onChange={e => setOldOdometer(e.target.value)}
                        inputMode="decimal"
                        min={replacement.handover_odometer ?? undefined}
                        hint={replacement.handover_odometer ? `Handover reading was ${replacement.handover_odometer}. Return reading cannot be lower.` : undefined}
                        error={error?.fields.return_odometer?.[0]}
                    />
                    <Input
                        label="Replacement handover odometer (optional)"
                        value={newOdometer}
                        onChange={e => setNewOdometer(e.target.value)}
                        inputMode="decimal"
                        error={error?.fields.handover_odometer?.[0]}
                    />
                    <p className="text-sm text-slate-600 sm:col-span-2">Return and handover are saved together. If either fails, neither is recorded. No replacement charges are calculated.</p>
                </>
            )}
            <div className="sm:col-span-2"><Textarea label={replacement ? "Replacement reason" : "Assignment notes"} required={!!replacement} value={notes} onChange={e => setNotes(e.target.value)} error={error?.fields.reason?.[0] ?? error?.fields.notes?.[0]} /></div>
        </fieldset>
        <div className="flex flex-wrap gap-2"><Button type="submit" loading={saving} disabled={!vehicle || !start || (!company && !owner) || (!!replacement && !notes.trim()) || isSameReplacementVehicle}>{replacement ? "Confirm replacement" : "Save assignment"}</Button><Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button></div>
    </form>;
}
