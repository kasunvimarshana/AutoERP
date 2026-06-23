import { useEffect, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { getVehicle } from '@/modules/vehicle/vehicleApi';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
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
import {
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
import type { RentalAllocation } from '../vehicleRentalTypes';

interface AllocationForm {
    vehicleSourceType: string;
    allocatedFrom: string;
    allocatedTo: string;
    startOdometer: string;
}

const emptyForm = (): AllocationForm => ({
    vehicleSourceType: 'company_owned',
    allocatedFrom: '',
    allocatedTo: '',
    startOdometer: '0',
});

export default function RentalAllocationPage() {
    const [params] = useSearchParams();
    const initialAgreementId = parsePositiveInteger(params.get('agreement_id'));
    const initialVehicleId = parsePositiveInteger(params.get('vehicle_id'));
    const [agreement, setAgreement] = useState<NamedResource | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [sourceAllocation, setSourceAllocation] = useState<NamedResource | null>(null);
    const [financeAgreement, setFinanceAgreement] = useState<NamedResource | null>(null);
    const [form, setForm] = useState<AllocationForm>(emptyForm);
    const [page, setPage] = useState(1);
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!initialAgreementId && !initialVehicleId) return;

        const controller = new AbortController();
        setError(null);

        void Promise.all([
            initialAgreementId
                ? getRentalAgreement(initialAgreementId, controller.signal).then((resource) => {
                    setAgreement({
                        id: resource.id,
                        code: resource.agreement_number,
                        name: [
                            resource.agreement_number,
                            resource.customer?.name ?? resource.supplier?.name,
                        ].filter(Boolean).join(' - '),
                    });
                })
                : Promise.resolve(),
            initialVehicleId
                ? getVehicle(initialVehicleId, controller.signal).then(setVehicle)
                : Promise.resolve(),
        ]).catch((requestError: unknown) => {
            if (!controller.signal.aborted) setError(toApiError(requestError));
        });

        return () => controller.abort();
    }, [initialAgreementId, initialVehicleId]);

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
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!agreement || !vehicle) return;

        setSaving(true);
        setError(null);
        try {
            await createRentalAllocation(agreement.id, {
                vehicle_id: vehicle.id,
                vehicle_source_type: form.vehicleSourceType,
                source_allocation_id: form.vehicleSourceType === 'owner_supplied'
                    ? sourceAllocation?.id ?? null
                    : null,
                vehicle_finance_agreement_id: form.vehicleSourceType === 'financed'
                    ? financeAgreement?.id ?? null
                    : null,
                allocated_from: form.allocatedFrom,
                allocated_to: form.allocatedTo || null,
                start_odometer: form.startOdometer,
            });
            setVehicle(null);
            setSourceAllocation(null);
            setFinanceAgreement(null);
            setForm(emptyForm());
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
            render: (row) => `${row.allocated_from.slice(0, 10)} – ${row.allocated_to?.slice(0, 10) ?? 'open'}`,
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

    const sourceSelectionValid = form.vehicleSourceType === 'owner_supplied'
        ? sourceAllocation !== null
        : form.vehicleSourceType === 'financed'
            ? financeAgreement !== null
            : true;

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
                                setPage(1);
                            }}
                            required
                        />
                        <VehicleLookupSelect value={vehicle} onChange={setVehicle} required />
                        <Select
                            label="Vehicle source"
                            value={form.vehicleSourceType}
                            onChange={(event) => changeSourceType(event.target.value)}
                            options={[
                                { value: 'company_owned', label: 'Company owned' },
                                { value: 'owner_supplied', label: 'Owner supplied' },
                                { value: 'financed', label: 'Financed' },
                            ]}
                        />
                        {form.vehicleSourceType === 'owner_supplied' && (
                            <RentalAllocationLookupSelect
                                value={sourceAllocation}
                                onChange={setSourceAllocation}
                                required
                                excludeId={null}
                            />
                        )}
                        {form.vehicleSourceType === 'financed' && (
                            <RentalFinanceAgreementLookupSelect
                                value={financeAgreement}
                                onChange={setFinanceAgreement}
                                required
                            />
                        )}
                        <Input
                            label="From"
                            type="datetime-local"
                            required
                            value={form.allocatedFrom}
                            onChange={(event) => setForm({ ...form, allocatedFrom: event.target.value })}
                        />
                        <Input
                            label="To"
                            type="datetime-local"
                            value={form.allocatedTo}
                            onChange={(event) => setForm({ ...form, allocatedTo: event.target.value })}
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
                            disabled={!agreement || !vehicle || !form.allocatedFrom || !sourceSelectionValid}
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
