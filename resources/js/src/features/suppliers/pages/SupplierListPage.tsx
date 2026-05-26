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
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { getStatusTone, parsePositiveInteger } from '../../shared/utils';
import { useDeleteSupplier, useSuppliers } from '../hooks';
import type { SupplierRecord } from '../types';

export function SupplierListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<SupplierRecord | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const type = searchParams.get('type') || undefined;
    const status = searchParams.get('status') || undefined;

    const suppliersQuery = useSuppliers({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        type: type as SupplierRecord['type'] | undefined,
        status: status as SupplierRecord['status'] | undefined,
        sort: '-updated_at',
    });
    const deleteMutation = useDeleteSupplier();

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
            title: 'Supplier deleted',
            description: `${target.name} has been removed from the supplier directory.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<SupplierRecord>[] = [
        {
            key: 'name',
            header: 'Supplier',
            render: (supplier) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/suppliers/${supplier.id}`}>
                        {supplier.name}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{supplier.supplier_code ?? 'No code assigned'}</p>
                </div>
            ),
        },
        { key: 'type', header: 'Type', render: (supplier) => <StatusBadge>{supplier.type === 'company' ? 'Company' : 'Individual'}</StatusBadge> },
        { key: 'status', header: 'Status', render: (supplier) => <StatusBadge tone={getStatusTone(supplier.status)}>{supplier.status}</StatusBadge> },
        { key: 'terms', header: 'Terms', render: (supplier) => <span className="text-sm text-stone-700">{supplier.payment_terms_days ? `${supplier.payment_terms_days} days` : '-'}</span> },
        { key: 'ap', header: 'AP Account', render: (supplier) => <span className="text-sm text-stone-700">{supplier.ap_account_id ?? '-'}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[13rem]',
            render: (supplier) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/suppliers/${supplier.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                    <Link to={`/suppliers/${supplier.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(supplier)} type="button" variant="secondary">
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
                    <Link to="/suppliers/new">
                        <Button>Add Supplier</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Suppliers' }]}
                description="Supplier master data now uses the same shared listing, filtering, and maintenance shell already proven in the Product and Customer flows."
                title="Supplier List"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Search suppliers, review procurement status, and move directly into detail or maintenance flows."
                    title="Supplier directory"
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
                                </Select>
                            </>
                        }
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search supplier name or code"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{suppliersQuery.data?.meta?.total ?? 0} records</div>}
                    />
                </TableToolbar>

                {suppliersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : suppliersQuery.isError ? (
                    isForbiddenError(suppliersQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={suppliersQuery.error.message} />
                    ) : (
                        <ErrorState
                            action={
                                <Button onClick={() => void suppliersQuery.refetch()} variant="secondary">
                                    Retry
                                </Button>
                            }
                            className="m-6"
                            description={suppliersQuery.error.message}
                            title="Unable to load suppliers"
                        />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/suppliers/new">
                                        <Button>Create your first supplier</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No suppliers match the current filters yet. Add a supplier or widen the search criteria."
                                title="No suppliers found"
                            />
                        }
                        footer={<TablePagination meta={suppliersQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(supplier) => supplier.id}
                        rows={suppliersQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete supplier"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete supplier"
            />
        </div>
    );
}
