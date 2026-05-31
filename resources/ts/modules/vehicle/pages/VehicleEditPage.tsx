import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { VehicleForm } from '../components/VehicleForm';
import { vehicleApi } from '../services/vehicleApi';
import type { Vehicle, VehicleFieldErrors, VehicleFormInput } from '../types/vehicle.types';

type FormState = {
    errors: VehicleFieldErrors;
    message: string;
};

const emptyFormState: FormState = { errors: {}, message: '' };

function pageError(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
        return error.message;
    }

    return error instanceof Error ? error.message : fallback;
}

function formError(error: unknown): FormState {
    if (error instanceof ApiError) {
        return { errors: error.errors, message: error.message };
    }

    return { errors: {}, message: error instanceof Error ? error.message : 'Unable to update vehicle.' };
}

export function VehicleEditPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const [error, setError] = useState('');
    const [formState, setFormState] = useState<FormState>(emptyFormState);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        vehicleApi
            .get(id)
            .then((response) => {
                if (mounted) {
                    setVehicle(response.data);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load vehicle.'));
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    async function handleSubmit(input: VehicleFormInput) {
        if (!vehicle) {
            return;
        }

        setFormState(emptyFormState);
        setIsSaving(true);

        try {
            const response = await vehicleApi.update(vehicle.id, input);
            navigate(`/vehicles/${response.data.id}`);
        } catch (error) {
            setFormState(formError(error));
        } finally {
            setIsSaving(false);
        }
    }

    if (isLoading) {
        return <EmptyState description="Loading vehicle profile from the backend..." title="Loading vehicle" />;
    }

    if (error || !vehicle) {
        return <EmptyState description={error || 'Vehicle was not found.'} title="Unable to edit vehicle" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Vehicle"
                subtitle="Edit vehicle master fields. Ownership remains a separate history-aware tab."
                title={`Edit ${vehicle.code || vehicle.registrationNumber || 'Vehicle'}`}
            />
            <VehicleForm
                errors={formState.errors}
                globalError={formState.message}
                isSaving={isSaving}
                mode="edit"
                onSubmit={handleSubmit}
                vehicle={vehicle}
            />
        </div>
    );
}
