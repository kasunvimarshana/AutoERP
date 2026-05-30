import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { VehicleForm } from '../components/VehicleForm';
import { VehicleOwnershipPanel } from '../components/VehicleOwnershipPanel';
import { VehicleSummaryCard } from '../components/VehicleSummaryCard';
import { vehicleApi } from '../services/vehicleApi';
import type { Vehicle, VehicleFormInput, VehicleOwnership } from '../types/vehicle.types';

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Ownership', value: 'ownership' },
    { label: 'Registration / Insurance', value: 'compliance' },
    { label: 'Service Profile', value: 'service' },
    { label: 'Rental Profile', value: 'rental' },
    { label: 'Activity / Audit', value: 'audit' },
];

const defaultVehicleFormValue: VehicleFormInput = {
    brand: '',
    category: '',
    code: '',
    color: '',
    currentOdometer: '0',
    fuelType: '',
    insuranceExpiry: '',
    model: '',
    registrationExpiry: '',
    registrationNumber: '',
    rentalEnabled: false,
    serviceEnabled: true,
    status: 'draft',
    transmissionType: '',
    usageProfile: 'dual',
    vin: '',
    year: '',
};

function numericText(value?: string) {
    const parsed = (value ?? '').replace(/[^\d]/g, '');

    return parsed === '' ? '0' : parsed;
}

function vehicleToFormInput(vehicle: Vehicle): VehicleFormInput {
    return {
        brand: vehicle.brand === 'Not provided' ? '' : vehicle.brand,
        category: vehicle.category === 'Not classified' ? '' : vehicle.category,
        code: vehicle.code,
        color: vehicle.color ?? '',
        currentOdometer: numericText(vehicle.currentOdometer),
        fuelType: vehicle.fuelType ?? '',
        insuranceExpiry: vehicle.insuranceExpiry ?? '',
        model: vehicle.model,
        registrationExpiry: vehicle.registrationExpiry ?? '',
        registrationNumber: vehicle.registrationNumber === 'Not provided' ? '' : vehicle.registrationNumber,
        rentalEnabled: vehicle.rentalEligibility.toLowerCase().includes('enabled'),
        serviceEnabled: !vehicle.serviceEligibility.toLowerCase().includes('disabled'),
        status: vehicle.status as VehicleFormInput['status'],
        transmissionType: vehicle.transmissionType ?? '',
        usageProfile: vehicle.usageProfile === 'Backend profile pending' ? 'dual' : vehicle.usageProfile,
        vin: vehicle.vin ?? '',
        year: vehicle.year ?? '',
    };
}

function VehicleListView() {
    const [vehicles, setVehicles] = useState<Vehicle[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        vehicleApi
            .list()
            .then((response) => {
                if (mounted) {
                    setVehicles(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load vehicles.');
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
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <Link to="/vehicles/new">
                        <Button>New Vehicle</Button>
                    </Link>
                }
                eyebrow="Master Data"
                subtitle="Vehicles are reusable fleet and customer/provider vehicle profiles. Ownership is tracked separately from service customer, rental customer, payer, and provider context."
                title="Vehicles"
            />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Vehicle profiles', String(vehicles.length || 'Backend count'), 'Identity and eligibility are backend-owned'],
                    ['Ownership model', 'History-aware', 'Customer, supplier, company, provider, and external owners'],
                    ['Business usage', 'Shared', 'VehicleService and VehicleRental validate through Vehicle'],
                ].map(([label, value, helper]) => (
                    <Card className="p-5" key={label}>
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                        <p className="mt-1 text-xs text-slate-400">{helper}</p>
                    </Card>
                ))}
            </div>
            <SearchFilterBar placeholder="Search vehicle code, plate, VIN, brand, model..." />
            {isLoading ? <EmptyState description="Loading vehicle master data..." title="Loading vehicles" /> : null}
            {error ? <EmptyState description={error} title="Vehicle service unavailable" /> : null}
            {!isLoading && !error && vehicles.length === 0 ? <EmptyState description="No vehicle profiles were returned." title="No vehicles found" /> : null}
            {!isLoading && !error && vehicles.length > 0 ? (
                <DataTable
                    columns={[
                        { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/vehicles/${row.id}`}>{row.code}</Link> },
                        { header: 'Registration', key: 'registrationNumber' },
                        { header: 'Vehicle', key: 'vehicle', render: (row) => `${row.year || ''} ${row.brand} ${row.model}`.trim() },
                        { header: 'Usage Profile', key: 'usageProfile' },
                        { header: 'Odometer', key: 'currentOdometer' },
                        { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={vehicles}
                />
            ) : null}
        </div>
    );
}

function VehicleEditorView({ mode }: { mode: 'create' | 'edit' }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const [error, setError] = useState('');
    const [formValue, setFormValue] = useState<VehicleFormInput>(defaultVehicleFormValue);
    const [isLoading, setIsLoading] = useState(mode === 'edit');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (mode !== 'edit' || !id) {
            return;
        }

        let mounted = true;

        vehicleApi
            .get(id)
            .then((response) => {
                if (mounted) {
                    setFormValue(vehicleToFormInput(response.data));
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load vehicle for editing.');
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
    }, [id, mode]);

    const submit = async (value: VehicleFormInput) => {
        setError('');
        setIsSubmitting(true);

        try {
            const response = mode === 'edit' && id ? await vehicleApi.update(id, value) : await vehicleApi.create(value);

            navigate(`/vehicles/${response.data.id}`);
        } catch (caught: unknown) {
            setError(caught instanceof Error ? caught.message : 'Unable to save vehicle.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <Link to={id ? `/vehicles/${id}` : '/vehicles'}>
                        <Button variant="secondary">Back</Button>
                    </Link>
                }
                eyebrow="Vehicle"
                subtitle="Vehicle profile writes go through backend validation. Ownership is managed as separate history, not as a single customer or supplier field on the vehicle."
                title={mode === 'create' ? 'New Vehicle' : 'Edit Vehicle'}
            />
            {error ? <EmptyState description={error} title="Vehicle save unavailable" /> : null}
            {isLoading ? <EmptyState description="Loading vehicle profile from backend or mock service..." title="Loading vehicle" /> : null}
            {!isLoading ? <VehicleForm initialValue={formValue} isSubmitting={isSubmitting} onSubmit={submit} /> : null}
        </div>
    );
}

function VehicleDetailView({ vehicleId }: { vehicleId: string }) {
    const [activeTab, setActiveTab] = useState('overview');
    const [currentOwnership, setCurrentOwnership] = useState<VehicleOwnership | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [ownerships, setOwnerships] = useState<VehicleOwnership[]>([]);
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);

    useEffect(() => {
        let mounted = true;

        Promise.all([
            vehicleApi.get(vehicleId),
            vehicleApi.listOwnerships(vehicleId),
            vehicleApi.getCurrentOwnership(vehicleId, 'legal_owner'),
        ])
            .then(([vehicleResponse, ownershipResponse, currentOwnershipResponse]) => {
                if (mounted) {
                    setVehicle(vehicleResponse.data);
                    setOwnerships(ownershipResponse.data);
                    setCurrentOwnership(currentOwnershipResponse.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load vehicle detail.');
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
    }, [vehicleId]);

    if (isLoading) {
        return <EmptyState description="Loading vehicle profile, ownership context, and backend summaries..." title="Loading vehicle" />;
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
                subtitle="Vehicle detail shows generic master data, ownership context, service/rental eligibility, compliance dates, and audit references."
                title={`${vehicle.registrationNumber} - ${vehicle.brand} ${vehicle.model}`}
            />
            <VehicleSummaryCard currentOwnership={currentOwnership} vehicle={vehicle} />
            <Card className="p-5">
                <Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} />
            </Card>

            {activeTab === 'overview' ? (
                <Card className="p-5">
                    <h2 className="text-base font-bold text-slate-950">Overview</h2>
                    <div className="mt-4 grid gap-4 md:grid-cols-3">
                        {[
                            ['Vehicle code', vehicle.code],
                            ['Registration', vehicle.registrationNumber],
                            ['VIN / chassis', vehicle.vin || 'Not provided'],
                            ['Engine number', vehicle.engineNumber || 'Not provided'],
                            ['Fuel', vehicle.fuelType || 'Not provided'],
                            ['Transmission', vehicle.transmissionType || 'Not provided'],
                            ['Category', vehicle.category],
                            ['Type', vehicle.type],
                            ['Color', vehicle.color || 'Not provided'],
                        ].map(([label, value]) => (
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={label}>
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                                <p className="mt-1 text-sm font-semibold text-slate-800">{value}</p>
                            </div>
                        ))}
                    </div>
                </Card>
            ) : null}
            {activeTab === 'ownership' ? <VehicleOwnershipPanel currentOwnership={currentOwnership} ownerships={ownerships} /> : null}
            {activeTab === 'compliance' ? (
                <PreviewPanel
                    rows={[
                        { label: 'Registration expiry', value: vehicle.registrationExpiry || 'Not provided' },
                        { label: 'Insurance expiry', value: vehicle.insuranceExpiry || 'Not provided' },
                        { label: 'Compliance status', value: 'Backend-owned status preview' },
                    ]}
                    title="Registration / Insurance"
                />
            ) : null}
            {activeTab === 'service' ? (
                <PreviewPanel
                    rows={[
                        { label: 'Service eligibility', value: vehicle.serviceEligibility },
                        { label: 'Service history', value: 'Backend-owned reference list' },
                        { label: 'Service due status', value: 'Backend-owned status preview' },
                    ]}
                    title="Service Profile"
                />
            ) : null}
            {activeTab === 'rental' ? (
                <PreviewPanel
                    rows={[
                        { label: 'Rental eligibility', value: vehicle.rentalEligibility },
                        { label: 'Rental availability', value: 'Backend-owned preview' },
                        { label: 'Provider context', value: currentOwnership?.ownershipRole === 'provider' ? currentOwnership.ownerDisplayName : 'Returned by ownership service' },
                    ]}
                    title="Rental Profile"
                />
            ) : null}
            {activeTab === 'audit' ? (
                <PreviewPanel
                    rows={[
                        { label: 'Status history', value: 'Backend-owned history' },
                        { label: 'Documents', value: 'Document module references' },
                        { label: 'Audit', value: 'Audit module timeline' },
                    ]}
                    title="Activity / Audit"
                />
            ) : null}
        </div>
    );
}

export function VehiclePage() {
    const { id } = useParams();
    const location = useLocation();

    if (location.pathname.endsWith('/new')) {
        return <VehicleEditorView mode="create" />;
    }

    if (location.pathname.endsWith('/edit')) {
        return <VehicleEditorView mode="edit" />;
    }

    if (id) {
        return <VehicleDetailView vehicleId={id} />;
    }

    return <VehicleListView />;
}
