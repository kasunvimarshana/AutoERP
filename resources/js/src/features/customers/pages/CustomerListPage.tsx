import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { useDeleteCustomer, useCustomers } from '../hooks';
import { getStatusTone, parsePositiveInteger } from '../../shared/utils';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import type { CustomerRecord } from '../types';

export function CustomerListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<CustomerRecord | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const type = searchParams.get('type') || undefined;
    const status = searchParams.get('status') || undefined;

    const customersQuery = useCustomers({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        type: type as CustomerRecord['type'] | undefined,
        status: status as CustomerRecord['status'] | undefined,
        sort: '-updated_at',
    });
    const deleteMutation = useDeleteCustomer();

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('search' in updates || 'type' in updates || 'status' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        showToast({
            title: 'Customer deleted',
            description: `${target.name} has been removed from the customer directory.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<CustomerRecord>[] = [
        {
            key: 'name',
            header: 'Customer',
            render: (customer) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/customers/${customer.id}`}>
                        {customer.name}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{customer.customer_code ?? 'No code assigned'}</p>
                </div>
            ),
        },
        {
            key: 'type',
            header: 'Type',
            render: (customer) => <StatusBadge>{customer.type === 'company' ? 'Company' : 'Individual'}</StatusBadge>,
        },
        {
            key: 'status',
            header: 'Status',
            render: (customer) => <StatusBadge tone={getStatusTone(customer.status)}>{customer.status}</StatusBadge>,
        },
        {
            key: 'credit_limit',
            header: 'Credit Limit',
            render: (customer) => <span className="text-sm text-stone-700">{customer.credit_limit ?? '-'}</span>,
        },
        {
            key: 'payment_terms_days',
            header: 'Terms',
            render: (customer) => <span className="text-sm text-stone-700">{customer.payment_terms_days ? `${customer.payment_terms_days} days` : '-'}</span>,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[13rem]',
            render: (customer) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/customers/${customer.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                    <Link to={`/customers/${customer.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(customer)} type="button" variant="secondary">
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to="/customers/new">
                        <Button>Add Customer</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Customers' }]}
                description="Customer master data now follows the same shared list, filter, and maintenance pattern already established in the Product module."
                title="Customer List"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Search customer accounts, review commercial standing, and move directly into profile or maintenance flows."
                    title="Customer directory"
                >
                    <SearchFilterToolbar
                        filters={
                            <>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type ?? ''}>
                                    <option value="">All types</option>
                                    <option value="company">Company</option>
                                    <option value="individual">Individual</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status ?? ''}>
                                    <option value="">All statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="blocked">Blocked</option>
                                </Select>
                            </>
                        }
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search customer name or code"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{customersQuery.data?.meta?.total ?? 0} records</div>}
                    />
                </TableToolbar>

                {customersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : customersQuery.isError ? (
                    isForbiddenError(customersQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={customersQuery.error.message} />
                    ) : (
                        <ErrorState
                            action={
                                <Button onClick={() => void customersQuery.refetch()} variant="secondary">
                                    Retry
                                </Button>
                            }
                            className="m-6"
                            description={customersQuery.error.message}
                            title="Unable to load customers"
                        />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/customers/new">
                                        <Button>Create your first customer</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No customers match the current filters yet. Add an account or widen the search criteria."
                                title="No customers found"
                            />
                        }
                        footer={<TablePagination meta={customersQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(customer) => customer.id}
                        rows={customersQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete customer"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete customer"
            />
        </div>
    );
}
