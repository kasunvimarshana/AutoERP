import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type { VehicleOwnership } from '../types/vehicle.types';

function humanize(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export function VehicleOwnershipPanel({
    currentOwnership,
    ownerships,
}: {
    currentOwnership: VehicleOwnership | null;
    ownerships: VehicleOwnership[];
}) {
    return (
        <div className="space-y-5">
            <PreviewPanel
                rows={[
                    { label: 'Current owner', value: currentOwnership?.ownerDisplayName ?? 'No current legal owner returned' },
                    { label: 'Owner type', value: currentOwnership ? humanize(currentOwnership.ownerType) : 'Pending' },
                    { label: 'Ownership type', value: currentOwnership ? humanize(currentOwnership.ownershipType) : 'Pending' },
                    { label: 'Role', value: currentOwnership ? humanize(currentOwnership.ownershipRole) : 'Pending' },
                    { label: 'Started', value: currentOwnership?.startDate ?? 'Pending' },
                ]}
                status={currentOwnership ? 'Current' : 'Missing'}
                subtitle="Ownership is context and history, separate from billing customer, payer, and rental provider workflow rules."
                title="Current Ownership"
            />
            {ownerships.length === 0 ? (
                <EmptyState description="No ownership history was returned for this vehicle." title="No ownership records" />
            ) : (
                <DataTable
                    columns={[
                        { header: 'Owner', key: 'ownerDisplayName' },
                        { header: 'Owner Type', key: 'ownerType', render: (row) => humanize(row.ownerType) },
                        { header: 'Ownership', key: 'ownershipType', render: (row) => humanize(row.ownershipType) },
                        { header: 'Role', key: 'ownershipRole', render: (row) => humanize(row.ownershipRole) },
                        { header: 'Window', key: 'window', render: (row) => `${row.startDate} to ${row.endDate || 'Current'}` },
                        { header: 'Status', key: 'isCurrent', render: (row) => <StatusBadge status={row.isCurrent ? 'active' : 'inactive'} /> },
                        { header: 'Notes', key: 'notes', render: (row) => row.notes || 'No notes' },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={ownerships}
                />
            )}
        </div>
    );
}

