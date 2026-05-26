import { useEffect, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useParams } from 'react-router-dom';
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
import { useGrn, usePurchaseOrders, useUpdateGrn } from '../hooks';
import { grnFormSchema, type GrnFormInput, type GrnFormValues } from '../schemas';

function inputDate(value: string | null | undefined) {
    return value ? value.slice(0, 10) : '';
}

export function GrnEditPage() {
    const { grnId: grnIdParam } = useParams();
    const grnId = parsePositiveInteger(grnIdParam ?? null, 0);
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { user } = useAuth();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const grnQuery = useGrn(grnId, grnId > 0);
    const form = useForm<GrnFormInput, unknown, GrnFormValues>({
        resolver: zodResolver(grnFormSchema),
        defaultValues: {
            supplier_id: '',
            warehouse_id: '',
            purchase_order_id: '',
            currency_id: '',
            grn_number: '',
            received_date: '',
            exchange_rate: '',
            notes: '',
            lines: [],
        },
    });
    const selectedWarehouseId = Number(useWatch({ control: form.control, name: 'warehouse_id' }) || 0);
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const locationsQuery = useWarehouseLocations(selectedWarehouseId, { tenant_id: tenantId, page: 1, per_page: 100, is_receivable: true, sort: 'path' }, selectedWarehouseId > 0);
    const purchaseOrdersQuery = usePurchaseOrders({ tenant_id: tenantId, page: 1, per_page: 100, sort: '-updated_at' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 250, is_active: true, sort: 'name' });
    const variantsQuery = useProductVariants({ tenant_id: tenantId, page: 1, per_page: 500, is_active: true, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const updateMutation = useUpdateGrn(grnId);

    useEffect(() => {
        const grn = grnQuery.data;
        if (!grn) {
            return;
        }

        form.reset({
            supplier_id: grn.supplier_id,
            warehouse_id: grn.warehouse_id,
            purchase_order_id: grn.purchase_order_id ?? '',
            currency_id: grn.currency_id ?? '',
            grn_number: grn.grn_number,
            received_date: inputDate(grn.received_date),
            exchange_rate: grn.exchange_rate ?? '',
            notes: grn.notes ?? '',
            lines: (grn.lines ?? grn.grn_lines ?? []).map((line) => ({
                id: line.id,
                purchase_order_line_id: line.purchase_order_line_id ?? '',
                item_id: line.item_id,
                variant_id: line.variant_id ?? '',
                description: line.description ?? '',
                location_id: line.location_id,
                uom_id: line.uom_id,
                expected_qty: line.expected_qty,
                received_qty: line.received_qty,
                rejected_qty: line.rejected_qty,
                unit_price: line.unit_price,
            })),
        });
    }, [form, grnQuery.data]);

    if (grnId <= 0) {
        return <ErrorState description="The GRN route is missing a valid GRN ID." title="Invalid GRN route" />;
    }

    async function onSubmit(values: GrnFormValues) {
        const grn = grnQuery.data;
        if (!grn) {
            return;
        }

        setFormError(null);

        try {
            const updated = await updateMutation.mutateAsync({
                tenant_id: grn.tenant_id,
                supplier_id: values.supplier_id,
                warehouse_id: values.warehouse_id,
                purchase_order_id: values.purchase_order_id ?? null,
                currency_id: values.currency_id ?? null,
                grn_number: values.grn_number || grn.grn_number,
                received_date: values.received_date,
                exchange_rate: values.exchange_rate ?? null,
                notes: values.notes ?? null,
                created_by: grn.created_by ?? user?.id ?? 1,
                lines: values.lines.map((line) => ({
                    id: line.id,
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

            showToast({ title: 'GRN updated', description: `${updated.grn_number} line items were saved.`, tone: 'success' });
            navigate(`/purchase/grns/${updated.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update GRN.');
        }
    }

    const lookupError = grnQuery.error ?? suppliersQuery.error ?? warehousesQuery.error ?? locationsQuery.error ?? purchaseOrdersQuery.error ?? productsQuery.error ?? variantsQuery.error ?? unitsQuery.error;
    const isLoading = grnQuery.isPending || suppliersQuery.isPending || warehousesQuery.isPending || purchaseOrdersQuery.isPending || productsQuery.isPending || variantsQuery.isPending || unitsQuery.isPending || (selectedWarehouseId > 0 && locationsQuery.isPending);

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/grns' }, { label: 'GRNs', href: '/purchase/grns' }, { label: 'Edit GRN' }]} description="Update GRN header fields and received product line items." title={grnQuery.data?.grn_number ?? 'Edit GRN'} />
            <ContentCard>
                {isLoading ? <LoadingState lines={8} /> : lookupError ? <ErrorState description={lookupError.message} title="Unable to load GRN setup" /> : <GrnForm form={form} formError={formError} isSubmitting={updateMutation.isPending} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} purchaseOrders={purchaseOrdersQuery.data?.items ?? []} variants={variantsQuery.data?.items ?? []} suppliers={suppliersQuery.data?.items ?? []} unitsOfMeasure={unitsQuery.data?.items ?? []} warehouseLocations={locationsQuery.data?.items ?? []} warehouses={warehousesQuery.data?.items ?? []} submitLabel="Save GRN" />}
            </ContentCard>
        </div>
    );
}
