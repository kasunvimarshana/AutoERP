import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type { Vehicle, VehicleDocument, VehicleMasterSummary, VehicleValidationResult } from '../types/vehicle.types';

function yesNo(value: boolean) {
    return value ? 'Enabled' : 'Disabled';
}

export function VehicleTable({ rows }: { rows: Vehicle[] }) {
    return (
        <DataTable
            columns={[
                {
                    header: 'Vehicle',
                    key: 'vehicle',
                    render: (row) => (
                        <div>
                            <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicles/${row.id}`}>
                                {row.code || row.registrationNumber || `Vehicle #${row.id}`}
                            </Link>
                            <p className="mt-1 text-xs text-slate-500">{[row.year, row.make, row.model].filter(Boolean).join(' ') || 'Vehicle details pending'}</p>
                        </div>
                    ),
                },
                { header: 'Registration', key: 'registrationNumber', render: (row) => row.registrationNumber || 'Pending' },
                { header: 'Category', key: 'category', render: (row) => row.category || 'Unspecified' },
                { header: 'Service', key: 'serviceEnabled', render: (row) => yesNo(row.serviceEnabled) },
                { header: 'Rental', key: 'rentalEnabled', render: (row) => yesNo(row.rentalEnabled) },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                {
                    header: 'Actions',
                    key: 'actions',
                    render: (row) => (
                        <div className="flex flex-wrap gap-2">
                            <Link to={`/vehicles/${row.id}`}>
                                <Button variant="ghost">View</Button>
                            </Link>
                            <Link to={`/vehicles/${row.id}/edit`}>
                                <Button variant="secondary">Edit</Button>
                            </Link>
                        </div>
                    ),
                },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function VehicleOverviewPanel({ vehicle }: { vehicle: Vehicle }) {
    const rows = [
        ['Vehicle code', vehicle.code || 'Not provided'],
        ['Registration', vehicle.registrationNumber || 'Not provided'],
        ['VIN / chassis', vehicle.vin || 'Not provided'],
        ['Make', vehicle.make || 'Not provided'],
        ['Model', vehicle.model || 'Not provided'],
        ['Year', vehicle.year || 'Not provided'],
        ['Category', vehicle.category || 'Not classified'],
        ['Color', vehicle.color || 'Not provided'],
        ['Odometer', vehicle.currentOdometer || '0'],
        ['Fuel', vehicle.fuelType || 'Not provided'],
        ['Transmission', vehicle.transmission || 'Not provided'],
        ['Seating capacity', vehicle.seatingCapacity || 'Not provided'],
    ];

    return (
        <Card className="p-5">
            <h2 className="text-base font-bold text-slate-950">Overview</h2>
            <div className="mt-4 grid gap-4 md:grid-cols-3">
                {rows.map(([label, value]) => (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={label}>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                        <p className="mt-1 text-sm font-semibold text-slate-800">{value}</p>
                    </div>
                ))}
            </div>
        </Card>
    );
}

export function VehicleRegistrationPanel({ vehicle }: { vehicle: Vehicle }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Registration number', value: vehicle.registrationNumber || 'Not provided' },
                { label: 'Registration expiry', value: vehicle.registrationExpiry || 'Not provided' },
                { label: 'Compliance status', value: 'Backend-owned; not calculated in frontend' },
            ]}
            subtitle="Registration values come from the Vehicle API. Expiry status is not calculated in the frontend."
            title="Registration"
        />
    );
}

export function VehicleInsurancePanel({ vehicle }: { vehicle: Vehicle }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Insurance expiry', value: vehicle.insuranceExpiry || 'Not provided' },
                { label: 'Insurance status', value: 'Backend-owned; not calculated in frontend' },
                { label: 'Risk/accounting impact', value: 'Owned by backend modules' },
            ]}
            subtitle="Insurance details are displayed as backend data. Risk or finance impact belongs to backend services."
            title="Insurance"
        />
    );
}

export function VehicleServiceProfilePanel({
    validation,
    vehicle,
}: {
    validation?: VehicleValidationResult | null;
    vehicle: Vehicle;
}) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Service enabled', value: yesNo(vehicle.serviceEnabled) },
                { label: 'Service validation', value: validation ? (validation.isValid ? 'Valid' : 'Not valid') : 'Backend validation not loaded' },
                { label: 'Validation reason', value: validation?.reason ?? 'Backend validation endpoint owns this decision' },
                { label: 'Last service date', value: vehicle.lastServiceDate || 'Not provided' },
                { label: 'Last service odometer', value: vehicle.lastServiceOdometer || 'Not provided' },
                { label: 'Next service due', value: vehicle.nextServiceDueDate || 'Backend-owned due status' },
            ]}
            status={validation?.isValid ? 'Valid' : vehicle.serviceEnabled ? 'Enabled' : 'Disabled'}
            subtitle="Vehicle only exposes generic service eligibility. VehicleService owns job workflow rules."
            title="Service Profile"
        />
    );
}

export function VehicleRentalProfilePanel({
    validation,
    vehicle,
}: {
    validation?: VehicleValidationResult | null;
    vehicle: Vehicle;
}) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Rental enabled', value: yesNo(vehicle.rentalEnabled) },
                { label: 'Rental validation', value: validation ? (validation.isValid ? 'Valid' : 'Not valid') : 'Backend validation not loaded' },
                { label: 'Validation reason', value: validation?.reason ?? 'Backend validation endpoint owns this decision' },
                { label: 'Provider payable', value: 'Owned by VehicleRental backend workflow' },
                { label: 'Availability', value: 'Backend-owned; not calculated in frontend' },
            ]}
            status={validation?.isValid ? 'Valid' : vehicle.rentalEnabled ? 'Enabled' : 'Disabled'}
            subtitle="Vehicle only exposes generic rental eligibility. VehicleRental owns agreement and billing rules."
            title="Rental Profile"
        />
    );
}

export function VehicleDocumentsPanel({ documents }: { documents: VehicleDocument[] }) {
    if (documents.length === 0) {
        return <EmptyState description="No vehicle documents were returned by the backend." title="No documents" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Document', key: 'title' },
                { header: 'Type', key: 'documentType' },
                { header: 'Number', key: 'documentNumber', render: (row) => row.documentNumber || 'Not provided' },
                { header: 'Expiry', key: 'expiryDate', render: (row) => row.expiryDate || 'Not provided' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={documents}
        />
    );
}

export function VehicleHistoryPanel({ title }: { title: string }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Source', value: 'Backend/reference modules' },
                { label: 'Calculation', value: 'No frontend totals or availability logic' },
                { label: 'Current status', value: 'History endpoints not exposed in Vehicle API yet' },
            ]}
            subtitle="History references are displayed without calculating service cost, rental usage, or availability in the frontend."
            title={title}
        />
    );
}

export function VehicleMasterSummaryTable({ rows }: { rows: VehicleMasterSummary[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Name', key: 'name' },
                { header: 'Profiles', key: 'recordCount' },
                { header: 'Latest status', key: 'latestStatus', render: (row) => <StatusBadge status={row.latestStatus} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}
