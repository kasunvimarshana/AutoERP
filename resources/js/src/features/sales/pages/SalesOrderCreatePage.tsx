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
import { useCustomers } from '../../customers/hooks';
import { useWarehouses } from '../../warehouse/hooks';
import { SalesOrderForm } from '../components/SalesOrderForm';
import { useCreateSalesOrder } from '../hooks';
import { salesOrderFormSchema, type SalesOrderFormInput, type SalesOrderFormValues } from '../schemas';

export function SalesOrderCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { user } = useAuth();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const createMutation = useCreateSalesOrder();
    const form = useForm<SalesOrderFormInput, unknown, SalesOrderFormValues>({
        resolver: zodResolver(salesOrderFormSchema),
        defaultValues: {
            customer_id: '',
            warehouse_id: '',
            currency_id: '',
            order_date: new Date().toISOString().slice(0, 10),
            requested_delivery_date: '',
            org_unit_id: '',
            price_list_id: '',
            exchange_rate: '',
            subtotal: '',
            tax_total: '',
            discount_total: '',
            grand_total: '',
            notes: '',
        },
    });

    async function onSubmit(values: SalesOrderFormValues) {
        setFormError(null);

        try {
            const salesOrder = await createMutation.mutateAsync({
                tenant_id: tenantId,
                customer_id: values.customer_id,
                warehouse_id: values.warehouse_id,
                currency_id: values.currency_id,
                order_date: values.order_date,
                requested_delivery_date: values.requested_delivery_date ?? null,
                org_unit_id: values.org_unit_id ?? null,
                price_list_id: values.price_list_id ?? null,
                exchange_rate: values.exchange_rate ?? null,
                subtotal: values.subtotal ?? null,
                tax_total: values.tax_total ?? null,
                discount_total: values.discount_total ?? null,
                grand_total: values.grand_total ?? null,
                notes: values.notes ?? null,
                created_by: user?.id ?? null,
            });

            showToast({ title: 'Sales order created', description: `${salesOrder.so_number} is ready for workflow processing.`, tone: 'success' });
            navigate(`/sales/orders/${salesOrder.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create sales order.');
        }
    }

    const lookupError = customersQuery.error ?? warehousesQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales', href: '/sales/orders' }, { label: 'Add Sales Order' }]} description="Create a sales order with the same large-card ERP form layout used across the product and master-data modules." title="Add Sales Order" />
            <ContentCard>
                {customersQuery.isPending || warehousesQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : lookupError ? (
                    <ErrorState description={lookupError.message} title="Unable to load sales order setup" />
                ) : (
                    <SalesOrderForm
                        customers={customersQuery.data?.items ?? []}
                        form={form}
                        formError={formError}
                        isSubmitting={createMutation.isPending}
                        onSubmit={onSubmit}
                        warehouses={warehousesQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
