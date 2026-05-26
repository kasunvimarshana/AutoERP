import { useEffect, useMemo, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useAuth } from '../../auth/context/AuthContext';
import { useTenant } from '../../auth/context/TenantContext';
import { useProductVariants, useProducts, useUnitsOfMeasure } from '../../products/hooks';
import { useSuppliers } from '../../suppliers/hooks';
import { parsePositiveInteger } from '../../shared/utils';
import { useWarehouseLocations, useWarehouses } from '../../warehouse/hooks';
import { GrnForm } from '../components/GrnForm';
import { useCreateGrn, usePurchaseOrder, usePurchaseOrders } from '../hooks';
import { grnFormSchema, type GrnFormInput, type GrnFormValues } from '../schemas';

function defaultValues(purchaseOrderId: number): GrnFormInput {
    return {
        supplier_id: '',
        warehouse_id: '',
        purchase_order_id: purchaseOrderId || '',
        currency_id: '',
        grn_number: '',
        received_date: new Date().toISOString().slice(0, 10),
        exchange_rate: '',
        notes: '',
        lines: [
            {
                id: '',
                purchase_order_line_id: '',
                item_id: '',
                variant_id: '',
                description: '',
                location_id: '',
                uom_id: '',
                expected_qty: '0',
                received_qty: '1',
                rejected_qty: '0',
                unit_price: '0',
            },
        ],
    };
}

export function GrnCreatePage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const initialPurchaseOrderId = parsePositiveInteger(searchParams.get('purchaseOrderId') ?? searchParams.get('poId'), 0);
    const { showToast } = useToast();
    const { user } = useAuth();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const [loadedPurchaseOrderId, setLoadedPurchaseOrderId] = useState<number | null>(null);
    const form = useForm<GrnFormInput, unknown, GrnFormValues>({
        resolver: zodResolver(grnFormSchema),
        defaultValues: defaultValues(initialPurchaseOrderId),
    });
    const selectedPurchaseOrderId = Number(useWatch({ control: form.control, name: 'purchase_order_id' }) || 0);
    const selectedWarehouseId = Number(useWatch({ control: form.control, name: 'warehouse_id' }) || 0);
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const locationsQuery = useWarehouseLocations(selectedWarehouseId, { tenant_id: tenantId, page: 1, per_page: 100, is_receivable: true, sort: 'path' }, selectedWarehouseId > 0);
    const purchaseOrdersQuery = usePurchaseOrders({ tenant_id: tenantId, page: 1, per_page: 100, sort: '-updated_at' });
    const selectedPurchaseOrderQuery = usePurchaseOrder(selectedPurchaseOrderId, selectedPurchaseOrderId > 0);
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 250, is_active: true, sort: 'name' });
    const variantsQuery = useProductVariants({ tenant_id: tenantId, page: 1, per_page: 500, is_active: true, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const createMutation = useCreateGrn();
    const defaultLocationId = useMemo(() => locationsQuery.data?.items[0]?.id ?? '', [locationsQuery.data?.items]);

    useEffect(() => {
        const order = selectedPurchaseOrderQuery.data;
        if (!order || loadedPurchaseOrderId === order.id) {
            return;
        }

        form.setValue('supplier_id', order.supplier_id, { shouldDirty: true, shouldValidate: true });
        form.setValue('warehouse_id', order.warehouse_id, { shouldDirty: true, shouldValidate: true });
        form.setValue('currency_id', order.currency_id, { shouldDirty: true, shouldValidate: true });
        form.setValue('exchange_rate', order.exchange_rate ?? '', { shouldDirty: true });
        form.setValue(
            'lines',
            (order.lines ?? []).map((line) => ({
                id: '',
                purchase_order_line_id: line.id,
                item_id: line.item_id,
                variant_id: line.variant_id ?? '',
                description: line.description ?? '',
                location_id: defaultLocationId,
                uom_id: line.uom_id,
                expected_qty: line.ordered_qty,
                received_qty: Number(line.ordered_qty) - Number(line.received_qty ?? 0) > 0 ? Number(line.ordered_qty) - Number(line.received_qty ?? 0) : line.ordered_qty,
                rejected_qty: '0',
                unit_price: line.unit_price,
            })),
            { shouldDirty: true, shouldValidate: true },
        );
        setLoadedPurchaseOrderId(order.id);
    }, [defaultLocationId, form, loadedPurchaseOrderId, selectedPurchaseOrderQuery.data]);

    useEffect(() => {
        if (!defaultLocationId) {
            return;
        }

        const lines = form.getValues('lines') ?? [];
        lines.forEach((line, index) => {
            if (!line.location_id) {
                form.setValue(`lines.${index}.location_id`, defaultLocationId, { shouldDirty: true, shouldValidate: true });
            }
        });
    }, [defaultLocationId, form]);

    async function onSubmit(values: GrnFormValues) {
        setFormError(null);

        try {
            const grn = await createMutation.mutateAsync({
                tenant_id: tenantId,
                supplier_id: values.supplier_id,
                warehouse_id: values.warehouse_id,
                purchase_order_id: values.purchase_order_id ?? null,
                currency_id: values.currency_id,
                grn_number: values.grn_number,
                received_date: values.received_date,
                exchange_rate: values.exchange_rate ?? null,
                notes: values.notes ?? null,
                created_by: user?.id ?? 1,
                lines: values.lines.map((line) => ({
                    purchase_order_line_id: line.purchase_order_line_id ?? null,
                    item_id: line.item_id,
                    variant_id: line.variant_id ?? null,
                    warehouse_id: values.warehouse_id,
                    location_id: line.location_id,
                    uom_id: line.uom_id,
                    expected_qty: line.expected_qty ?? 0,
                    received_qty: line.received_qty,
                    rejected_qty: line.rejected_qty ?? 0,
                    unit_price: line.unit_price,
                })),
            });

            showToast({ title: 'GRN created', description: `${grn.grn_number} is ready for posting.`, tone: 'success' });
            navigate(`/purchase/grns/${grn.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create GRN.');
        }
    }

    const lookupError = suppliersQuery.error ?? warehousesQuery.error ?? locationsQuery.error ?? purchaseOrdersQuery.error ?? selectedPurchaseOrderQuery.error ?? productsQuery.error ?? variantsQuery.error ?? unitsQuery.error;
    const isLoading = suppliersQuery.isPending || warehousesQuery.isPending || purchaseOrdersQuery.isPending || productsQuery.isPending || variantsQuery.isPending || unitsQuery.isPending || (selectedWarehouseId > 0 && locationsQuery.isPending) || (selectedPurchaseOrderId > 0 && selectedPurchaseOrderQuery.isPending);

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/grns' }, { label: 'Add GRN' }]} description="Create a goods receipt note with received product line items." title="Add GRN" />
            <ContentCard>
                {isLoading ? <LoadingState lines={8} /> : lookupError ? <ErrorState description={lookupError.message} title="Unable to load GRN setup" /> : <GrnForm form={form} formError={formError} isSubmitting={createMutation.isPending} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} purchaseOrders={purchaseOrdersQuery.data?.items ?? []} variants={variantsQuery.data?.items ?? []} suppliers={suppliersQuery.data?.items ?? []} unitsOfMeasure={unitsQuery.data?.items ?? []} warehouseLocations={locationsQuery.data?.items ?? []} warehouses={warehousesQuery.data?.items ?? []} />}
            </ContentCard>
        </div>
    );
}
