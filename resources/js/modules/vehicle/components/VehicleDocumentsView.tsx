import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fetchVehicleDocumentFile, listVehicleDocuments } from '../vehicleApi';
import { hasVehiclePermission, vehiclePermissions } from '../vehiclePermissions';
import type { VehicleDocument } from '../vehicleTypes';

export function VehicleDocumentsView({ vehicleId }: { vehicleId: number }) {
    const auth = useAuth();
    const canDownload = hasVehiclePermission(auth, vehiclePermissions.downloadDocuments);
    const [rows, setRows] = useState<VehicleDocument[]>([]);
    const [loading, setLoading] = useState(true);
    const [fileAction, setFileAction] = useState<string | null>(null);
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

    const openFile = async (row: VehicleDocument, mode: 'preview' | 'download') => {
        if (!canDownload || !row.has_file) return;
        const action = `${mode}-${row.id}`;
        setFileAction(action);
        setError(null);
        try {
            const blob = await fetchVehicleDocumentFile(vehicleId, row.id, mode);
            const url = URL.createObjectURL(blob);
            if (mode === 'preview') {
                window.open(url, '_blank', 'noopener,noreferrer');
            } else {
                const link = window.document.createElement('a');
                link.href = url;
                link.download = row.file_name ?? 'vehicle-document';
                link.click();
            }
            window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setFileAction(null);
        }
    };

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
                    { key: 'file', header: 'File', render: (row) => row.has_file ? <div className="flex flex-wrap gap-2">
                        <span className="text-slate-600">{row.file_name}</span>
                        {canDownload && <Button variant="ghost" loading={fileAction === `preview-${row.id}`} onClick={() => void openFile(row, 'preview')}>Preview</Button>}
                        {canDownload && <Button variant="ghost" loading={fileAction === `download-${row.id}`} onClick={() => void openFile(row, 'download')}>Download</Button>}
                    </div> : '-' },
                ]}
            />
        </div>
    );
}
