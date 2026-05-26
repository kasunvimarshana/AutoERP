import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
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
import { useOrganizationUnits } from '../../organization/hooks';
import { useProducts } from '../../products/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { useWarehouses } from '../../warehouse/hooks';
import { ValuationConfigForm } from '../components/ValuationConfigForm';
import { valuationConfigFormSchema, type ValuationConfigFormInput, type ValuationConfigFormValues } from '../schemas';
import { useCreateValuationConfig, useDeleteValuationConfig, useUpdateValuationConfig, useValuationConfigs } from '../hooks';
import type { ValuationConfigRecord } from '../types';

export function ValuationConfigsPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [editingConfig, setEditingConfig] = useState<ValuationConfigRecord | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<ValuationConfigRecord | null>(null);
    const valuationConfigsQuery = useValuationConfigs({ tenant_id: tenantId, page: 1, per_page: 100 });
    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'path' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const createMutation = useCreateValuationConfig();
    const updateMutation = useUpdateValuationConfig(editingConfig?.id ?? 0);
    const deleteMutation = useDeleteValuationConfig(tenantId);
    const form = useForm<ValuationConfigFormInput, unknown, ValuationConfigFormValues>({
        resolver: zodResolver(valuationConfigFormSchema),
        defaultValues: {
            org_unit_id: '',
            warehouse_id: '',
            product_id: '',
            transaction_type: '',
            valuation_method: 'fifo',
            allocation_strategy: 'fifo',
            is_active: true,
        },
    });

    useEffect(() => {
        if (!editingConfig) {
            form.reset({
                org_unit_id: '',
                warehouse_id: '',
                product_id: '',
                transaction_type: '',
                valuation_method: 'fifo',
                allocation_strategy: 'fifo',
                is_active: true,
            });
            return;
        }

        form.reset({
            org_unit_id: editingConfig.org_unit_id ?? '',
            warehouse_id: editingConfig.warehouse_id ?? '',
            product_id: editingConfig.product_id ?? '',
            transaction_type: editingConfig.transaction_type ?? '',
            valuation_method: editingConfig.valuation_method,
            allocation_strategy: editingConfig.allocation_strategy,
            is_active: editingConfig.is_active,
        });
    }, [editingConfig, form]);

    async function onSubmit(values: ValuationConfigFormValues) {
        setFormError(null);

        try {
            const payload = {
                tenant_id: tenantId,
                org_unit_id: values.org_unit_id ?? null,
                warehouse_id: values.warehouse_id ?? null,
                product_id: values.product_id ?? null,
                transaction_type: values.transaction_type ?? null,
                valuation_method: values.valuation_method,
                allocation_strategy: values.allocation_strategy,
                is_active: values.is_active,
            };

            if (editingConfig) {
                await updateMutation.mutateAsync(payload);
                showToast({ title: 'Valuation config updated', description: 'The valuation configuration was updated successfully.', tone: 'success' });
            } else {
                await createMutation.mutateAsync(payload);
                showToast({ title: 'Valuation config created', description: 'The valuation configuration is now active for inventory resolution.', tone: 'success' });
            }

            setEditingConfig(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to save valuation config.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        await deleteMutation.mutateAsync(deleteTarget.id);
        setDeleteTarget(null);
        if (editingConfig?.id === deleteTarget.id) {
            setEditingConfig(null);
        }
        showToast({ title: 'Valuation config deleted', description: 'The valuation configuration was removed successfully.', tone: 'success' });
    }

    const columns: DataTableColumn<ValuationConfigRecord>[] = [
        { key: 'scope', header: 'Scope', render: (config) => <span className="text-sm text-stone-700">Product {config.product_id ? `#${config.product_id}` : 'Any'} / Warehouse {config.warehouse_id ? `#${config.warehouse_id}` : 'Any'}</span> },
        { key: 'transaction_type', header: 'Transaction Type', render: (config) => <span className="text-sm text-stone-700">{config.transaction_type ?? 'Any'}</span> },
        { key: 'valuation_method', header: 'Valuation', render: (config) => <span className="text-sm text-stone-700">{config.valuation_method}</span> },
        { key: 'allocation_strategy', header: 'Allocation', render: (config) => <span className="text-sm text-stone-700">{config.allocation_strategy}</span> },
        { key: 'status', header: 'Status', render: (config) => <StatusBadge tone={config.is_active ? 'success' : 'default'}>{config.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[14rem]',
            render: (config) => (
                <div className="flex flex-wrap gap-2">
                    <Button className="h-9 px-3 text-xs" onClick={() => setEditingConfig(config)} type="button" variant="secondary">Edit</Button>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(config)} type="button" variant="secondary">Delete</Button>
                </div>
            ),
        },
    ];

    const lookupError = valuationConfigsQuery.error ?? organizationUnitsQuery.error ?? warehousesQuery.error ?? productsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory' }, { label: 'Valuation Configs' }]} description="Inventory costing rules are managed in a shared admin layout with backend-safe create, update, and delete actions." title="Valuation Configs" />

            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard className="p-0">
                    {valuationConfigsQuery.isPending ? <LoadingState className="m-6" lines={8} /> : valuationConfigsQuery.isError ? <ErrorState className="m-6" description={valuationConfigsQuery.error.message} title="Unable to load valuation configs" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No valuation configs are defined yet." title="No valuation configs found" />} getRowKey={(config) => config.id} rows={valuationConfigsQuery.data.items} />}
                </ContentCard>

                <ContentCard>
                    {organizationUnitsQuery.isPending || warehousesQuery.isPending || productsQuery.isPending ? (
                        <LoadingState lines={8} />
                    ) : lookupError ? (
                        <ErrorState description={lookupError.message} title="Unable to load valuation config setup" />
                    ) : (
                        <ValuationConfigForm
                            form={form}
                            formError={formError}
                            isSubmitting={createMutation.isPending || updateMutation.isPending}
                            mode={editingConfig ? 'edit' : 'create'}
                            onCancel={() => setEditingConfig(null)}
                            onSubmit={onSubmit}
                            organizationUnits={organizationUnitsQuery.data?.items ?? []}
                            products={productsQuery.data?.items ?? []}
                            warehouses={warehousesQuery.data?.items ?? []}
                        />
                    )}
                </ContentCard>
            </div>

            <ConfirmWorkflowModal confirmLabel="Delete valuation config" description={deleteTarget ? `Delete valuation config #${deleteTarget.id}?` : ''} isLoading={deleteMutation.isPending} onCancel={() => setDeleteTarget(null)} onConfirm={() => void handleDeleteConfirm()} open={Boolean(deleteTarget)} title="Delete valuation config" />
        </div>
    );
}
