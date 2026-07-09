import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { listVehicleServiceStatusHistory } from '../vehicleServiceApi';
import { VehicleServiceStatusBadge } from './VehicleServiceStatusBadge';

export default function VehicleServiceStatusHistoryTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listVehicleServiceStatusHistory(jobId, signal), [jobId]);
    if (result.loading) return <LoadingState />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={result.error} />
            <DataTable
                rows={result.data ?? []}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'dimension', header: 'Dimension', render: (row) => row.dimension.replaceAll('_', ' ') },
                    { key: 'old', header: 'From', render: (row) => row.old_status ? <VehicleServiceStatusBadge status={row.old_status} /> : '-' },
                    { key: 'new', header: 'To', render: (row) => <VehicleServiceStatusBadge status={row.new_status} /> },
                    { key: 'reason', header: 'Reason', render: (row) => row.reason ?? '-' },
                    { key: 'changed', header: 'Changed at', render: (row) => formatDate(row.changed_at) },
                ]}
            />
        </div>
    );
}
