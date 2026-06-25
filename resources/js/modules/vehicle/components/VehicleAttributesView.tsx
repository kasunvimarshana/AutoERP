import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { listVehicleAttributes } from '../vehicleApi';
import type { VehicleAttribute } from '../vehicleTypes';

export function VehicleAttributesView({ vehicleId }: { vehicleId: number }) {
    const [rows, setRows] = useState<VehicleAttribute[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setLoading(true);
        });
        listVehicleAttributes(vehicleId, { per_page: 50 }, controller.signal)
            .then((response) => {
                if (!controller.signal.aborted) {
                    setRows(response.data);
                    setError(null);
                }
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [vehicleId]);

    if (loading) return <LoadingState label="Loading attributes..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'key', header: 'Key', render: (row) => row.attribute_key },
                    { key: 'value', header: 'Value', render: (row) => row.attribute_value ?? '-' },
                    { key: 'type', header: 'Type', render: (row) => row.data_type },
                ]}
            />
        </div>
    );
}
