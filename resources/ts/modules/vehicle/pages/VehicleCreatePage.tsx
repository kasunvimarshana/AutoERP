import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { VehicleForm } from '../components/VehicleForm';
import { vehicleApi } from '../services/vehicleApi';
import type { VehicleFieldErrors, VehicleFormInput } from '../types/vehicle.types';

type FormState = {
    errors: VehicleFieldErrors;
    message: string;
};

const emptyFormState: FormState = { errors: {}, message: '' };

function formError(error: unknown): FormState {
    if (error instanceof ApiError) {
        return { errors: error.errors, message: error.message };
    }

    return { errors: {}, message: error instanceof Error ? error.message : 'Unable to create vehicle.' };
}

export function VehicleCreatePage() {
    const navigate = useNavigate();
    const [formState, setFormState] = useState<FormState>(emptyFormState);
    const [isSaving, setIsSaving] = useState(false);

    async function handleSubmit(input: VehicleFormInput) {
        setFormState(emptyFormState);
        setIsSaving(true);

        try {
            const response = await vehicleApi.create(input);
            navigate(`/vehicles/${response.data.id}`);
        } catch (error) {
            setFormState(formError(error));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Vehicle"
                subtitle="Create vehicle master data. Ownership is added after save as a separate history-aware context."
                title="New Vehicle"
            />
            <VehicleForm
                errors={formState.errors}
                globalError={formState.message}
                isSaving={isSaving}
                mode="create"
                onSubmit={handleSubmit}
            />
        </div>
    );
}
