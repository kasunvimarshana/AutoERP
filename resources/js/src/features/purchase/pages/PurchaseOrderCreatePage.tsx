import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate } from 'react-router-dom';
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
import { useTaxGroups } from '../../tax/hooks';
import { useWarehouses } from '../../warehouse/hooks';
import { PurchaseOrderForm } from '../components/PurchaseOrderForm';
import { useCreatePurchaseOrder } from '../hooks';
import { purchaseOrderFormSchema, type PurchaseOrderFormInput, type PurchaseOrderFormValues } from '../schemas';

export function PurchaseOrderCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { user } = useAuth();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 250, is_active: true, sort: 'name' });
    const variantsQuery = useProductVariants({ tenant_id: tenantId, page: 1, per_page: 500, is_active: true, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const taxGroupsQuery = useTaxGroups({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const createMutation = useCreatePurchaseOrder();
    const form = useForm<PurchaseOrderFormInput, unknown, PurchaseOrderFormValues>({
        resolver: zodResolver(purchaseOrderFormSchema),
        defaultValues: {
            supplier_id: '',
            warehouse_id: '',
            currency_id: '',
            po_number: '',
            order_date: new Date().toISOString().slice(0, 10),
            expected_date: '',
            organization_unit_id: '',
            exchange_rate: '',
            header_discount_type: '',
            header_discount_value: '',
            header_tax_group_id: '',
            notes: '',
            lines: [],
        },
    });

    async function onSubmit(values: PurchaseOrderFormValues) {
        setFormError(null);

        try {
            const totals = calculateOrderTotals(values);
            const purchaseOrder = await createMutation.mutateAsync({
                tenant_id: tenantId,
                supplier_id: values.supplier_id,
                warehouse_id: values.warehouse_id,
                currency_id: values.currency_id ?? null,
                po_number: values.po_number || undefined,
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
                paid_amount: 0,
                balance: totals.grand_total,
                notes: values.notes ?? null,
                created_by: user?.id ?? 1,
                lines: values.lines.map((line) => ({
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

            showToast({ title: 'Purchase order created', description: `${purchaseOrder.po_number} is ready for confirmation.`, tone: 'success' });
            navigate(`/purchase/orders/${purchaseOrder.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create purchase order.');
        }
    }

    const lookupError = suppliersQuery.error ?? warehousesQuery.error ?? productsQuery.error ?? variantsQuery.error ?? unitsQuery.error ?? taxGroupsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/orders' }, { label: 'Add Purchase Order' }]} description="Create a purchase order with product line items, pricing, discounts, and totals." title="Add Purchase Order" />
            <ContentCard>
                {suppliersQuery.isPending || warehousesQuery.isPending || productsQuery.isPending || variantsQuery.isPending || unitsQuery.isPending || taxGroupsQuery.isPending ? <LoadingState lines={8} /> : lookupError ? <ErrorState description={lookupError.message} title="Unable to load purchase order setup" /> : <PurchaseOrderForm form={form} formError={formError} isSubmitting={createMutation.isPending} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} variants={variantsQuery.data?.items ?? []} suppliers={suppliersQuery.data?.items ?? []} taxGroups={taxGroupsQuery.data?.items ?? []} unitsOfMeasure={unitsQuery.data?.items ?? []} warehouses={warehousesQuery.data?.items ?? []} />}
            </ContentCard>
        </div>
    );
}

function toNumber(value: unknown) {
    const numeric = Number(value ?? 0);
    return Number.isFinite(numeric) ? numeric : 0;
}

function round(value: number) {
    return Math.round(value * 10000) / 10000;
}

export function calculateLineAmounts(line: PurchaseOrderFormValues['lines'][number]) {
    const gross_amount = round(line.ordered_qty * line.unit_price);
    const rawDiscount = line.discount_type === 'fixed' ? toNumber(line.discount_value) : gross_amount * (toNumber(line.discount_value) / 100);
    const discount_amount = round(Math.min(rawDiscount, gross_amount));
    const line_total = round(Math.max(gross_amount - discount_amount, 0));
    const tax_amount = round(toNumber(line.tax_amount));
    const line_total_with_tax = round(line_total + tax_amount);

    return { gross_amount, discount_amount, line_total, tax_amount, line_total_with_tax };
}

export function calculateOrderTotals(values: PurchaseOrderFormValues) {
    const lines = values.lines.map(calculateLineAmounts);
    const subtotal = round(lines.reduce((total, line) => total + line.gross_amount, 0));
    const line_discount_total = round(lines.reduce((total, line) => total + line.discount_amount, 0));
    const line_tax_total = round(lines.reduce((total, line) => total + line.tax_amount, 0));
    const headerBase = Math.max(subtotal - line_discount_total, 0);
    const header_discount_amount = round(values.header_discount_type === 'fixed'
        ? Math.min(toNumber(values.header_discount_value), headerBase)
        : values.header_discount_type === 'percentage'
            ? Math.min(headerBase * (toNumber(values.header_discount_value) / 100), headerBase)
            : 0);
    const discount_total = round(line_discount_total + header_discount_amount);
    const tax_total = line_tax_total;
    const grand_total = round(subtotal - discount_total + tax_total);

    return { subtotal, line_discount_total, line_tax_total, header_discount_amount, discount_total, tax_total, grand_total };
}
