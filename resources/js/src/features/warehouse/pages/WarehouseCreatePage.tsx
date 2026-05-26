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
import { useTenant } from '../../auth/context/TenantContext';
import { useOrganizationUnits } from '../../organization/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { WarehouseForm } from '../components/WarehouseForm';
import { useCreateWarehouse } from '../hooks';
import { warehouseFormSchema, type WarehouseFormInput, type WarehouseFormValues } from '../schemas';

export function WarehouseCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<WarehouseFormInput, unknown, WarehouseFormValues>({
        resolver: zodResolver(warehouseFormSchema),
        defaultValues: {
            org_unit_id: '',
            name: '',
            code: '',
            image_path: '',
            type: 'standard',
            address_id: '',
            is_active: true,
            is_default: false,
        },
    });

    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'path' });
    const createMutation = useCreateWarehouse();

    async function onSubmit(values: WarehouseFormValues) {
        setFormError(null);

        try {
            const warehouse = await createMutation.mutateAsync({
                tenant_id: tenantId,
                org_unit_id: values.org_unit_id ?? null,
                name: values.name,
                code: values.code ?? null,
                image_path: values.image_path ?? null,
                type: values.type,
                address_id: values.address_id ?? null,
                is_active: values.is_active,
                is_default: values.is_default,
            });

            showToast({
                title: 'Warehouse created',
                description: `${warehouse.name} is ready for location and stock setup.`,
                tone: 'success',
            });
            navigate(`/warehouses/${warehouse.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create warehouse.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Warehouses', href: '/warehouses' }, { label: 'Add Warehouse' }]}
                description="Create a warehouse using the same full-width form structure already established for product and master-data setup flows."
                title="Add Warehouse"
            />

            <ContentCard>
                {organizationUnitsQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : organizationUnitsQuery.isError ? (
                    isForbiddenError(organizationUnitsQuery.error) ? (
                        <ProtectedErrorState description={organizationUnitsQuery.error.message} />
                    ) : (
                        <ErrorState description={organizationUnitsQuery.error.message} title="Unable to load warehouse setup lookups" />
                    )
                ) : (
                    <WarehouseForm form={form} formError={formError} isSubmitting={createMutation.isPending} mode="create" onSubmit={onSubmit} organizationUnits={organizationUnitsQuery.data?.items ?? []} />
                )}
            </ContentCard>
        </div>
    );
}
