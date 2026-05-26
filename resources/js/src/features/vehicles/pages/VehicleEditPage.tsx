import { useEffect, useState } from 'react';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ContentCard } from '../../../components/ui/ContentCard';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { VehicleForm } from '../components/VehicleForm';
import { useUpdateVehicle, useVehicle } from '../hooks';
import { vehicleFormSchema, type VehicleFormInput, type VehicleFormValues } from '../schemas';
import { readVehicleMetadata, toVehiclePayload, vehicleTitle } from '../utils';

export function VehicleEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { vehicleId: vehicleIdParam } = useParams();
    const vehicleId = parsePositiveInteger(vehicleIdParam ?? null, 0);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<VehicleFormInput, unknown, VehicleFormValues>({
        resolver: zodResolver(vehicleFormSchema),
        defaultValues: {
            org_unit_id: '',
            customer_id: '',
            supplier_id: '',
            ownership_type: 'company_owned',
            asset_code: '',
            make: '',
            model: '',
            year: '',
            vin: '',
            registration_number: '',
            chassis_number: '',
            fuel_type: 'petrol',
            transmission: 'manual',
            odometer: '',
            rental_status: 'available',
            service_status: 'none',
            next_maintenance_due_at: '',
            primary_image_path: '',
            color: '',
            engine_number: '',
            notes: '',
            is_active: true,
        },
    });

    const vehicleQuery = useVehicle(vehicleId, tenantId, vehicleId > 0);
    const updateMutation = useUpdateVehicle(vehicleId);

    useEffect(() => {
        if (!vehicleQuery.data) {
            return;
        }

        const metadata = readVehicleMetadata(vehicleQuery.data);

        form.reset({
            org_unit_id: vehicleQuery.data.org_unit_id ?? '',
            customer_id: vehicleQuery.data.customer_id ?? '',
            supplier_id: vehicleQuery.data.supplier_id ?? '',
            ownership_type: vehicleQuery.data.ownership_type,
            asset_code: vehicleQuery.data.asset_code ?? '',
            make: vehicleQuery.data.make,
            model: vehicleQuery.data.model,
            year: vehicleQuery.data.year ?? '',
            vin: vehicleQuery.data.vin ?? '',
            registration_number: vehicleQuery.data.registration_number ?? '',
            chassis_number: vehicleQuery.data.chassis_number ?? '',
            fuel_type: vehicleQuery.data.fuel_type ?? 'petrol',
            transmission: vehicleQuery.data.transmission ?? 'manual',
            odometer: vehicleQuery.data.odometer ?? '',
            rental_status: vehicleQuery.data.rental_status,
            service_status: vehicleQuery.data.service_status,
            next_maintenance_due_at: vehicleQuery.data.next_maintenance_due_at?.slice(0, 10) ?? '',
            primary_image_path: vehicleQuery.data.primary_image_path ?? '',
            color: typeof metadata.color === 'string' ? metadata.color : '',
            engine_number: typeof metadata.engine_number === 'string' ? metadata.engine_number : '',
            notes: typeof metadata.notes === 'string' ? metadata.notes : '',
            is_active: vehicleQuery.data.is_active ?? true,
        });
    }, [form, vehicleQuery.data]);

    async function onSubmit(values: VehicleFormValues) {
        setFormError(null);

        try {
            const vehicle = await updateMutation.mutateAsync(toVehiclePayload(tenantId, values));

            showToast({
                title: 'Vehicle updated',
                description: `${vehicleTitle(vehicle)} was updated successfully.`,
                tone: 'success',
            });
            navigate(`/vehicles/${vehicle.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update vehicle.');
        }
    }

    if (vehicleId <= 0) {
        return <ErrorState description="The vehicle route is missing a valid vehicle ID." title="Invalid vehicle route" />;
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Vehicle', href: '/vehicles' }, { label: 'Edit Vehicle' }]}
                description="Edit vehicle registry fields using the same form shell as Add Vehicle."
                title="Edit Vehicle"
            />

            <ContentCard>
                {vehicleQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : vehicleQuery.isError ? (
                    isForbiddenError(vehicleQuery.error) ? (
                        <ProtectedErrorState description={vehicleQuery.error.message} />
                    ) : (
                        <ErrorState description={vehicleQuery.error.message} title="Unable to load vehicle" />
                    )
                ) : (
                    <VehicleForm form={form} formError={formError} isSubmitting={updateMutation.isPending} mode="edit" onSubmit={onSubmit} />
                )}
            </ContentCard>
        </div>
    );
}
