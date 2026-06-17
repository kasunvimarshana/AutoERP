import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { listVehicleDocuments } from '../vehicleApi';
import type { VehicleDocument } from '../vehicleTypes';

export function VehicleDocumentsView({ vehicleId }: { vehicleId: number }) {
    const [rows, setRows] = useState<VehicleDocument[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        listVehicleDocuments(vehicleId, { per_page: 50 }, controller.signal)
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

    if (loading) return <LoadingState label="Loading documents..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'type', header: 'Type', render: (row) => row.document_type.replaceAll('_', ' ') },
                    { key: 'number', header: 'Reference', render: (row) => row.document_number ?? '-' },
                    { key: 'issued', header: 'Issued', render: (row) => row.issued_date ?? '-' },
                    { key: 'expiry', header: 'Expiry', render: (row) => row.expiry_date ?? '-' },
                    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                    { key: 'file', header: 'File', render: (row) => row.file_path ? <a href={row.file_path} target="_blank" rel="noreferrer" className="font-medium text-sky-700 hover:underline">Preview / download</a> : '-' },
                ]}
            />
        </div>
    );
}
