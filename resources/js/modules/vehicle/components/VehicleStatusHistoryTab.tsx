import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { listVehicleStatusHistory } from '../vehicleApi';
import type { VehicleStatusHistory } from '../vehicleTypes';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';

export function VehicleStatusHistoryTab({ vehicleId }: { vehicleId: number }) {
    const [rows, setRows] = useState<VehicleStatusHistory[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setLoading(true);
        });
        listVehicleStatusHistory(vehicleId, { per_page: 50 }, controller.signal)
            .then((response) => {
                if (!controller.signal.aborted) setRows(response.data);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [vehicleId]);

    if (loading) return <LoadingState label="Loading status history..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'from', header: 'From', render: (row) => row.old_status ?? '-' },
                    { key: 'to', header: 'To', render: (row) => row.new_status },
                    { key: 'reason', header: 'Reason', render: (row) => row.reason ?? '-' },
                    { key: 'changed', header: 'Changed', render: (row) => row.changed_at ? formatBusinessDateTime(row.changed_at) : '-' },
                ]}
            />
        </div>
    );
}
