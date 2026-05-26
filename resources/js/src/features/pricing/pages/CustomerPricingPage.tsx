import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, StatusBadge, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useCustomers, useCustomerPriceLists } from '../../customers/hooks';
import type { CustomerPriceListAssignment, CustomerRecord } from '../../customers/types';
import { useTenant } from '../../auth/context/TenantContext';
import { usePriceLists } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate } from '../../shared/utils';

export function CustomerPricingPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const priceListsQuery = usePriceLists({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });

    const selectedCustomerId = Number(searchParams.get('customer_id') ?? customersQuery.data?.items[0]?.id ?? 0);
    const selectedCustomer = customersQuery.data?.items.find((customer) => customer.id === selectedCustomerId) ?? null;
    const assignmentsQuery = useCustomerPriceLists(selectedCustomerId, tenantId, selectedCustomerId > 0);

    const priceListNames = useMemo(
        () => new Map((priceListsQuery.data?.items ?? []).map((priceList) => [priceList.id, priceList.name])),
        [priceListsQuery.data?.items],
    );

    const columns: DataTableColumn<CustomerPriceListAssignment>[] = [
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
                breadcrumbs={[{ label: 'Pricing' }, { label: 'Customer Pricing' }]}
                description="Customer-specific pricing assignments now resolve against the existing pricing endpoints and the Phase 3 customer directory."
                title="Customer Pricing"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Select a customer to inspect the active price-list assignments the pricing module currently exposes." title="Customer assignment lookup">
                    {customersQuery.isPending ? (
                        <LoadingState lines={3} />
                    ) : customersQuery.isError ? (
                        isForbiddenError(customersQuery.error) ? (
                            <ProtectedErrorState description={customersQuery.error.message} />
                        ) : (
                            <ErrorState description={customersQuery.error.message} title="Unable to load customers" />
                        )
                    ) : customersQuery.data.items.length === 0 ? (
                        <EmptyState description="Customer assignments will appear here once customers exist in the tenant." title="No customers available" />
                    ) : (
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="max-w-md flex-1">
                                <Select onChange={(event) => setSearchParams({ customer_id: event.target.value })} value={selectedCustomerId ? String(selectedCustomerId) : ''}>
                                    {customersQuery.data.items.map((customer: CustomerRecord) => (
                                        <option key={customer.id} value={customer.id}>
                                            {customer.name}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div className="text-sm text-stone-500">
                                {selectedCustomer ? `${selectedCustomer.name} | ${selectedCustomer.customer_code ?? 'No code'} | ${selectedCustomer.status}` : 'No customer selected'}
                            </div>
                        </div>
                    )}
                </TableToolbar>
            </ContentCard>

            <ContentCard className="p-0">
                <TableToolbar
                    description={
                        selectedCustomer
                            ? `Assignments for ${selectedCustomer.name}. These rows come from the same customer pricing API already used inside the customer detail workspace.`
                            : 'Choose a customer above to inspect assignment priority and linked price lists.'
                    }
                    title="Assigned price lists"
                />

                {!selectedCustomer ? (
                    <EmptyState className="m-6" description="Select a customer to load pricing assignments." title="No customer selected" />
                ) : assignmentsQuery.isPending || priceListsQuery.isPending ? (
                    <LoadingState className="m-6" lines={6} />
                ) : assignmentsQuery.isError ? (
                    isForbiddenError(assignmentsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={assignmentsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={assignmentsQuery.error.message} title="Unable to load customer pricing assignments" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No price-list assignments are configured for this customer yet." title="No assignments found" />}
                        getRowKey={(assignment) => assignment.id}
                        rows={assignmentsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
