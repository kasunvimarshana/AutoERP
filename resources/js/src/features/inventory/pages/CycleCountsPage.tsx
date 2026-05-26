import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { DataTable, StatusBadge, type DataTableColumn } from '../../../components/tables';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { useUsers } from '../../access/hooks';
import { useProducts } from '../../products/hooks';
import { useWarehouses, useWarehouseLocations } from '../../warehouse/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatDateTime } from '../../shared/utils';
import { CycleCountForm } from '../components/CycleCountForm';
import { cycleCountFormSchema, type CycleCountFormInput, type CycleCountFormValues } from '../schemas';
import { useCreateCycleCount, useCycleCounts, useStartCycleCount } from '../hooks';
import type { CycleCountRecord } from '../types';

export function CycleCountsPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const [startTarget, setStartTarget] = useState<CycleCountRecord | null>(null);
    const cycleCountsQuery = useCycleCounts({ tenant_id: tenantId, page: 1, per_page: 100 });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const defaultWarehouseId = warehousesQuery.data?.items[0]?.id ?? 0;
    const locationsQuery = useWarehouseLocations(defaultWarehouseId, { tenant_id: tenantId, page: 1, per_page: 100, sort: 'path:asc' }, defaultWarehouseId > 0);
    const usersQuery = useUsers({ tenant_id: tenantId, page: 1, per_page: 100 });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const createMutation = useCreateCycleCount();
    const startMutation = useStartCycleCount(startTarget?.id ?? 0, tenantId);
    const form = useForm<CycleCountFormInput, unknown, CycleCountFormValues>({
        resolver: zodResolver(cycleCountFormSchema),
        defaultValues: {
            warehouse_id: '',
            location_id: '',
            counted_by_user_id: '',
            lines: [{ product_id: 0, counted_qty: '', unit_cost: '' }],
        },
    });

    async function onSubmit(values: CycleCountFormValues) {
        setFormError(null);

        try {
            await createMutation.mutateAsync({
                tenant_id: tenantId,
                warehouse_id: values.warehouse_id,
                location_id: values.location_id ?? null,
                counted_by_user_id: values.counted_by_user_id ?? null,
                lines: values.lines.map((line) => ({
                    product_id: line.product_id,
                    counted_qty: line.counted_qty ?? null,
                    unit_cost: line.unit_cost ?? null,
                })),
            });

            showToast({ title: 'Cycle count created', description: 'The cycle count is ready to start and complete from its detail screen.', tone: 'success' });
            form.reset({ warehouse_id: '', location_id: '', counted_by_user_id: '', lines: [{ product_id: 0, counted_qty: '', unit_cost: '' }] });
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create cycle count.');
        }
    }

    async function handleStartConfirm() {
        await startMutation.mutateAsync();
        setStartTarget(null);
        showToast({ title: 'Cycle count started', description: 'The count moved into the in-progress workflow state.', tone: 'success' });
    }

    const columns: DataTableColumn<CycleCountRecord>[] = [
        {
            key: 'id',
            header: 'Cycle Count',
            render: (count) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/inventory/cycle-counts/${count.id}`}>
                        Count #{count.id}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{count.counted_at ? formatDateTime(count.counted_at) : 'Not started yet'}</p>
                </div>
            ),
        },
        { key: 'warehouse_id', header: 'Warehouse', render: (count) => <span className="text-sm text-stone-700">#{count.warehouse_id}</span> },
        { key: 'location_id', header: 'Location', render: (count) => <span className="text-sm text-stone-700">{count.location_id ? `#${count.location_id}` : 'All locations'}</span> },
        { key: 'status', header: 'Status', render: (count) => <StatusBadge>{count.status.replaceAll('_', ' ')}</StatusBadge> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[14rem]',
            render: (count) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/inventory/cycle-counts/${count.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button>
                    </Link>
                    {count.status === 'draft' ? <Button className="h-9 px-3 text-xs" onClick={() => setStartTarget(count)} type="button" variant="secondary">Start</Button> : null}
                </div>
            ),
        },
    ];

    const lookupError = warehousesQuery.error ?? locationsQuery.error ?? usersQuery.error ?? productsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory' }, { label: 'Cycle Counts' }]} description="Cycle counts are now visible as operational documents with create, start, and complete workflows connected to the backend." title="Cycle Counts" />

            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard className="p-0">
                    {cycleCountsQuery.isPending ? <LoadingState className="m-6" lines={8} /> : cycleCountsQuery.isError ? <ErrorState className="m-6" description={cycleCountsQuery.error.message} title="Unable to load cycle counts" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No cycle counts have been created yet." title="No cycle counts found" />} getRowKey={(count) => count.id} rows={cycleCountsQuery.data.items} />}
                </ContentCard>

                <ContentCard>
                    {warehousesQuery.isPending || usersQuery.isPending || productsQuery.isPending ? (
                        <LoadingState lines={8} />
                    ) : lookupError ? (
                        <ErrorState description={lookupError.message} title="Unable to load cycle count setup" />
                    ) : (
                        <CycleCountForm form={form} formError={formError} isSubmitting={createMutation.isPending} locations={locationsQuery.data?.items ?? []} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} users={usersQuery.data?.items ?? []} warehouses={warehousesQuery.data?.items ?? []} />
                    )}
                </ContentCard>
            </div>

            <ConfirmWorkflowModal confirmLabel="Start cycle count" description={startTarget ? `Start cycle count #${startTarget.id}?` : ''} isLoading={startMutation.isPending} onCancel={() => setStartTarget(null)} onConfirm={() => void handleStartConfirm()} open={Boolean(startTarget)} title="Start cycle count" />
        </div>
    );
}
