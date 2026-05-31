import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { VehicleOwnershipPanel } from '../components/VehicleOwnershipPanel';
import {
    VehicleDocumentsPanel,
    VehicleHistoryPanel,
    VehicleInsurancePanel,
    VehicleOverviewPanel,
    VehicleRegistrationPanel,
    VehicleRentalProfilePanel,
    VehicleServiceProfilePanel,
} from '../components/VehiclePanels';
import { VehicleSummaryCard } from '../components/VehicleSummaryCard';
import { vehicleApi } from '../services/vehicleApi';
import type { Vehicle, VehicleDocument, VehicleOwnership, VehicleValidationResult } from '../types/vehicle.types';

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Ownership', value: 'ownership' },
    { label: 'Registration', value: 'registration' },
    { label: 'Insurance', value: 'insurance' },
    { label: 'Service Profile', value: 'service' },
    { label: 'Rental Profile', value: 'rental' },
    { label: 'Documents', value: 'documents' },
    { label: 'Service History', value: 'service-history' },
    { label: 'Rental History', value: 'rental-history' },
    { label: 'Activity / Audit', value: 'audit' },
];

function pageError(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
        return error.message;
    }

    return error instanceof Error ? error.message : fallback;
}

export function VehicleDetailPage() {
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [currentOwnership, setCurrentOwnership] = useState<VehicleOwnership | null>(null);
    const [documents, setDocuments] = useState<VehicleDocument[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [ownerships, setOwnerships] = useState<VehicleOwnership[]>([]);
    const [rentalValidation, setRentalValidation] = useState<VehicleValidationResult | null>(null);
    const [serviceValidation, setServiceValidation] = useState<VehicleValidationResult | null>(null);
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);

    const loadVehicle = useCallback(async () => {
        setError('');

        const [vehicleResponse, ownershipResponse, currentOwnershipResponse, documentResponse, serviceValidationResponse, rentalValidationResponse] =
            await Promise.all([
                vehicleApi.get(id),
                vehicleApi.listOwnerships(id),
                vehicleApi.getCurrentOwnership(id, 'legal_owner'),
                vehicleApi.listDocuments(id),
                vehicleApi.validateUsage(id, 'service'),
                vehicleApi.validateUsage(id, 'rental'),
            ]);

        setVehicle(vehicleResponse.data);
        setOwnerships(ownershipResponse.data);
        setCurrentOwnership(currentOwnershipResponse.data);
        setDocuments(documentResponse.data);
        setServiceValidation(serviceValidationResponse.data);
        setRentalValidation(rentalValidationResponse.data);
    }, [id]);

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        loadVehicle()
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load vehicle detail.'));
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
    }, [loadVehicle]);

    async function refreshOwnership() {
        const [ownershipResponse, currentOwnershipResponse] = await Promise.all([
            vehicleApi.listOwnerships(id),
            vehicleApi.getCurrentOwnership(id, 'legal_owner'),
        ]);

        setOwnerships(ownershipResponse.data);
        setCurrentOwnership(currentOwnershipResponse.data);
    }

    if (isLoading) {
        return <EmptyState description="Loading vehicle profile, ownership, documents, and validation results..." title="Loading vehicle" />;
    }

    if (error || !vehicle) {
        return <EmptyState description={error || 'Vehicle was not found.'} title="Unable to load vehicle" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Link to="/vehicles">
                            <Button variant="secondary">Back</Button>
                        </Link>
                        <Link to={`/vehicles/${vehicle.id}/edit`}>
                            <Button>Edit Vehicle</Button>
                        </Link>
                    </>
                }
                eyebrow="Vehicle"
                subtitle="Vehicle master data stays generic. Service and rental workflows consume this profile and ownership context without owning it."
                title={`${vehicle.registrationNumber || vehicle.code || 'Vehicle'} ${vehicle.make || vehicle.model ? `- ${[vehicle.make, vehicle.model].filter(Boolean).join(' ')}` : ''}`}
            />

            <VehicleSummaryCard currentOwnership={currentOwnership} vehicle={vehicle} />

            <Card className="p-5">
                <Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} />
            </Card>

            {activeTab === 'overview' ? <VehicleOverviewPanel vehicle={vehicle} /> : null}
            {activeTab === 'ownership' ? (
                <VehicleOwnershipPanel
                    currentOwnership={currentOwnership}
                    onChanged={refreshOwnership}
                    ownerships={ownerships}
                    vehicleId={vehicle.id}
                />
            ) : null}
            {activeTab === 'registration' ? <VehicleRegistrationPanel vehicle={vehicle} /> : null}
            {activeTab === 'insurance' ? <VehicleInsurancePanel vehicle={vehicle} /> : null}
            {activeTab === 'service' ? <VehicleServiceProfilePanel validation={serviceValidation} vehicle={vehicle} /> : null}
            {activeTab === 'rental' ? <VehicleRentalProfilePanel validation={rentalValidation} vehicle={vehicle} /> : null}
            {activeTab === 'documents' ? <VehicleDocumentsPanel documents={documents} /> : null}
            {activeTab === 'service-history' ? <VehicleHistoryPanel title="Service History References" /> : null}
            {activeTab === 'rental-history' ? <VehicleHistoryPanel title="Rental History References" /> : null}
            {activeTab === 'audit' ? <VehicleHistoryPanel title="Activity / Audit" /> : null}
        </div>
    );
}
