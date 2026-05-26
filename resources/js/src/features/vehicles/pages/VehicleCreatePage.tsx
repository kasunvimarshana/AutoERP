import { useState } from 'react';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ContentCard } from '../../../components/ui/ContentCard';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { VehicleForm } from '../components/VehicleForm';
import { useCreateVehicle } from '../hooks';
import { vehicleFormSchema, type VehicleFormInput, type VehicleFormValues } from '../schemas';
import { toVehiclePayload, vehicleTitle } from '../utils';

const vehicleDefaultValues: VehicleFormInput = {
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
};

export function VehicleCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<VehicleFormInput, unknown, VehicleFormValues>({
        resolver: zodResolver(vehicleFormSchema),
        defaultValues: vehicleDefaultValues,
    });
    const createMutation = useCreateVehicle();

    async function onSubmit(values: VehicleFormValues) {
        setFormError(null);

        try {
            const vehicle = await createMutation.mutateAsync(toVehiclePayload(tenantId, values));

            showToast({
                title: 'Vehicle created',
                description: `${vehicleTitle(vehicle)} has been added to the vehicle registry.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to create vehicle.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Vehicle', href: '/vehicles' }, { label: 'Add Vehicle' }]}
                description="Create a vehicle registry record using the same large-card master-data form style used across AutoERP."
                title="Add Vehicle"
            />

            <ContentCard>
                <VehicleForm form={form} formError={formError} isSubmitting={createMutation.isPending} mode="create" onSubmit={onSubmit} />
            </ContentCard>

            {createMutation.isError && formError === null ? <ErrorState description={createMutation.error.message} title="Unable to create vehicle" /> : null}
        </div>
    );
}
