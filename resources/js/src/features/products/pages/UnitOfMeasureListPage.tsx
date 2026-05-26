import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { useDeleteUnitOfMeasure, useUnitsOfMeasure } from '../hooks';
import type { UnitOfMeasure, UnitOfMeasureType } from '../types';
import { formatDate, parsePositiveInteger } from '../utils';

const unitTypeOptions: UnitOfMeasureType[] = ['unit', 'mass', 'volume', 'length', 'time', 'other'];

export function UnitOfMeasureListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<UnitOfMeasure | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const typeParam = searchParams.get('type');
    const type = unitTypeOptions.includes(typeParam as UnitOfMeasureType) ? (typeParam as UnitOfMeasureType) : undefined;
    const unitsQuery = useUnitsOfMeasure({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        type,
        sort: 'name',
    });
    const deleteMutation = useDeleteUnitOfMeasure();

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

            if ('search' in updates || 'type' in updates) {
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
            title: 'Unit deleted',
            description: `${target.name} was removed from the units list.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<UnitOfMeasure>[] = [
        {
            key: 'name',
            header: 'Unit',
            render: (unit) => (
                <div>
                    <p className="font-medium text-stone-950">{unit.name}</p>
                    <p className="mt-1 text-xs text-stone-500">{unit.symbol}</p>
                </div>
            ),
        },
        {
            key: 'type',
            header: 'Type',
            render: (unit) => <StatusBadge>{unit.type}</StatusBadge>,
        },
        {
            key: 'base',
            header: 'Base Unit',
            render: (unit) => <StatusBadge tone={unit.is_base ? 'success' : 'default'}>{unit.is_base ? 'Base' : 'Derived'}</StatusBadge>,
        },
        {
            key: 'updated',
            header: 'Updated',
            render: (unit) => formatDate(unit.updated_at),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[11rem]',
            render: (unit) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/products/units/${unit.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(unit)} type="button" variant="secondary">
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
                    <div className="flex flex-wrap gap-2">
                        <Link to="/products/units/conversions">
                            <Button variant="secondary">Conversions</Button>
                        </Link>
                        <Link to="/products/units/new">
                            <Button>Add UOM</Button>
                        </Link>
                    </div>
                }
                breadcrumbs={[{ label: 'Products', href: '/products' }, { label: 'Units of Measure' }]}
                description="Units of measure now connect directly to a dedicated conversion workspace, keeping the Product module ready for purchasing, inventory, and sales quantity flows."
                title="Units of Measure"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Manage base and derived units with the same consistent list, filter, and row action pattern." title="Units of measure">
                    <SearchFilterToolbar
                        filters={
                            <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type ?? ''}>
                                <option value="">All types</option>
                                <option value="unit">Unit</option>
                                <option value="mass">Mass</option>
                                <option value="volume">Volume</option>
                                <option value="length">Length</option>
                                <option value="time">Time</option>
                                <option value="other">Other</option>
                            </Select>
                        }
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search unit name"
                                value={search}
                            />
                        }
                    />
                </TableToolbar>

                {unitsQuery.isPending ? (
                    <LoadingState className="m-6" lines={7} />
                ) : unitsQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => void unitsQuery.refetch()} variant="secondary">
                                Retry
                            </Button>
                        }
                        className="m-6"
                        description={unitsQuery.error.message}
                        title="Unable to load units of measure"
                    />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/products/units/new">
                                        <Button>Create UOM</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No units of measure match the current filters yet."
                                title="No units found"
                            />
                        }
                        footer={<TablePagination meta={unitsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(unit) => unit.id}
                        rows={unitsQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete UOM"
                description={deleteTarget ? `Delete ${deleteTarget.name}?` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete unit of measure"
            />
        </div>
    );
}
