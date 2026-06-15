import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { createVehicle, createVehicleWithRelations } from './vehicleApi';
import { VehicleForm } from './components/VehicleForm';
import type { VehicleAttributePayload, VehicleDocumentPayload, VehicleOwnershipPayload, VehiclePayload } from './vehicleTypes';

export default function VehicleCreatePage() {
    const navigate = useNavigate();
    const location = useLocation();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const submit = async (payload: VehiclePayload, relations: { documents: VehicleDocumentPayload[]; ownerships: VehicleOwnershipPayload[]; attributes: VehicleAttributePayload[] }) => {
        setSubmitting(true);
        setError(null);
        try {
            const hasRelations = relations.documents.length > 0 || relations.ownerships.length > 0 || relations.attributes.length > 0;
            const vehicle = hasRelations ? await createVehicleWithRelations({ vehicle: payload, ...relations }) : await createVehicle(payload);
            navigate(`/vehicles/${vehicle.id}${location.search}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div>
            <ContentHeader title="Create Vehicle" description="Create vehicle master data and optional one-shot relation records." />
            <VehicleForm error={error} submitting={submitting} enableRelations onSubmit={submit} />
        </div>
    );
}
