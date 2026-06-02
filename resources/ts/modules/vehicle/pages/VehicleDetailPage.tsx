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
    const [loadedTabs, setLoadedTabs] = useState<Set<string>>(new Set(['overview']));
    const [tabLoading, setTabLoading] = useState('');

    const loadVehicle = useCallback(async () => {
        setError('');

        const [vehicleResponse, currentOwnershipResponse] =
            await Promise.all([
                vehicleApi.get(id),
                vehicleApi.getCurrentOwnership(id, 'legal_owner'),
            ]);

        setVehicle(vehicleResponse.data);
        setCurrentOwnership(currentOwnershipResponse.data);
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

    useEffect(() => {
        let mounted = true;

        if (!vehicle || loadedTabs.has(activeTab)) {
            return () => {
                mounted = false;
            };
        }

        async function loadTab() {
            setTabLoading(activeTab);
            try {
                if (activeTab === 'ownership') {
                    const [ownershipResponse, currentOwnershipResponse] = await Promise.all([
                        vehicleApi.listOwnerships(id),
                        vehicleApi.getCurrentOwnership(id, 'legal_owner'),
                    ]);
                    if (mounted) {
                        setOwnerships(ownershipResponse.data);
                        setCurrentOwnership(currentOwnershipResponse.data);
                    }
                }
                if (activeTab === 'documents') {
                    const response = await vehicleApi.listDocuments(id);
                    if (mounted) setDocuments(response.data);
                }
                if (activeTab === 'service') {
                    const response = await vehicleApi.validateUsage(id, 'service');
                    if (mounted) setServiceValidation(response.data);
                }
                if (activeTab === 'rental') {
                    const response = await vehicleApi.validateUsage(id, 'rental');
                    if (mounted) setRentalValidation(response.data);
                }

                if (mounted) {
                    setLoadedTabs((current) => new Set([...current, activeTab]));
                }
            } catch (error: unknown) {
                if (mounted) {
                    setError(pageError(error, 'Unable to load this vehicle section.'));
                }
            } finally {
                if (mounted) {
                    setTabLoading('');
                }
            }
        }

        void loadTab();

        return () => {
            mounted = false;
        };
    }, [activeTab, id, loadedTabs, vehicle]);

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
            {tabLoading ? <EmptyState description="Loading this vehicle section from the backend..." title="Loading section" /> : null}

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
        </div>
    );
}
