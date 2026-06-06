import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { getVehicle } from './vehicleApi';
import { VehicleAttributeTab } from './components/VehicleAttributeTab';
import { VehicleDocumentTab } from './components/VehicleDocumentTab';
import { VehicleOwnershipTab } from './components/VehicleOwnershipTab';
import { VehicleStatusHistoryTab } from './components/VehicleStatusHistoryTab';
import { VehicleSummaryCard } from './components/VehicleSummaryCard';
import type { Vehicle } from './vehicleTypes';

type DetailTab = 'summary' | 'ownership' | 'documents' | 'attributes' | 'history';

export default function VehicleDetailPage() {
    const { id } = useParams();
    const vehicleId = Number(id);
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);
    const [tab, setTab] = useState<DetailTab>('summary');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        getVehicle(vehicleId, controller.signal)
            .then(setVehicle)
            .catch((requestError) => setError(toApiError(requestError)))
            .finally(() => setLoading(false));
        return () => controller.abort();
    }, [vehicleId]);

    if (loading) return <LoadingState label="Loading vehicle..." />;
    if (!vehicle) return <ErrorAlert error={error} />;

    return (
        <div>
            <ContentHeader title={vehicle.vehicle_number} description={vehicle.registration_number ?? vehicle.code ?? undefined} actions={<Link to={`/vehicles/${vehicle.id}/edit`}><Button>Edit</Button></Link>} />
            <ErrorAlert error={error} />
            <Panel>
                <Tabs<DetailTab> active={tab} onChange={setTab} tabs={[
                    { id: 'summary', label: 'Summary' },
                    { id: 'ownership', label: 'Ownership' },
                    { id: 'documents', label: 'Documents' },
                    { id: 'attributes', label: 'Attributes' },
                    { id: 'history', label: 'Status History' },
                ]} />
                <div className="mt-5">
                    {tab === 'summary' && <VehicleSummaryCard vehicle={vehicle} />}
                    {tab === 'ownership' && <VehicleOwnershipTab vehicleId={vehicle.id} />}
                    {tab === 'documents' && <VehicleDocumentTab vehicleId={vehicle.id} />}
                    {tab === 'attributes' && <VehicleAttributeTab vehicleId={vehicle.id} />}
                    {tab === 'history' && <VehicleStatusHistoryTab vehicleId={vehicle.id} />}
                </div>
            </Panel>
        </div>
    );
}
