import { useEffect, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
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
const EVENT_INTERNAL_TRANSFER = 'internal_transfer';

const customerRentalEventOptions = [
    EVENT_COMPANY_TO_CUSTOMER,
    EVENT_CUSTOMER_TO_COMPANY,
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
    [EVENT_INTERNAL_TRANSFER]: { from: 'company', to: 'company' },
};

const emptyForm = (eventType = EVENT_COMPANY_TO_CUSTOMER) => ({
    event_type: eventType,
    occurred_at: businessDateTimeInputValue(),
    odometer: '0',
    fuel_level_percent: '',
    location: '',
    from_role: eventRoles[eventType].from,
    to_role: eventRoles[eventType].to,
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
    return allocationAgreementKind(allocation) === AGREEMENT_KIND_OWNER_SUPPLY
        ? ownerSupplyEventOptions
        : customerRentalEventOptions;
}

function defaultEventType(allocation: RentalAllocation | null): string {
    return allocationAgreementKind(allocation) === AGREEMENT_KIND_OWNER_SUPPLY
        ? EVENT_OWNER_TO_COMPANY
        : EVENT_COMPANY_TO_CUSTOMER;
}

function setEventType<T extends ReturnType<typeof emptyForm>>(form: T, eventType: string): T {
    return {
        ...form,
        event_type: eventType,
        from_role: eventRoles[eventType].from,
        to_role: eventRoles[eventType].to,
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
    const [allocationRefresh, setAllocationRefresh] = useState(0);
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

                    return setEventType(current, eventType);
                });
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [allocationIdToLoad, allocationRefresh]);

    const selectedAllocationId = allocation?.id ?? allocationIdToLoad;
    const result = useApi(
        (signal) => listRentalCustodyEvents(
            { vehicle_allocation_id: selectedAllocationId ?? undefined, per_page: 50 },
            signal,
        ),
        [selectedAllocationId, refresh],
    );

    const submit = async (event: FormEvent) => {
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
            await createRentalCustodyEvent(allocation.id, allocationDetails.row_version, {
                ...form,
                fuel_level_percent: form.fuel_level_percent || null,
                location: form.location || null,
                condition_summary: form.condition_summary || null,
                damage_summary: form.damage_summary || null,
            });
            setForm(emptyForm(defaultEventType(allocationDetails)));
            setAllocationRefresh((value) => value + 1);
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
            setAllocationRefresh((value) => value + 1);
            setRefresh((value) => value + 1);
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
                <Button variant="secondary" onClick={() => void confirm(row)}>
                    Confirm
                </Button>
            ) : null,
        },
    ];
    const eventOptions = allowedEventOptions(allocationDetails);

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
            {canManage && (
                <Panel title="Record custody event" className="mt-5">
                    <form onSubmit={submit}>
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
                        <div className="mt-4 flex justify-end">
                            <Button type="submit" loading={saving} disabled={!allocationDetails || !form.occurred_at}>
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
