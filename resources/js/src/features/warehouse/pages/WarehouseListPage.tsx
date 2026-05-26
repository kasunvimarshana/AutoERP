import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
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
import { formatDate, parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import { useDeleteWarehouse, useWarehouses } from '../hooks';
import type { WarehouseRecord } from '../types';

export function WarehouseListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<WarehouseRecord | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const name = searchParams.get('name') ?? '';
    const type = searchParams.get('type') ?? '';
    const active = searchParams.get('active') ?? '';

    const warehousesQuery = useWarehouses({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: name || undefined,
        type: type || undefined,
        is_active: parseBooleanSearchParam(active),
        sort: 'name:asc',
    });
    const deleteMutation = useDeleteWarehouse();

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

            if ('name' in updates || 'type' in updates || 'active' in updates) {
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
            title: 'Warehouse deleted',
            description: `${target.name} has been removed from the warehouse master list.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<WarehouseRecord>[] = [
        {
            key: 'name',
            header: 'Warehouse',
            render: (warehouse) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/warehouses/${warehouse.id}`}>
                        {warehouse.name}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{warehouse.code ?? 'No code assigned'}</p>
                </div>
            ),
        },
        { key: 'type', header: 'Type', render: (warehouse) => <StatusBadge>{warehouse.type}</StatusBadge> },
        { key: 'default', header: 'Default', render: (warehouse) => <StatusBadge tone={warehouse.is_default ? 'success' : 'default'}>{warehouse.is_default ? 'Default' : 'Optional'}</StatusBadge> },
        { key: 'status', header: 'Status', render: (warehouse) => <StatusBadge tone={warehouse.is_active ? 'success' : 'default'}>{warehouse.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
        { key: 'updated_at', header: 'Updated', render: (warehouse) => <span className="text-sm text-stone-700">{formatDate(warehouse.updated_at)}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[14rem]',
            render: (warehouse) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/warehouses/${warehouse.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                    <Link to={`/warehouses/${warehouse.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(warehouse)} type="button" variant="secondary">
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
                    <Link to="/warehouses/new">
                        <Button>Add Warehouse</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Warehouses' }, { label: 'Warehouse List' }]}
                description="Warehouse operations now have a dedicated master-data workspace for list, create, detail, stock, and location maintenance."
                title="Warehouse List"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Review active warehouses, virtual/transit nodes, and default storage points before moving into stock and workflow operations."
                    title="Warehouses"
                >
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type}>
                                    <option value="">All types</option>
                                    <option value="standard">Standard</option>
                                    <option value="virtual">Virtual</option>
                                    <option value="transit">Transit</option>
                                    <option value="quarantine">Quarantine</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={active}>
                                    <option value="">All statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                            </div>
                        }
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ name: event.target.value || undefined })} placeholder="Search warehouse name" value={name} />}
                        trailing={<div className="text-sm text-stone-500">{warehousesQuery.data?.meta?.total ?? 0} warehouses</div>}
                    />
                </TableToolbar>

                {warehousesQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : warehousesQuery.isError ? (
                    isForbiddenError(warehousesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={warehousesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={warehousesQuery.error.message} title="Unable to load warehouses" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/warehouses/new">
                                        <Button>Create your first warehouse</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No warehouses match the current filters yet. Create a warehouse to begin operational setup."
                                title="No warehouses found"
                            />
                        }
                        footer={<TablePagination meta={warehousesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(warehouse) => warehouse.id}
                        rows={warehousesQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete warehouse"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete warehouse"
            />
        </div>
    );
}
