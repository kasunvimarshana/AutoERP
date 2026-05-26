import { useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, SearchFilterToolbar, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { RelatedDocumentsTabs } from '../../shared/workflow';
import { formatCurrency, formatDate, formatDateTime, formatQuantity, parsePositiveInteger } from '../../shared/utils';
import {
    useCreateWarehouseLocation,
    useDeleteWarehouseLocation,
    useUpdateWarehouseLocation,
    useWarehouse,
    useWarehouseLocations,
    useWarehouseStockLevels,
    useWarehouseStockMovements,
} from '../hooks';
import { WarehouseLocationForm } from '../components/WarehouseLocationForm';
import {
    warehouseLocationFormSchema,
    type WarehouseLocationFormInput,
    type WarehouseLocationFormValues,
} from '../schemas';
import type { WarehouseLocationRecord, WarehouseStockLevelRecord, WarehouseStockMovementRecord } from '../types';

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'locations', label: 'Locations' },
    { id: 'stock-levels', label: 'Stock Levels' },
    { id: 'stock-movements', label: 'Stock Movements' },
] as const;

type WarehouseTabId = (typeof tabs)[number]['id'];

export function WarehouseDetailPage() {
    const { showToast } = useToast();
    const { warehouseId: warehouseIdParam } = useParams();
    const warehouseId = parsePositiveInteger(warehouseIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as WarehouseTabId | null) ?? 'overview';
    const [editingLocation, setEditingLocation] = useState<WarehouseLocationRecord | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<WarehouseLocationRecord | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const [stockLevelProductFilter, setStockLevelProductFilter] = useState('');
    const [stockLevelLocationFilter, setStockLevelLocationFilter] = useState('');
    const [movementProductFilter, setMovementProductFilter] = useState('');
    const [movementTypeFilter, setMovementTypeFilter] = useState('');

    const warehouseQuery = useWarehouse(warehouseId, warehouseId > 0);
    const warehouse = warehouseQuery.data;
    const locationsQuery = useWarehouseLocations(
        warehouseId,
        { tenant_id: warehouse?.tenant_id ?? 0, per_page: 100, sort: 'path:asc' },
        Boolean(warehouse?.tenant_id),
    );
    const stockLevelsQuery = useWarehouseStockLevels(warehouseId, warehouse?.tenant_id ?? 0, 1, 100, Boolean(warehouse?.tenant_id));
    const stockMovementsQuery = useWarehouseStockMovements(
        warehouseId,
        {
            tenant_id: warehouse?.tenant_id ?? 0,
            per_page: 100,
            product_id: movementProductFilter ? Number(movementProductFilter) : undefined,
            movement_type: movementTypeFilter || undefined,
            sort: 'performed_at:desc',
        },
        Boolean(warehouse?.tenant_id),
    );

    const createLocationMutation = useCreateWarehouseLocation(warehouseId);
    const updateLocationMutation = useUpdateWarehouseLocation(warehouseId, editingLocation?.id ?? 0);
    const deleteLocationMutation = useDeleteWarehouseLocation(warehouseId);
    const locationForm = useForm<WarehouseLocationFormInput, unknown, WarehouseLocationFormValues>({
        resolver: zodResolver(warehouseLocationFormSchema),
        defaultValues: {
            parent_id: '',
            name: '',
            code: '',
            type: 'bin',
            is_active: true,
            is_pickable: true,
            is_receivable: true,
            capacity: '',
        },
    });

    useEffect(() => {
        if (!editingLocation) {
            locationForm.reset({
                parent_id: '',
                name: '',
                code: '',
                type: 'bin',
                is_active: true,
                is_pickable: true,
                is_receivable: true,
                capacity: '',
            });
            return;
        }

        locationForm.reset({
            parent_id: editingLocation.parent_id ?? '',
            name: editingLocation.name,
            code: editingLocation.code ?? '',
            type: editingLocation.type,
            is_active: editingLocation.is_active,
            is_pickable: editingLocation.is_pickable,
            is_receivable: editingLocation.is_receivable,
            capacity: editingLocation.capacity ?? '',
        });
    }, [editingLocation, locationForm]);

    async function handleLocationSubmit(values: WarehouseLocationFormValues) {
        if (!warehouse) {
            return;
        }

        setFormError(null);

        try {
            if (editingLocation) {
                await updateLocationMutation.mutateAsync({
                    tenant_id: warehouse.tenant_id,
                    parent_id: values.parent_id ?? null,
                    name: values.name,
                    code: values.code ?? null,
                    type: values.type,
                    is_active: values.is_active,
                    is_pickable: values.is_pickable,
                    is_receivable: values.is_receivable,
                    capacity: values.capacity ?? null,
                });

                showToast({
                    title: 'Location updated',
                    description: `${values.name} was updated successfully.`,
                    tone: 'success',
                });
            } else {
                await createLocationMutation.mutateAsync({
                    tenant_id: warehouse.tenant_id,
                    parent_id: values.parent_id ?? null,
                    name: values.name,
                    code: values.code ?? null,
                    type: values.type,
                    is_active: values.is_active,
                    is_pickable: values.is_pickable,
                    is_receivable: values.is_receivable,
                    capacity: values.capacity ?? null,
                });

                showToast({
                    title: 'Location created',
                    description: `${values.name} was added to the warehouse hierarchy.`,
                    tone: 'success',
                });
            }

            setEditingLocation(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, locationForm.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to save location.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteLocationMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        if (editingLocation?.id === target.id) {
            setEditingLocation(null);
        }
        showToast({
            title: 'Location deleted',
            description: `${target.name} has been removed from the warehouse hierarchy.`,
            tone: 'success',
        });
    }

    const filteredStockLevels = useMemo(() => {
        return (stockLevelsQuery.data?.items ?? []).filter((level) => {
            const matchesProduct = !stockLevelProductFilter || String(level.product_id).includes(stockLevelProductFilter);
            const matchesLocation = !stockLevelLocationFilter || String(level.location_id).includes(stockLevelLocationFilter);
            return matchesProduct && matchesLocation;
        });
    }, [stockLevelLocationFilter, stockLevelProductFilter, stockLevelsQuery.data?.items]);

    if (warehouseId <= 0) {
        return <ErrorState description="The warehouse route is missing a valid warehouse ID." title="Invalid warehouse route" />;
    }

    if (warehouseQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (warehouseQuery.isError) {
        return isForbiddenError(warehouseQuery.error) ? <ProtectedErrorState description={warehouseQuery.error.message} /> : <ErrorState description={warehouseQuery.error.message} title="Unable to load warehouse" />;
    }

    if (!warehouse) {
        return <ErrorState description="The warehouse could not be resolved from the current route." title="Warehouse not found" />;
    }

    const resolvedWarehouse = warehouse;

    const locationColumns: DataTableColumn<WarehouseLocationRecord>[] = [
        {
            key: 'name',
            header: 'Location',
            render: (location) => (
                <div style={{ marginLeft: `${location.depth * 1.1}rem` }}>
                    <p className="font-medium text-stone-950">{location.name}</p>
                    <p className="mt-1 text-xs text-stone-500">{location.path ?? location.code ?? 'No path available'}</p>
                </div>
            ),
        },
        { key: 'type', header: 'Type', render: (location) => <span className="text-sm text-stone-700">{location.type}</span> },
        { key: 'capacity', header: 'Capacity', render: (location) => <span className="text-sm text-stone-700">{formatQuantity(location.capacity)}</span> },
        {
            key: 'flags',
            header: 'Flags',
            render: (location) => (
                <div className="flex flex-wrap gap-1 text-xs text-stone-600">
                    <span>{location.is_pickable ? 'Pickable' : 'No picking'}</span>
                    <span>•</span>
                    <span>{location.is_receivable ? 'Receivable' : 'No receiving'}</span>
                </div>
            ),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[12rem]',
            render: (location) => (
                <div className="flex flex-wrap gap-2">
                    <Button className="h-9 px-3 text-xs" onClick={() => setEditingLocation(location)} type="button" variant="secondary">
                        Edit
                    </Button>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(location)} type="button" variant="secondary">
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    const stockLevelColumns: DataTableColumn<WarehouseStockLevelRecord>[] = [
        { key: 'product_id', header: 'Product', render: (level) => <span className="text-sm text-stone-700">#{level.product_id}</span> },
        { key: 'location_id', header: 'Location', render: (level) => <span className="text-sm text-stone-700">#{level.location_id}</span> },
        { key: 'on_hand', header: 'On Hand', render: (level) => <span className="font-medium text-stone-950">{formatQuantity(level.quantity_on_hand)}</span> },
        { key: 'reserved', header: 'Reserved', render: (level) => <span className="text-sm text-stone-700">{formatQuantity(level.quantity_reserved)}</span> },
        {
            key: 'available',
            header: 'Available',
            render: (level) => {
                const available = Number(level.quantity_on_hand) - Number(level.quantity_reserved);
                return <span className="font-medium text-stone-950">{formatQuantity(available)}</span>;
            },
        },
        { key: 'unit_cost', header: 'Unit Cost', render: (level) => <span className="text-sm text-stone-700">{formatCurrency(level.unit_cost)}</span> },
        { key: 'last_movement_at', header: 'Last Movement', render: (level) => <span className="text-sm text-stone-700">{formatDateTime(level.last_movement_at)}</span> },
    ];

    const stockMovementColumns: DataTableColumn<WarehouseStockMovementRecord>[] = [
        { key: 'performed_at', header: 'Date', render: (movement) => <span className="text-sm text-stone-700">{formatDateTime(movement.performed_at)}</span> },
        { key: 'product_id', header: 'Product', render: (movement) => <span className="text-sm text-stone-700">#{movement.product_id}</span> },
        { key: 'type', header: 'Type', render: (movement) => <span className="text-sm font-medium capitalize text-stone-950">{movement.movement_type.replaceAll('_', ' ')}</span> },
        { key: 'from', header: 'From', render: (movement) => <span className="text-sm text-stone-700">{movement.from_location_id ? `#${movement.from_location_id}` : '-'}</span> },
        { key: 'to', header: 'To', render: (movement) => <span className="text-sm text-stone-700">{movement.to_location_id ? `#${movement.to_location_id}` : '-'}</span> },
        { key: 'quantity', header: 'Quantity', render: (movement) => <span className="font-medium text-stone-950">{formatQuantity(movement.quantity)}</span> },
    ];

    function renderOverview() {
        return (
            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard>
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Warehouse Code</dt>
                            <dd className="mt-1 text-sm font-medium text-stone-950">{resolvedWarehouse.code ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Type</dt>
                            <dd className="mt-1 text-sm font-medium capitalize text-stone-950">{resolvedWarehouse.type}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Organization Unit</dt>
                            <dd className="mt-1 text-sm font-medium text-stone-950">{resolvedWarehouse.org_unit_id ? `#${resolvedWarehouse.org_unit_id}` : '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Address ID</dt>
                            <dd className="mt-1 text-sm font-medium text-stone-950">{resolvedWarehouse.address_id ? `#${resolvedWarehouse.address_id}` : '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Default Warehouse</dt>
                            <dd className="mt-1 text-sm font-medium text-stone-950">{resolvedWarehouse.is_default ? 'Yes' : 'No'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Updated</dt>
                            <dd className="mt-1 text-sm font-medium text-stone-950">{formatDate(resolvedWarehouse.updated_at)}</dd>
                        </div>
                    </dl>
                </ContentCard>
                <ContentCard>
                    <div className="space-y-3">
                        <div className="rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-4">
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Location Nodes</p>
                            <p className="mt-2 text-2xl font-semibold text-stone-950">{locationsQuery.data?.items.length ?? 0}</p>
                        </div>
                        <div className="rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-4">
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Tracked Stock Levels</p>
                            <p className="mt-2 text-2xl font-semibold text-stone-950">{stockLevelsQuery.data?.items.length ?? 0}</p>
                        </div>
                    </div>
                </ContentCard>
            </div>
        );
    }

    function renderLocations() {
        const error = locationsQuery.error;

        return (
            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard className="p-0">
                    {locationsQuery.isPending ? (
                        <LoadingState className="m-6" lines={6} />
                    ) : error ? (
                        isForbiddenError(error) ? <ProtectedErrorState className="m-6" description={error.message} /> : <ErrorState className="m-6" description={error.message} title="Unable to load warehouse locations" />
                    ) : (
                        <DataTable
                            columns={locationColumns}
                            emptyState={<EmptyState className="m-6" description="No locations have been created for this warehouse yet." title="No locations found" />}
                            getRowKey={(location) => location.id}
                            rows={locationsQuery.data.items}
                        />
                    )}
                </ContentCard>

                <ContentCard>
                    {locationsQuery.isPending ? (
                        <LoadingState lines={6} />
                    ) : (
                        <WarehouseLocationForm
                            form={locationForm}
                            formError={formError}
                            isSubmitting={createLocationMutation.isPending || updateLocationMutation.isPending}
                            locations={(locationsQuery.data?.items ?? []).filter((location) => location.id !== editingLocation?.id)}
                            mode={editingLocation ? 'edit' : 'create'}
                            onCancel={() => setEditingLocation(null)}
                            onSubmit={handleLocationSubmit}
                        />
                    )}
                </ContentCard>
            </div>
        );
    }

    function renderStockLevels() {
        const error = stockLevelsQuery.error;

        return (
            <ContentCard className="p-0">
                <TableToolbar description="Filter the current warehouse stock page locally by product or location without inventing unsupported backend filters." title="Stock Levels">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => setStockLevelProductFilter(event.target.value)} placeholder="Filter product ID" value={stockLevelProductFilter} />
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => setStockLevelLocationFilter(event.target.value)} placeholder="Filter location ID" value={stockLevelLocationFilter} />
                            </div>
                        }
                    />
                </TableToolbar>

                {stockLevelsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : error ? (
                    isForbiddenError(error) ? <ProtectedErrorState className="m-6" description={error.message} /> : <ErrorState className="m-6" description={error.message} title="Unable to load stock levels" />
                ) : (
                    <DataTable
                        columns={stockLevelColumns}
                        emptyState={<EmptyState className="m-6" description="No stock levels are available for this warehouse yet." title="No stock levels found" />}
                        getRowKey={(level) => level.id}
                        rows={filteredStockLevels}
                    />
                )}
            </ContentCard>
        );
    }

    function renderStockMovements() {
        const error = stockMovementsQuery.error;

        return (
            <ContentCard className="p-0">
                <TableToolbar description="Use the real warehouse movement filters exposed by the backend: product and movement type." title="Stock Movements">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => setMovementProductFilter(event.target.value)} placeholder="Product ID" value={movementProductFilter} />
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => setMovementTypeFilter(event.target.value)} placeholder="Movement type" value={movementTypeFilter} />
                            </div>
                        }
                    />
                </TableToolbar>

                {stockMovementsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : error ? (
                    isForbiddenError(error) ? <ProtectedErrorState className="m-6" description={error.message} /> : <ErrorState className="m-6" description={error.message} title="Unable to load stock movements" />
                ) : (
                    <DataTable
                        columns={stockMovementColumns}
                        emptyState={<EmptyState className="m-6" description="No stock movements match the current warehouse filters." title="No stock movements found" />}
                        getRowKey={(movement) => movement.id}
                        rows={stockMovementsQuery.data.items}
                    />
                )}
            </ContentCard>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/warehouses/${resolvedWarehouse.id}/edit`}>
                        <Button>Edit Warehouse</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Warehouse', href: '/warehouses' }, { label: resolvedWarehouse.name }]}
                description="Warehouse detail brings locations, stock levels, and stock movements into one operational workspace without changing the shared ERP shell pattern."
                title={resolvedWarehouse.name}
            />

            <RelatedDocumentsTabs activeTab={activeTab} onChange={(tabId) => setSearchParams({ tab: tabId })} tabs={tabs.map((tab) => ({ id: tab.id, label: tab.label }))} />

            {activeTab === 'overview' ? renderOverview() : null}
            {activeTab === 'locations' ? renderLocations() : null}
            {activeTab === 'stock-levels' ? renderStockLevels() : null}
            {activeTab === 'stock-movements' ? renderStockMovements() : null}

            <ConfirmModal
                confirmLabel="Delete location"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteLocationMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete warehouse location"
            />
        </div>
    );
}
