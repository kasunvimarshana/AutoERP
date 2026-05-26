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
import { useTenant } from '../../auth/context/TenantContext';
import { useOrganizationUnits } from '../../organization/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { WarehouseForm } from '../components/WarehouseForm';
import { useUpdateWarehouse, useWarehouse } from '../hooks';
import { warehouseFormSchema, type WarehouseFormInput, type WarehouseFormValues } from '../schemas';

export function WarehouseEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { warehouseId: warehouseIdParam } = useParams();
    const warehouseId = parsePositiveInteger(warehouseIdParam ?? null, 0);
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

    const warehouseQuery = useWarehouse(warehouseId, warehouseId > 0);
    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'path' });
    const updateMutation = useUpdateWarehouse(warehouseId);

    useEffect(() => {
        if (!warehouseQuery.data) {
            return;
        }

        form.reset({
            org_unit_id: warehouseQuery.data.org_unit_id ?? '',
            name: warehouseQuery.data.name,
            code: warehouseQuery.data.code ?? '',
            image_path: warehouseQuery.data.image_path ?? '',
            type: warehouseQuery.data.type,
            address_id: warehouseQuery.data.address_id ?? '',
            is_active: warehouseQuery.data.is_active,
            is_default: warehouseQuery.data.is_default,
        });
    }, [form, warehouseQuery.data]);

    async function onSubmit(values: WarehouseFormValues) {
        setFormError(null);

        try {
            const warehouse = await updateMutation.mutateAsync({
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
                title: 'Warehouse updated',
                description: `${warehouse.name} was updated successfully.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to update warehouse.');
        }
    }

    if (warehouseId <= 0) {
        return <ErrorState description="The warehouse route is missing a valid warehouse ID." title="Invalid warehouse route" />;
    }

    const lookupError = warehouseQuery.error ?? organizationUnitsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Warehouses', href: '/warehouses' }, { label: 'Edit Warehouse' }]}
                description="Edit warehouse ownership, status, and operational type using the shared administrative form shell."
                title="Edit Warehouse"
            />

            <ContentCard>
                {warehouseQuery.isPending || organizationUnitsQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : lookupError ? (
                    isForbiddenError(lookupError) ? (
                        <ProtectedErrorState description={lookupError.message} />
                    ) : (
                        <ErrorState description={lookupError.message} title="Unable to load warehouse" />
                    )
                ) : (
                    <WarehouseForm form={form} formError={formError} isSubmitting={updateMutation.isPending} mode="edit" onSubmit={onSubmit} organizationUnits={organizationUnitsQuery.data?.items ?? []} />
                )}
            </ContentCard>
        </div>
    );
}
