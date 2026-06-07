import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { createVehicleServiceDocument, deleteVehicleServiceDocument, listVehicleServiceDocuments } from '../vehicleServiceApi';

export default function VehicleServiceDocumentTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listVehicleServiceDocuments(jobId, signal), [jobId]);
    const [type, setType] = useState('image');
    const [description, setDescription] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <form className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-3" onSubmit={async (event) => {
                event.preventDefault();
                setSaving(true);
                setError(null);
                const payload = new FormData();
                payload.set('document_type', type);
                payload.set('description', description);
                if (file) payload.set('file', file);
                try {
                    await createVehicleServiceDocument(jobId, payload);
                    setDescription('');
                    setFile(null);
                    result.reload();
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSaving(false);
                }
            }}>
                <Select label="Document type" value={type} options={['image', 'inspection_report', 'warranty', 'invoice_copy', 'other'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setType(event.target.value)} />
                <Input label="Description" value={description} onChange={(event) => setDescription(event.target.value)} />
                <Input label="File" type="file" onChange={(event) => setFile(event.target.files?.[0] ?? null)} />
                <Button type="submit" loading={saving}>Upload document</Button>
            </form>
            {result.loading ? <LoadingState /> : (
                <DataTable
                    rows={result.data ?? []}
                    rowKey={(document) => document.id}
                    columns={[
                        { key: 'type', header: 'Type', render: (document) => document.document_type.replaceAll('_', ' ') },
                        { key: 'description', header: 'Description', render: (document) => document.description ?? '-' },
                        { key: 'path', header: 'File', render: (document) => document.file_path ?? '-' },
                        { key: 'actions', header: '', render: (document) => <Button type="button" variant="danger" onClick={async () => { await deleteVehicleServiceDocument(jobId, document.id); result.reload(); }}>Delete</Button> },
                    ]}
                />
            )}
        </div>
    );
}
