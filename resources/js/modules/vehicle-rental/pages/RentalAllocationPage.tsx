import { useEffect, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { getVehicle } from '@/modules/vehicle/vehicleApi';
import { listVehicleOwnerships as listPartyVehicleOwnerships } from '@/modules/vehicle/vehicleOwnershipApi';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { readableRelation } from '@/shared/utils/object';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import type { PartyVehicleRelationship } from '@/shared/types/partyVehicle';
import {
    RentalAvailableVehicleLookupSelect,
    RentalAgreementLookupSelect,
    RentalAllocationLookupSelect,
    RentalFinanceAgreementLookupSelect,
} from '../components/RentalLookups';
import { RentalPage } from '../components/RentalPage';
import {
    createRentalAllocation,
    getRentalAgreement,
    listRentalAllocations,
} from '../vehicleRentalApi';
import type { RentalAgreement, RentalAllocation } from '../vehicleRentalTypes';

interface AllocationForm {
    vehicleSourceType: string;
    allocatedFrom: string;
    allocatedTo: string;
    startOdometer: string;
}

type AllocationLookupValue = NamedResource & { vehicle?: VehicleSummary | null };

const AGREEMENT_KIND_OWNER_SUPPLY = 'owner_supply';
const SOURCE_TYPE_COMPANY_OWNED = 'company_owned';
const SOURCE_TYPE_OWNER_SUPPLIED = 'owner_supplied';
const SOURCE_TYPE_FINANCED = 'financed';
const OWNERSHIP_LOOKUP_PAGE_SIZE = 100;

const emptyForm = (): AllocationForm => ({
    vehicleSourceType: SOURCE_TYPE_COMPANY_OWNED,
    allocatedFrom: '',
    allocatedTo: '',
    startOdometer: '0',
});

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

function agreementLookupValue(agreement: RentalAgreement): NamedResource {
    return {
        id: agreement.id,
        code: agreement.agreement_number,
        name: [
            agreement.agreement_number,
            agreement.customer?.name ?? agreement.supplier?.name,
        ].filter(Boolean).join(' - '),
    };
}

function sourceTypeForAgreement(agreement: RentalAgreement | null): string {
    return agreement?.agreement_kind === AGREEMENT_KIND_OWNER_SUPPLY
        ? SOURCE_TYPE_OWNER_SUPPLIED
        : SOURCE_TYPE_COMPANY_OWNED;
}

function ownershipCoversPeriod(ownership: PartyVehicleRelationship, startsAtValue: string, endsAtValue: string): boolean {
    const startedAt = new Date(ownership.started_at);
    const endedAt = ownership.ended_at ? new Date(ownership.ended_at) : null;
    const periodStartsAt = new Date(startsAtValue);
    const periodEndsAt = new Date(endsAtValue);

    if (
        Number.isNaN(startedAt.getTime()) ||
        Number.isNaN(periodStartsAt.getTime()) ||
        Number.isNaN(periodEndsAt.getTime()) ||
        (endedAt !== null && Number.isNaN(endedAt.getTime()))
    ) {
        return false;
    }

    return startedAt.getTime() <= periodStartsAt.getTime()
        && (endedAt === null || endedAt.getTime() >= periodEndsAt.getTime());
}

function ownershipLabel(ownership: PartyVehicleRelationship | null): string {
    if (ownership === null) return '';

    return [
        ownership.vehicle.registration_number ?? ownership.vehicle.number,
        ownership.owner.name,
        ownership.ownership_type?.replaceAll('_', ' '),
    ].filter(Boolean).join(' - ');
}

export default function RentalAllocationPage() {
    const [params] = useSearchParams();
    const initialAgreementId = parsePositiveInteger(params.get('agreement_id'));
    const initialVehicleId = parsePositiveInteger(params.get('vehicle_id'));
    const [agreement, setAgreement] = useState<NamedResource | null>(null);
    const [agreementDetails, setAgreementDetails] = useState<RentalAgreement | null>(null);
    const [agreementIdToLoad, setAgreementIdToLoad] = useState<number | null>(initialAgreementId);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [sourceAllocation, setSourceAllocation] = useState<AllocationLookupValue | null>(null);
    const [financeAgreement, setFinanceAgreement] = useState<NamedResource | null>(null);
    const [ownerSupplyOwnership, setOwnerSupplyOwnership] = useState<PartyVehicleRelationship | null>(null);
    const [companyOwnership, setCompanyOwnership] = useState<PartyVehicleRelationship | null>(null);
    const [ownershipLoading, setOwnershipLoading] = useState(false);
    const [companyOwnershipLoading, setCompanyOwnershipLoading] = useState(false);
    const [ownershipHint, setOwnershipHint] = useState<string | null>(null);
    const [companyOwnershipHint, setCompanyOwnershipHint] = useState<string | null>(null);
    const [form, setForm] = useState<AllocationForm>(emptyForm);
    const [page, setPage] = useState(1);
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const isOwnerSupplyAgreement = agreementDetails?.agreement_kind === AGREEMENT_KIND_OWNER_SUPPLY;
    const ownerSupplySupplierId = agreementDetails?.supplier?.id ?? null;

    useEffect(() => {
        setAgreementIdToLoad(initialAgreementId);
    }, [initialAgreementId]);

    useEffect(() => {
        if (!agreementIdToLoad) {
            setAgreementDetails(null);

            return;
        }

        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setError(null);
        });

        void getRentalAgreement(agreementIdToLoad, controller.signal).then((resource) => {
            setAgreement(agreementLookupValue(resource));
            setAgreementDetails(resource);
            setSourceAllocation(null);
            setFinanceAgreement(null);
            setForm((current) => ({
                ...current,
                vehicleSourceType: sourceTypeForAgreement(resource),
                allocatedFrom: toDateTimeLocal(resource.starts_at),
                allocatedTo: toDateTimeLocal(resource.ends_at),
            }));
        }).catch((requestError: unknown) => {
            if (!controller.signal.aborted) setError(toApiError(requestError));
        });

        return () => controller.abort();
    }, [agreementIdToLoad]);

    useEffect(() => {
        if (!initialVehicleId) return;

        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setError(null);
        });

        void getVehicle(initialVehicleId, controller.signal)
            .then(setVehicle)
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [initialVehicleId]);

    useEffect(() => {
        setOwnerSupplyOwnership(null);
        setOwnershipHint(null);

        if (!isOwnerSupplyAgreement || !agreementDetails || !ownerSupplySupplierId || !vehicle || !form.allocatedFrom || !form.allocatedTo) {
            setOwnershipLoading(false);

            return;
        }

        const selectedVehicle = vehicle;
        const selectedStartAt = toIsoDateTime(form.allocatedFrom);
        const selectedEndAt = toIsoDateTime(form.allocatedTo);
        const controller = new AbortController();
        setOwnershipLoading(true);

        void listPartyVehicleOwnerships('supplier', {
            supplier_id: ownerSupplySupplierId,
            vehicle_id: selectedVehicle.id,
            status: 'active',
            per_page: OWNERSHIP_LOOKUP_PAGE_SIZE,
        }, controller.signal).then((response) => {
            if (controller.signal.aborted) return;

            const ownership = response.data.find((row) => row.is_current && ownershipCoversPeriod(row, selectedStartAt, selectedEndAt))
                ?? response.data.find((row) => ownershipCoversPeriod(row, selectedStartAt, selectedEndAt))
                ?? null;

            setOwnerSupplyOwnership(ownership);
            setOwnershipHint(ownership === null
                ? 'No active supplier ownership covers this agreement period.'
                : null);
        }).catch((requestError: unknown) => {
            if (!controller.signal.aborted) setError(toApiError(requestError));
        }).finally(() => {
            if (!controller.signal.aborted) setOwnershipLoading(false);
        });

        return () => controller.abort();
    }, [
        agreementDetails,
        isOwnerSupplyAgreement,
        ownerSupplySupplierId,
        form.allocatedFrom,
        form.allocatedTo,
        vehicle,
        vehicle?.id,
    ]);

    useEffect(() => {
        setCompanyOwnership(null);
        setCompanyOwnershipHint(null);

        if (isOwnerSupplyAgreement
            || form.vehicleSourceType !== SOURCE_TYPE_COMPANY_OWNED
            || !agreementDetails
            || !vehicle
            || !form.allocatedFrom
            || !form.allocatedTo) {
            setCompanyOwnershipLoading(false);

            return;
        }

        const selectedVehicle = vehicle;
        const selectedStartAt = toIsoDateTime(form.allocatedFrom);
        const selectedEndAt = toIsoDateTime(form.allocatedTo);
        const controller = new AbortController();
        setCompanyOwnershipLoading(true);

        void listPartyVehicleOwnerships('company', {
            vehicle_id: selectedVehicle.id,
            status: 'active',
            per_page: OWNERSHIP_LOOKUP_PAGE_SIZE,
        }, controller.signal).then((response) => {
            if (controller.signal.aborted) return;

            const ownership = response.data.find((row) => row.is_current && ownershipCoversPeriod(row, selectedStartAt, selectedEndAt))
                ?? response.data.find((row) => ownershipCoversPeriod(row, selectedStartAt, selectedEndAt))
                ?? null;

            setCompanyOwnership(ownership);
            setCompanyOwnershipHint(ownership === null
                ? 'No active company ownership covers this allocation period.'
                : null);
        }).catch((requestError: unknown) => {
            if (!controller.signal.aborted) setError(toApiError(requestError));
        }).finally(() => {
            if (!controller.signal.aborted) setCompanyOwnershipLoading(false);
        });

        return () => controller.abort();
    }, [
        agreementDetails,
        isOwnerSupplyAgreement,
        form.vehicleSourceType,
        form.allocatedFrom,
        form.allocatedTo,
        vehicle,
        vehicle?.id,
    ]);

    const result = useApi(
        (signal) => listRentalAllocations(
            { agreement_id: agreement?.id, page, per_page: 25 },
            signal,
        ),
        [agreement?.id, page, refresh],
    );

    const changeSourceType = (vehicleSourceType: string) => {
        setForm((current) => ({ ...current, vehicleSourceType }));
        setSourceAllocation(null);
        setFinanceAgreement(null);
        setCompanyOwnership(null);
        setCompanyOwnershipHint(null);
    };

    const clearVehicleAndSources = () => {
        setVehicle(null);
        setSourceAllocation(null);
        setFinanceAgreement(null);
        setOwnerSupplyOwnership(null);
        setCompanyOwnership(null);
        setCompanyOwnershipHint(null);
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!agreement || !agreementDetails || !vehicle) return;

        const vehicleSourceType = isOwnerSupplyAgreement
            ? SOURCE_TYPE_OWNER_SUPPLIED
            : form.vehicleSourceType;
        if (isOwnerSupplyAgreement && ownerSupplyOwnership === null) {
            setOwnershipHint('No active supplier ownership covers this agreement period.');

            return;
        }
        if (!isOwnerSupplyAgreement && vehicleSourceType === SOURCE_TYPE_COMPANY_OWNED && companyOwnership === null) {
            setCompanyOwnershipHint('No active company ownership covers this allocation period.');

            return;
        }

        setSaving(true);
        setError(null);
        try {
            await createRentalAllocation(agreement.id, agreementDetails.row_version, {
                vehicle_id: vehicle.id,
                vehicle_ownership_id: isOwnerSupplyAgreement
                    ? ownerSupplyOwnership?.id ?? null
                    : vehicleSourceType === SOURCE_TYPE_COMPANY_OWNED
                        ? companyOwnership?.id ?? null
                        : null,
                vehicle_source_type: vehicleSourceType,
                source_allocation_id: !isOwnerSupplyAgreement && vehicleSourceType === SOURCE_TYPE_OWNER_SUPPLIED
                    ? sourceAllocation?.id ?? null
                    : null,
                vehicle_finance_agreement_id: !isOwnerSupplyAgreement && vehicleSourceType === SOURCE_TYPE_FINANCED
                    ? financeAgreement?.id ?? null
                    : null,
                allocated_from: toIsoDateTime(form.allocatedFrom),
                allocated_to: form.allocatedTo ? toIsoDateTime(form.allocatedTo) : null,
                start_odometer: form.startOdometer,
            });
            setVehicle(null);
            setSourceAllocation(null);
            setFinanceAgreement(null);
            setOwnerSupplyOwnership(null);
            setCompanyOwnership(null);
            setCompanyOwnershipHint(null);
            setForm({
                ...emptyForm(),
                vehicleSourceType: sourceTypeForAgreement(agreementDetails),
                allocatedFrom: toDateTimeLocal(agreementDetails?.starts_at),
                allocatedTo: toDateTimeLocal(agreementDetails?.ends_at),
            });
            setPage(1);
            setRefresh((value) => value + 1);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const columns: DataColumn<RentalAllocation>[] = [
        { key: 'number', header: 'Allocation', render: (row) => row.allocation_number },
        { key: 'agreement', header: 'Agreement', render: (row) => readableRelation(row.agreement) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => readableRelation(row.vehicle) },
        {
            key: 'source',
            header: 'Source',
            render: (row) => row.vehicle_source_type.replaceAll('_', ' '),
        },
        {
            key: 'period',
            header: 'Period',
            render: (row) => `${row.allocated_from.slice(0, 10)} - ${row.allocated_to?.slice(0, 10) ?? 'open'}`,
        },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <LinkButton variant="secondary" to={`/vehicle-rental/allocations/${row.id}`}>
                    View
                </LinkButton>
            ),
        },
    ];

    const sourceSelectionValid = (() => {
        if (isOwnerSupplyAgreement) return ownerSupplyOwnership !== null;
        if (form.vehicleSourceType === SOURCE_TYPE_COMPANY_OWNED) return companyOwnership !== null;
        if (form.vehicleSourceType === SOURCE_TYPE_OWNER_SUPPLIED) return sourceAllocation !== null;
        if (form.vehicleSourceType === SOURCE_TYPE_FINANCED) return financeAgreement !== null;

        return true;
    })();
    const agreementStartsAt = agreementDetails ? toDateTimeLocal(agreementDetails.starts_at) : '';
    const agreementEndsAt = agreementDetails ? toDateTimeLocal(agreementDetails.ends_at) : '';
    const vehicleSourceType = isOwnerSupplyAgreement ? SOURCE_TYPE_OWNER_SUPPLIED : form.vehicleSourceType;
    const allocationStartAt = form.allocatedFrom ? toIsoDateTime(form.allocatedFrom) : null;
    const allocationEndAt = form.allocatedTo ? toIsoDateTime(form.allocatedTo) : null;
    const usesOwnerSourceAllocation = !isOwnerSupplyAgreement && vehicleSourceType === SOURCE_TYPE_OWNER_SUPPLIED;

    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle allocations"
                description="Assign a vehicle through guided agreement, vehicle and ownership controls. Internal database identifiers are never entered manually."
            />
            <ErrorAlert error={error ?? result.error} />
            <Panel title="Create allocation">
                <form onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <RentalAgreementLookupSelect
                            value={agreement}
                            onChange={(value) => {
                                setAgreement(value);
                                setAgreementDetails(null);
                                setAgreementIdToLoad(value?.id ?? null);
                                setVehicle(null);
                                setSourceAllocation(null);
                                setFinanceAgreement(null);
                                setOwnerSupplyOwnership(null);
                                setCompanyOwnership(null);
                                setCompanyOwnershipHint(null);
                                setPage(1);
                                if (value === null) setForm(emptyForm());
                            }}
                            required
                        />
                        {usesOwnerSourceAllocation ? (
                            <Input
                                label="Vehicle"
                                value={readableRelation(vehicle)}
                                readOnly
                                required
                                hint="Selected from the owner source allocation."
                            />
                        ) : (
                            <RentalAvailableVehicleLookupSelect
                                value={vehicle}
                                onChange={(value) => {
                                    setVehicle(value);
                                    setSourceAllocation(null);
                                    setFinanceAgreement(null);
                                    setOwnerSupplyOwnership(null);
                                    setCompanyOwnership(null);
                                    setCompanyOwnershipHint(null);
                                }}
                                startAt={allocationStartAt}
                                endAt={allocationEndAt}
                                required
                            />
                        )}
                        <Select
                            label="Vehicle source"
                            value={vehicleSourceType}
                            disabled={isOwnerSupplyAgreement}
                            onChange={(event) => changeSourceType(event.target.value)}
                            options={isOwnerSupplyAgreement
                                ? [{ value: SOURCE_TYPE_OWNER_SUPPLIED, label: 'Owner supplied' }]
                                : [
                                    { value: SOURCE_TYPE_COMPANY_OWNED, label: 'Company owned' },
                                    { value: SOURCE_TYPE_OWNER_SUPPLIED, label: 'Owner supplied' },
                                    { value: SOURCE_TYPE_FINANCED, label: 'Financed' },
                                ]}
                        />
                        {isOwnerSupplyAgreement && (
                            <Input
                                label="Owner vehicle ownership"
                                value={ownershipLoading ? 'Checking ownership...' : ownershipLabel(ownerSupplyOwnership)}
                                readOnly
                                required
                                hint={ownershipHint ?? undefined}
                            />
                        )}
                        {!isOwnerSupplyAgreement && form.vehicleSourceType === SOURCE_TYPE_COMPANY_OWNED && (
                            <Input
                                label="Company vehicle ownership"
                                value={companyOwnershipLoading ? 'Checking ownership...' : ownershipLabel(companyOwnership)}
                                readOnly
                                required
                                hint={companyOwnershipHint ?? undefined}
                            />
                        )}
                        {usesOwnerSourceAllocation && (
                            <RentalAllocationLookupSelect
                                value={sourceAllocation}
                                onChange={(value) => {
                                    const selected = value as AllocationLookupValue | null;
                                    setSourceAllocation(selected);
                                    setVehicle(selected?.vehicle ?? null);
                                    setFinanceAgreement(null);
                                    setCompanyOwnership(null);
                                    setCompanyOwnershipHint(null);
                                }}
                                agreementKind={AGREEMENT_KIND_OWNER_SUPPLY}
                                coversStartAt={allocationStartAt}
                                coversEndAt={allocationEndAt}
                                openOnly
                                disabled={!allocationStartAt || !allocationEndAt}
                                required
                                excludeId={null}
                            />
                        )}
                        {!isOwnerSupplyAgreement && form.vehicleSourceType === SOURCE_TYPE_FINANCED && (
                            <RentalFinanceAgreementLookupSelect
                                value={financeAgreement}
                                onChange={setFinanceAgreement}
                                vehicleId={vehicle?.id}
                                coversStartAt={allocationStartAt}
                                coversEndAt={allocationEndAt}
                                activeOnly
                                disabled={!vehicle}
                                required
                            />
                        )}
                        <Input
                            label="From"
                            type="datetime-local"
                            required
                            value={form.allocatedFrom}
                            min={agreementStartsAt || undefined}
                            max={form.allocatedTo || agreementEndsAt || undefined}
                            onChange={(event) => {
                                setForm({ ...form, allocatedFrom: event.target.value });
                                clearVehicleAndSources();
                            }}
                        />
                        <Input
                            label="To"
                            type="datetime-local"
                            value={form.allocatedTo}
                            min={form.allocatedFrom || agreementStartsAt || undefined}
                            max={agreementEndsAt || undefined}
                            onChange={(event) => {
                                setForm({ ...form, allocatedTo: event.target.value });
                                clearVehicleAndSources();
                            }}
                        />
                        <Input
                            label="Start odometer"
                            type="number"
                            min="0"
                            step="0.000001"
                            required
                            value={form.startOdometer}
                            onChange={(event) => setForm({ ...form, startOdometer: event.target.value })}
                        />
                    </div>
                    <div className="mt-4 flex justify-end">
                        <Button
                            type="submit"
                            loading={saving}
                            disabled={!agreement || !agreementDetails || !vehicle || !form.allocatedFrom || ownershipLoading || companyOwnershipLoading || !sourceSelectionValid}
                        >
                            Create allocation
                        </Button>
                    </div>
                </form>
            </Panel>
            <div className="mt-5">
                {result.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={result.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                    />
                )}
                <Pagination meta={result.data?.meta} onPageChange={setPage} />
            </div>
        </RentalPage>
    );
}
