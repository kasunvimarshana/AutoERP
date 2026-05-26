import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
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
import { useTaxGroups } from '../../tax/hooks';
import { useWarehouses } from '../../warehouse/hooks';
import { PurchaseOrderForm } from '../components/PurchaseOrderForm';
import { usePurchaseOrder, useUpdatePurchaseOrder } from '../hooks';
import { purchaseOrderFormSchema, type PurchaseOrderFormInput, type PurchaseOrderFormValues } from '../schemas';
import { calculateLineAmounts, calculateOrderTotals } from './PurchaseOrderCreatePage';

function inputDate(value: string | null | undefined) {
    return value ? value.slice(0, 10) : '';
}

function blankDefaults(): PurchaseOrderFormInput {
    return {
        supplier_id: '',
        warehouse_id: '',
        currency_id: '',
        po_number: '',
        order_date: '',
        expected_date: '',
        organization_unit_id: '',
        exchange_rate: '',
        header_discount_type: '',
        header_discount_value: '',
        header_tax_group_id: '',
        notes: '',
        lines: [],
    };
}

export function PurchaseOrderEditPage() {
    const { purchaseOrderId: purchaseOrderIdParam } = useParams();
    const purchaseOrderId = parsePositiveInteger(purchaseOrderIdParam ?? null, 0);
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { user } = useAuth();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const purchaseOrderQuery = usePurchaseOrder(purchaseOrderId, purchaseOrderId > 0);
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 250, is_active: true, sort: 'name' });
    const variantsQuery = useProductVariants({ tenant_id: tenantId, page: 1, per_page: 500, is_active: true, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const taxGroupsQuery = useTaxGroups({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const updateMutation = useUpdatePurchaseOrder(purchaseOrderId);
    const form = useForm<PurchaseOrderFormInput, unknown, PurchaseOrderFormValues>({
        resolver: zodResolver(purchaseOrderFormSchema),
        defaultValues: blankDefaults(),
    });

    useEffect(() => {
        const purchaseOrder = purchaseOrderQuery.data;
        if (!purchaseOrder) {
            return;
        }

        form.reset({
            supplier_id: purchaseOrder.supplier_id,
            warehouse_id: purchaseOrder.warehouse_id,
            currency_id: purchaseOrder.currency_id ?? '',
            po_number: purchaseOrder.po_number,
            order_date: inputDate(purchaseOrder.order_date),
            expected_date: inputDate(purchaseOrder.expected_date),
            organization_unit_id: purchaseOrder.organization_unit_id ?? '',
            exchange_rate: purchaseOrder.exchange_rate ?? '',
            header_discount_type: purchaseOrder.header_discount_type ?? '',
            header_discount_value: purchaseOrder.header_discount_value ?? '',
            header_tax_group_id: purchaseOrder.header_tax_group_id ?? '',
            notes: purchaseOrder.notes ?? '',
            lines: (purchaseOrder.lines ?? purchaseOrder.purchase_order_lines ?? []).map((line) => ({
                id: line.id,
                item_id: line.item_id,
                variant_id: line.variant_id ?? '',
                description: line.description ?? '',
                uom_id: line.uom_id,
                ordered_qty: line.ordered_qty,
                received_qty: line.received_qty,
                rejected_qty: line.rejected_qty,
                invoiced_qty: line.invoiced_qty,
                unit_price: line.unit_price,
                discount_type: line.discount_type ?? '',
                discount_value: line.discount_value,
                tax_group_id: line.tax_group_id ?? '',
                tax_amount: line.tax_amount,
                account_id: line.account_id ?? '',
            })),
        });
    }, [form, purchaseOrderQuery.data]);

    if (purchaseOrderId <= 0) {
        return <ErrorState description="The purchase order route is missing a valid purchase order ID." title="Invalid purchase order route" />;
    }

    async function onSubmit(values: PurchaseOrderFormValues) {
        const purchaseOrder = purchaseOrderQuery.data;
        if (!purchaseOrder) {
            return;
        }

        setFormError(null);

        try {
            const totals = calculateOrderTotals(values);
            const updated = await updateMutation.mutateAsync({
                tenant_id: purchaseOrder.tenant_id,
                supplier_id: values.supplier_id,
                warehouse_id: values.warehouse_id,
                currency_id: values.currency_id ?? null,
                po_number: values.po_number || purchaseOrder.po_number,
                order_date: values.order_date,
                expected_date: values.expected_date ?? null,
                organization_unit_id: values.organization_unit_id ?? null,
                exchange_rate: values.exchange_rate ?? null,
                header_discount_type: values.header_discount_type || null,
                header_discount_value: values.header_discount_value ?? null,
                header_discount_amount: totals.header_discount_amount,
                header_tax_group_id: values.header_tax_group_id ?? null,
                header_tax_amount: 0,
                subtotal: totals.subtotal,
                line_tax_total: totals.line_tax_total,
                line_discount_total: totals.line_discount_total,
                tax_total: totals.tax_total,
                discount_total: totals.discount_total,
                grand_total: totals.grand_total,
                paid_amount: Number(purchaseOrder.paid_amount ?? 0),
                balance: Math.max(totals.grand_total - Number(purchaseOrder.paid_amount ?? 0), 0),
                notes: values.notes ?? null,
                created_by: purchaseOrder.created_by ?? user?.id ?? 1,
                lines: values.lines.map((line) => ({
                    id: line.id,
                    item_id: line.item_id,
                    variant_id: line.variant_id ?? null,
                    description: line.description ?? null,
                    uom_id: line.uom_id,
                    ordered_qty: line.ordered_qty,
                    received_qty: line.received_qty ?? 0,
                    rejected_qty: line.rejected_qty ?? 0,
                    invoiced_qty: line.invoiced_qty ?? 0,
                    unit_price: line.unit_price,
                    discount_type: line.discount_type || null,
                    discount_value: line.discount_value ?? 0,
                    ...calculateLineAmounts(line),
                    tax_group_id: line.tax_group_id ?? null,
                    tax_amount: line.tax_amount ?? 0,
                    account_id: line.account_id ?? null,
                })),
            });

            showToast({ title: 'Purchase order updated', description: `${updated.po_number} line items were saved.`, tone: 'success' });
            navigate(`/purchase/orders/${updated.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update purchase order.');
        }
    }

    const lookupError = purchaseOrderQuery.error ?? suppliersQuery.error ?? warehousesQuery.error ?? productsQuery.error ?? variantsQuery.error ?? unitsQuery.error ?? taxGroupsQuery.error;
    const isLoading = purchaseOrderQuery.isPending || suppliersQuery.isPending || warehousesQuery.isPending || productsQuery.isPending || variantsQuery.isPending || unitsQuery.isPending || taxGroupsQuery.isPending;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/orders' }, { label: 'Purchase Orders', href: '/purchase/orders' }, { label: 'Edit Purchase Order' }]} description="Update purchase order header fields and product line items." title={purchaseOrderQuery.data?.po_number ?? 'Edit Purchase Order'} />
            <ContentCard>
                {isLoading ? <LoadingState lines={8} /> : lookupError ? <ErrorState description={lookupError.message} title="Unable to load purchase order setup" /> : <PurchaseOrderForm form={form} formError={formError} isSubmitting={updateMutation.isPending} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} variants={variantsQuery.data?.items ?? []} suppliers={suppliersQuery.data?.items ?? []} taxGroups={taxGroupsQuery.data?.items ?? []} unitsOfMeasure={unitsQuery.data?.items ?? []} warehouses={warehousesQuery.data?.items ?? []} submitLabel="Save Purchase Order" />}
            </ContentCard>
        </div>
    );
}
