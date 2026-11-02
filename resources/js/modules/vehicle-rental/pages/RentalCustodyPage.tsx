import { useEffect, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateTimeInputValue } from '@/shared/utils/businessDate';
import { readableRelation } from '@/shared/utils/object';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import { RentalAllocationLookupSelect } from '../components/RentalLookups';
import { RentalPage } from '../components/RentalPage';
import {
    confirmRentalCustodyEvent,
    createRentalCustodyEvent,
    getRentalAllocation,
    listRentalCustodyEvents,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type { RentalAllocation, RentalCustodyEvent } from '../vehicleRentalTypes';

const AGREEMENT_KIND_OWNER_SUPPLY = 'owner_supply';
const EVENT_OWNER_TO_COMPANY = 'owner_to_company';
const EVENT_COMPANY_TO_CUSTOMER = 'company_to_customer';
const EVENT_CUSTOMER_TO_COMPANY = 'customer_to_company';
const EVENT_COMPANY_TO_OWNER = 'company_to_owner';
const EVENT_REPLACEMENT_OUT = 'replacement_out';
const EVENT_REPLACEMENT_IN = 'replacement_in';
const EVENT_INTERNAL_TRANSFER = 'internal_transfer';

const customerRentalEventOptions = [
    EVENT_COMPANY_TO_CUSTOMER,
    EVENT_CUSTOMER_TO_COMPANY,
    EVENT_REPLACEMENT_OUT,
    EVENT_REPLACEMENT_IN,
    EVENT_INTERNAL_TRANSFER,
] as const;

const ownerSupplyEventOptions = [
    EVENT_OWNER_TO_COMPANY,
    EVENT_COMPANY_TO_OWNER,
    EVENT_INTERNAL_TRANSFER,
] as const;

const eventRoles: Record<string, { from: string; to: string }> = {
    [EVENT_OWNER_TO_COMPANY]: { from: 'owner', to: 'company' },
    [EVENT_COMPANY_TO_CUSTOMER]: { from: 'company', to: 'customer' },
    [EVENT_CUSTOMER_TO_COMPANY]: { from: 'customer', to: 'company' },
    [EVENT_COMPANY_TO_OWNER]: { from: 'company', to: 'owner' },
    [EVENT_REPLACEMENT_OUT]: { from: 'customer', to: 'company' },
    [EVENT_REPLACEMENT_IN]: { from: 'company', to: 'customer' },
    [EVENT_INTERNAL_TRANSFER]: { from: 'company', to: 'company' },
};

function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) return '';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const offset = date.getTimezoneOffset() * 60_000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

function toIsoDateTime(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toISOString();
}

function initialOccurredAt(allocation: RentalAllocation | null): string {
    const now = businessDateTimeInputValue();
    if (!allocation) return now;

    const fromLocal = toDateTimeLocal(allocation.allocated_from);
    const toLocal = allocation.allocated_to ? toDateTimeLocal(allocation.allocated_to) : null;

    if (fromLocal && now < fromLocal) {
        return fromLocal;
    }
    if (toLocal && now > toLocal) {
        return toLocal;
    }

    return now;
}

function initialOdometer(allocation: RentalAllocation | null): string {
    if (!allocation) return '0';
    if (allocation.start_odometer !== null && allocation.start_odometer !== undefined && allocation.start_odometer !== '') {
        return String(allocation.start_odometer);
    }

    return '0';
}

const emptyForm = (eventType = EVENT_COMPANY_TO_CUSTOMER, occurredAt = businessDateTimeInputValue(), odometer = '0') => ({
    event_type: eventType,
    occurred_at: occurredAt,
    odometer,
    fuel_level_percent: '',
    location: '',
    from_role: eventRoles[eventType]?.from ?? 'company',
    to_role: eventRoles[eventType]?.to ?? 'customer',
    condition_summary: '',
    damage_summary: '',
});

function allocationAgreementKind(allocation: RentalAllocation | null): string | null {
    const agreement = allocation?.agreement;

    return agreement && 'agreement_kind' in agreement && typeof agreement.agreement_kind === 'string'
        ? agreement.agreement_kind
        : null;
}

function allowedEventOptions(allocation: RentalAllocation | null): readonly string[] {
    if (!allocation) return customerRentalEventOptions;
    const isOwner = allocationAgreementKind(allocation) === AGREEMENT_KIND_OWNER_SUPPLY;
    const status = allocation.status;

    if (status === 'planned') {
        return isOwner
            ? [EVENT_OWNER_TO_COMPANY, EVENT_INTERNAL_TRANSFER]
            : [EVENT_COMPANY_TO_CUSTOMER, EVENT_INTERNAL_TRANSFER];
    }

    if (status === 'active') {
        return isOwner
            ? [EVENT_COMPANY_TO_OWNER, EVENT_INTERNAL_TRANSFER]
            : [EVENT_CUSTOMER_TO_COMPANY, EVENT_REPLACEMENT_OUT, EVENT_INTERNAL_TRANSFER];
    }

    if (['returned', 'replaced', 'completed'].includes(status)) {
        return [];
    }

    return isOwner ? ownerSupplyEventOptions : customerRentalEventOptions;
}

function defaultEventType(allocation: RentalAllocation | null): string {
    const allowed = allowedEventOptions(allocation);
    if (allowed.length > 0) {
        return allowed[0];
    }

    return allocationAgreementKind(allocation) === AGREEMENT_KIND_OWNER_SUPPLY
        ? EVENT_OWNER_TO_COMPANY
        : EVENT_COMPANY_TO_CUSTOMER;
}

function setEventType<T extends ReturnType<typeof emptyForm>>(form: T, eventType: string): T {
    return {
        ...form,
        event_type: eventType,
        from_role: eventRoles[eventType]?.from ?? 'company',
        to_role: eventRoles[eventType]?.to ?? 'customer',
    };
}

function allocationLookupValue(allocation: RentalAllocation): NamedResource {
    return {
        id: allocation.id,
        code: allocation.allocation_number,
        name: [
            allocation.allocation_number,
            allocation.vehicle?.registration_number ?? allocation.vehicle?.name,
            allocation.status,
        ].filter(Boolean).join(' - '),
    };
}

export default function RentalCustodyPage() {
    const auth = useAuth();
    const [params] = useSearchParams();
    const initialAllocationId = parsePositiveInteger(params.get('allocation_id'));
    const canManage = hasPermission(auth, vehicleRentalPermissions.custodyManage);
    const [allocation, setAllocation] = useState<NamedResource | null>(null);
    const [allocationDetails, setAllocationDetails] = useState<RentalAllocation | null>(null);
    const [allocationIdToLoad, setAllocationIdToLoad] = useState<number | null>(initialAllocationId);
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState(emptyForm);

    useEffect(() => {
        setAllocationIdToLoad(initialAllocationId);
    }, [initialAllocationId]);

    useEffect(() => {
        if (!allocationIdToLoad) {
            setAllocationDetails(null);
            setAllocation(null);
            setForm(emptyForm());

            return;
        }

        const controller = new AbortController();
        setError(null);

        void getRentalAllocation(allocationIdToLoad, controller.signal)
            .then((resource) => {
                setAllocation(allocationLookupValue(resource));
                setAllocationDetails(resource);
                setForm((current) => {
                    const allowed = allowedEventOptions(resource);
                    const eventType = allowed.includes(current.event_type)
                        ? current.event_type
                        : defaultEventType(resource);

                    return {
                        ...setEventType(current, eventType),
                        occurred_at: initialOccurredAt(resource),
                        odometer: resource.status === 'planned' && resource.start_odometer
                            ? String(resource.start_odometer)
                            : initialOdometer(resource),
                    };
                });
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [allocationIdToLoad]);

    const selectedAllocationId = allocation?.id ?? allocationIdToLoad;
    const result = useApi(
        (signal) => listRentalCustodyEvents(
            { vehicle_allocation_id: selectedAllocationId ?? undefined, per_page: 50 },
            signal,
        ),
        [selectedAllocationId, refresh],
    );

    const submit = async (event: FormEvent, confirmImmediately = false) => {
        event.preventDefault();
        if (!allocation || !allocationDetails) return;

        const allowed = allowedEventOptions(allocationDetails);
        if (!allowed.includes(form.event_type)) {
            setForm((current) => setEventType(current, defaultEventType(allocationDetails)));

            return;
        }

        setSaving(true);
        setError(null);
        try {
            const created = await createRentalCustodyEvent(allocation.id, {
                ...form,
                occurred_at: toIsoDateTime(form.occurred_at),
                fuel_level_percent: form.fuel_level_percent || null,
                location: form.location || null,
                condition_summary: form.condition_summary || null,
                damage_summary: form.damage_summary || null,
            });

            if (confirmImmediately && created?.id) {
                await confirmRentalCustodyEvent(created.id, created.row_version ?? 1);
            }

            const updatedAllocation = await getRentalAllocation(allocation.id);
            setAllocationDetails(updatedAllocation);
            setAllocation(allocationLookupValue(updatedAllocation));
            setForm(emptyForm(
                defaultEventType(updatedAllocation),
                initialOccurredAt(updatedAllocation),
                initialOdometer(updatedAllocation),
            ));
            setRefresh((value) => value + 1);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const confirm = async (row: RentalCustodyEvent) => {
        setError(null);
        try {
            await confirmRentalCustodyEvent(row.id, row.row_version);
            setRefresh((value) => value + 1);
            if (selectedAllocationId) {
                const updatedAllocation = await getRentalAllocation(selectedAllocationId);
                setAllocationDetails(updatedAllocation);
                setAllocation(allocationLookupValue(updatedAllocation));
                setForm((current) => ({
                    ...current,
                    event_type: defaultEventType(updatedAllocation),
                    from_role: eventRoles[defaultEventType(updatedAllocation)]?.from ?? 'company',
                    to_role: eventRoles[defaultEventType(updatedAllocation)]?.to ?? 'customer',
                    occurred_at: initialOccurredAt(updatedAllocation),
                    odometer: initialOdometer(updatedAllocation),
                }));
            }
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        }
    };

    const columns: DataColumn<RentalCustodyEvent>[] = [
        { key: 'event', header: 'Event', render: (row) => row.event_number },
        { key: 'vehicle', header: 'Vehicle', render: (row) => readableRelation(row.vehicle) },
        { key: 'type', header: 'Type', render: (row) => row.event_type.replaceAll('_', ' ') },
        { key: 'roles', header: 'Custody', render: (row) => `${row.from_role} -> ${row.to_role}` },
        { key: 'odometer', header: 'Odometer', render: (row) => row.odometer },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: '',
            render: (row) => canManage && row.status === 'draft' ? (
                <Button
                    variant="secondary"
                    disabled={requiresOwnerReceipt}
                    title={requiresOwnerReceipt ? 'Owner vehicle must be received before customer handover can be confirmed' : undefined}
                    onClick={() => void confirm(row)}
                >
                    Confirm
                </Button>
            ) : null,
        },
    ];
    const sourceAllocation = allocationDetails?.source_allocation;
    const requiresOwnerReceipt =
        allocationDetails !== null &&
        allocationDetails.vehicle_source_type === 'owner_supplied' &&
        sourceAllocation !== null &&
        sourceAllocation !== undefined &&
        sourceAllocation.status === 'planned';
    const eventOptions = allowedEventOptions(allocationDetails);
    const isCustodyClosed = allocationDetails !== null && ['returned', 'replaced', 'completed'].includes(allocationDetails.status);

    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle custody"
                description="Record owner, company and customer handovers against a selected allocation with condition, fuel and odometer evidence."
            />
            <ErrorAlert error={error ?? result.error} />
            <Panel title="Context">
                <RentalAllocationLookupSelect
                    value={allocation}
                    onChange={(value) => {
                        setAllocation(value);
                        setAllocationDetails(null);
                        setAllocationIdToLoad(value?.id ?? null);
                        if (value === null) setForm(emptyForm());
                    }}
                    required={canManage}
                />
            </Panel>
            {allocationDetails && (
                <Panel title="Allocation details" className="mt-5">
                    <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div>
                            <div className="text-xs text-slate-500">Status</div>
                            <div className="mt-1 font-medium"><StatusBadge status={allocationDetails.status} /></div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Vehicle</div>
                            <div className="mt-1 font-medium">{readableRelation(allocationDetails.vehicle) || '-'}</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Agreement</div>
                            <div className="mt-1 font-medium">{readableRelation(allocationDetails.agreement) || '-'}</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Start odometer</div>
                            <div className="mt-1 font-medium">{allocationDetails.start_odometer ?? '-'}</div>
                        </div>
                    </div>
                </Panel>
            )}
            {requiresOwnerReceipt && sourceAllocation && (
                <Panel className="mt-5 border-amber-300 bg-amber-50">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 className="text-sm font-semibold text-amber-900">
                                Owner vehicle receipt required before customer handover
                            </h3>
                            <p className="mt-1 text-sm text-amber-800">
                                This vehicle is owner-supplied via allocation{' '}
                                <span className="font-semibold text-amber-950">
                                    {sourceAllocation.allocation_number ?? sourceAllocation.name ?? `#${sourceAllocation.id}`}
                                </span>.
                                The company must first receive the vehicle from the owner before customer handover can be confirmed.
                            </p>
                        </div>
                        <div className="shrink-0">
                            <LinkButton
                                to={`/vehicle-rental/custody?allocation_id=${sourceAllocation.id}`}
                                variant="secondary"
                            >
                                Receive vehicle from owner first
                            </LinkButton>
                        </div>
                    </div>
                </Panel>
            )}
            {isCustodyClosed ? (
                <Panel title="Custody lifecycle complete" className="mt-5">
                    <p className="text-sm text-slate-600">
                        This allocation is {allocationDetails?.status}. Vehicle custody has been fully closed and no further events can be recorded.
                    </p>
                </Panel>
            ) : canManage && (
                <Panel
                    title={
                        allocationDetails?.status === 'planned'
                            ? (allocationAgreementKind(allocationDetails) === AGREEMENT_KIND_OWNER_SUPPLY ? 'Receive vehicle from owner' : 'Handover vehicle to customer')
                            : allocationDetails?.status === 'active'
                                ? (allocationAgreementKind(allocationDetails) === AGREEMENT_KIND_OWNER_SUPPLY ? 'Return vehicle to owner' : 'Return vehicle from customer')
                                : 'Record custody event'
                    }
                    className="mt-5"
                >
                    <form onSubmit={(e) => void submit(e, false)}>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Select
                                label="Event type"
                                value={form.event_type}
                                onChange={(event) => setForm((current) => setEventType(current, event.target.value))}
                                options={eventOptions.map((value) => ({
                                    value,
                                    label: value.replaceAll('_', ' '),
                                }))}
                            />
                            <Input
                                label="Occurred at"
                                type="datetime-local"
                                required
                                value={form.occurred_at}
                                min={allocationDetails ? toDateTimeLocal(allocationDetails.allocated_from) : undefined}
                                max={allocationDetails?.allocated_to ? toDateTimeLocal(allocationDetails.allocated_to) : undefined}
                                onChange={(event) => setForm({ ...form, occurred_at: event.target.value })}
                            />
                            <Input
                                label="Odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.odometer}
                                onChange={(event) => setForm({ ...form, odometer: event.target.value })}
                            />
                            <Input
                                label="Fuel level %"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value={form.fuel_level_percent}
                                onChange={(event) => setForm({ ...form, fuel_level_percent: event.target.value })}
                            />
                            <Select
                                label="From"
                                value={form.from_role}
                                disabled
                                options={['owner', 'company', 'customer'].map((value) => ({ value, label: value }))}
                            />
                            <Select
                                label="To"
                                value={form.to_role}
                                disabled
                                options={['owner', 'company', 'customer'].map((value) => ({ value, label: value }))}
                            />
                            <Input
                                label="Location"
                                value={form.location}
                                onChange={(event) => setForm({ ...form, location: event.target.value })}
                            />
                        </div>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Textarea
                                label="Condition"
                                value={form.condition_summary}
                                onChange={(event) => setForm({ ...form, condition_summary: event.target.value })}
                            />
                            <Textarea
                                label="Damage"
                                value={form.damage_summary}
                                onChange={(event) => setForm({ ...form, damage_summary: event.target.value })}
                            />
                        </div>
                        <div className="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                            {requiresOwnerReceipt && (
                                <span className="text-xs text-amber-800 mr-auto font-medium">
                                    Vehicle must be received from owner before customer handover can be recorded.
                                </span>
                            )}
                            <Button
                                type="button"
                                variant="secondary"
                                loading={saving}
                                disabled={!allocationDetails || !form.occurred_at || eventOptions.length === 0 || requiresOwnerReceipt}
                                onClick={(e) => void submit(e, true)}
                            >
                                Save & confirm
                            </Button>
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={!allocationDetails || !form.occurred_at || eventOptions.length === 0 || requiresOwnerReceipt}
                            >
                                Save custody event
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}
            <div className="mt-5">
                {result.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={result.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage={allocation ? 'No custody events recorded for this allocation.' : 'Select an allocation to review custody events.'}
                    />
                )}
            </div>
        </RentalPage>
    );
}
