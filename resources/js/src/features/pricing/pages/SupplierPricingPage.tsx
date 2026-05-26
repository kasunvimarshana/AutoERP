import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, StatusBadge, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useSuppliers, useSupplierPriceLists } from '../../suppliers/hooks';
import type { SupplierPriceListAssignment, SupplierRecord } from '../../suppliers/types';
import { useTenant } from '../../auth/context/TenantContext';
import { usePriceLists } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate } from '../../shared/utils';

export function SupplierPricingPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const priceListsQuery = usePriceLists({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });

    const selectedSupplierId = Number(searchParams.get('supplier_id') ?? suppliersQuery.data?.items[0]?.id ?? 0);
    const selectedSupplier = suppliersQuery.data?.items.find((supplier) => supplier.id === selectedSupplierId) ?? null;
    const assignmentsQuery = useSupplierPriceLists(selectedSupplierId, tenantId, selectedSupplierId > 0);

    const priceListNames = useMemo(
        () => new Map((priceListsQuery.data?.items ?? []).map((priceList) => [priceList.id, priceList.name])),
        [priceListsQuery.data?.items],
    );

    const columns: DataTableColumn<SupplierPriceListAssignment>[] = [
        {
            key: 'price_list_id',
            header: 'Price List',
            render: (assignment) => (
                <div>
                    <p className="font-medium text-stone-950">{priceListNames.get(assignment.price_list_id) ?? `Price List #${assignment.price_list_id}`}</p>
                    <p className="mt-1 text-xs text-stone-500">Assignment #{assignment.id}</p>
                </div>
            ),
        },
        { key: 'priority', header: 'Priority', render: (assignment) => <StatusBadge>{String(assignment.priority)}</StatusBadge> },
        { key: 'updated_at', header: 'Updated', render: (assignment) => <span className="text-sm text-stone-700">{formatDate(assignment.updated_at)}</span> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Pricing' }, { label: 'Supplier Pricing' }]}
                description="Supplier-side pricing assignments are now surfaced from the existing pricing and supplier APIs so procurement users can review purchasing list coverage."
                title="Supplier Pricing"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Choose a supplier to inspect purchasing-oriented price-list assignments and priority ordering." title="Supplier assignment lookup">
                    {suppliersQuery.isPending ? (
                        <LoadingState lines={3} />
                    ) : suppliersQuery.isError ? (
                        isForbiddenError(suppliersQuery.error) ? (
                            <ProtectedErrorState description={suppliersQuery.error.message} />
                        ) : (
                            <ErrorState description={suppliersQuery.error.message} title="Unable to load suppliers" />
                        )
                    ) : suppliersQuery.data.items.length === 0 ? (
                        <EmptyState description="Supplier assignments will appear here once suppliers exist in the tenant." title="No suppliers available" />
                    ) : (
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="max-w-md flex-1">
                                <Select onChange={(event) => setSearchParams({ supplier_id: event.target.value })} value={selectedSupplierId ? String(selectedSupplierId) : ''}>
                                    {suppliersQuery.data.items.map((supplier: SupplierRecord) => (
                                        <option key={supplier.id} value={supplier.id}>
                                            {supplier.name}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div className="text-sm text-stone-500">
                                {selectedSupplier ? `${selectedSupplier.name} | ${selectedSupplier.supplier_code ?? 'No code'} | ${selectedSupplier.status}` : 'No supplier selected'}
                            </div>
                        </div>
                    )}
                </TableToolbar>
            </ContentCard>

            <ContentCard className="p-0">
                <TableToolbar
                    description={
                        selectedSupplier
                            ? `Assignments for ${selectedSupplier.name}. These rows are backed by the same supplier pricing assignment endpoint already available in the backend.`
                            : 'Choose a supplier above to inspect assignment priority and linked price lists.'
                    }
                    title="Assigned price lists"
                />

                {!selectedSupplier ? (
                    <EmptyState className="m-6" description="Select a supplier to load pricing assignments." title="No supplier selected" />
                ) : assignmentsQuery.isPending || priceListsQuery.isPending ? (
                    <LoadingState className="m-6" lines={6} />
                ) : assignmentsQuery.isError ? (
                    isForbiddenError(assignmentsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={assignmentsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={assignmentsQuery.error.message} title="Unable to load supplier pricing assignments" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No price-list assignments are configured for this supplier yet." title="No assignments found" />}
                        getRowKey={(assignment) => assignment.id}
                        rows={assignmentsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
