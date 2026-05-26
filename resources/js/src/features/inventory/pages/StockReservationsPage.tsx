import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { useProducts } from '../../products/hooks';
import { useWarehouses, useWarehouseLocations } from '../../warehouse/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatDateTime, formatQuantity } from '../../shared/utils';
import { StockReservationForm } from '../components/StockReservationForm';
import { stockReservationFormSchema, type StockReservationFormInput, type StockReservationFormValues } from '../schemas';
import { useCreateStockReservation, useDeleteStockReservation, useReleaseExpiredReservations, useStockReservations } from '../hooks';
import type { StockReservationRecord } from '../types';

export function StockReservationsPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const [releaseExpiredOpen, setReleaseExpiredOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<StockReservationRecord | null>(null);
    const reservationsQuery = useStockReservations({ tenant_id: tenantId, page: 1, per_page: 100 });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const selectedWarehouseId = warehousesQuery.data?.items[0]?.id ?? 0;
    const locationsQuery = useWarehouseLocations(selectedWarehouseId, { tenant_id: tenantId, page: 1, per_page: 100, sort: 'path:asc' }, selectedWarehouseId > 0);
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const createMutation = useCreateStockReservation();
    const deleteMutation = useDeleteStockReservation(tenantId);
    const releaseExpiredMutation = useReleaseExpiredReservations();
    const form = useForm<StockReservationFormInput, unknown, StockReservationFormValues>({
        resolver: zodResolver(stockReservationFormSchema),
        defaultValues: { product_id: '', location_id: '', quantity: '', reserved_for_type: '', reserved_for_id: '', expires_at: '' },
    });

    async function onSubmit(values: StockReservationFormValues) {
        setFormError(null);

        try {
            await createMutation.mutateAsync({
                tenant_id: tenantId,
                product_id: values.product_id,
                location_id: values.location_id,
                quantity: values.quantity,
                reserved_for_type: values.reserved_for_type ?? null,
                reserved_for_id: values.reserved_for_id ?? null,
                expires_at: values.expires_at ?? null,
            });

            form.reset({ product_id: '', location_id: '', quantity: '', reserved_for_type: '', reserved_for_id: '', expires_at: '' });
            showToast({ title: 'Reservation created', description: 'The stock reservation is now active in inventory control.', tone: 'success' });
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create reservation.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        await deleteMutation.mutateAsync(deleteTarget.id);
        setDeleteTarget(null);
        showToast({ title: 'Reservation released', description: 'The selected stock reservation was removed successfully.', tone: 'success' });
    }

    async function handleReleaseExpiredConfirm() {
        const result = await releaseExpiredMutation.mutateAsync({ tenant_id: tenantId });
        setReleaseExpiredOpen(false);
        showToast({ title: 'Expired reservations released', description: `${result.released_count} expired reservations were released.`, tone: 'success' });
    }

    const columns: DataTableColumn<StockReservationRecord>[] = [
        { key: 'product_id', header: 'Product', render: (row) => <span className="font-medium text-stone-950">#{row.product_id}</span> },
        { key: 'location_id', header: 'Location', render: (row) => <span className="text-sm text-stone-700">#{row.location_id}</span> },
        { key: 'quantity', header: 'Quantity', render: (row) => <span className="text-sm text-stone-700">{formatQuantity(row.quantity)}</span> },
        { key: 'reserved_for', header: 'Reserved For', render: (row) => <span className="text-sm text-stone-700">{row.reserved_for_type && row.reserved_for_id ? `${row.reserved_for_type} #${row.reserved_for_id}` : '-'}</span> },
        { key: 'expires_at', header: 'Expires At', render: (row) => <span className="text-sm text-stone-700">{formatDateTime(row.expires_at)}</span> },
        { key: 'actions', header: 'Actions', render: (row) => <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(row)} type="button" variant="secondary">Delete</Button> },
    ];

    const lookupError = warehousesQuery.error ?? locationsQuery.error ?? productsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={<Button onClick={() => setReleaseExpiredOpen(true)} type="button" variant="secondary">Release Expired</Button>}
                breadcrumbs={[{ label: 'Inventory' }, { label: 'Stock Reservations' }]}
                description="Reservations protect available stock for downstream workflows and support safe release of expired holds through a confirmation step."
                title="Stock Reservations"
            />

            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard className="p-0">
                    {reservationsQuery.isPending ? <LoadingState className="m-6" lines={8} /> : reservationsQuery.isError ? <ErrorState className="m-6" description={reservationsQuery.error.message} title="Unable to load reservations" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No stock reservations exist yet." title="No reservations found" />} getRowKey={(row) => row.id} rows={reservationsQuery.data.items} />}
                </ContentCard>

                <ContentCard>
                    {warehousesQuery.isPending || productsQuery.isPending ? (
                        <LoadingState lines={8} />
                    ) : lookupError ? (
                        <ErrorState description={lookupError.message} title="Unable to load reservation setup" />
                    ) : (
                        <StockReservationForm form={form} formError={formError} isSubmitting={createMutation.isPending} locations={locationsQuery.data?.items ?? []} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} />
                    )}
                </ContentCard>
            </div>

            <ConfirmWorkflowModal confirmLabel="Release expired reservations" description="Release all expired stock reservations using the backend release-expired action?" isLoading={releaseExpiredMutation.isPending} onCancel={() => setReleaseExpiredOpen(false)} onConfirm={() => void handleReleaseExpiredConfirm()} open={releaseExpiredOpen} title="Release expired reservations" />
            <ConfirmWorkflowModal confirmLabel="Delete reservation" description={deleteTarget ? `Delete reservation #${deleteTarget.id}?` : ''} isLoading={deleteMutation.isPending} onCancel={() => setDeleteTarget(null)} onConfirm={() => void handleDeleteConfirm()} open={Boolean(deleteTarget)} title="Delete reservation" />
        </div>
    );
}
