import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { LoadingState } from '@/shared/components/LoadingState';
import { getVehicle, updateVehicle } from './vehicleApi';
import { VehicleForm } from './components/VehicleForm';
import type { Vehicle, VehicleAttributePayload, VehicleDocumentPayload, VehicleOwnershipPayload, VehiclePayload } from './vehicleTypes';

export default function VehicleEditPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const vehicleId = Number(id);
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        getVehicle(vehicleId, controller.signal)
            .then(setVehicle)
            .catch((requestError) => setError(toApiError(requestError)))
            .finally(() => setLoading(false));
        return () => controller.abort();
    }, [vehicleId]);

    const submit = async (payload: VehiclePayload, _relations: { documents: VehicleDocumentPayload[]; ownerships: VehicleOwnershipPayload[]; attributes: VehicleAttributePayload[] }) => {
        setSubmitting(true);
        setError(null);
        try {
            const updated = await updateVehicle(vehicleId, payload);
            navigate(`/vehicles/${updated.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <LoadingState label="Loading vehicle..." />;

    return (
        <div>
            <ContentHeader title="Edit Vehicle" description={vehicle?.vehicle_number} />
            <VehicleForm initial={vehicle} error={error} submitting={submitting} onSubmit={submit} />
        </div>
    );
}
