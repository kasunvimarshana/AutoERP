import { useEffect, useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import {
    createVehicleServiceDocument,
    deleteVehicleServiceDocument,
    downloadVehicleServiceDocument,
    getVehicleServiceDocumentOptions,
    listVehicleServiceDocuments,
} from '../vehicleServiceApi';
import type { VehicleServiceDocument } from '../vehicleServiceTypes';


export default function VehicleServiceDocumentTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listVehicleServiceDocuments(jobId, signal), [jobId]);
    const options = useApi((signal) => getVehicleServiceDocumentOptions(jobId, signal), [jobId]);
    const [type, setType] = useState('');
    const [description, setDescription] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [fileInputKey, setFileInputKey] = useState(0);
    const [saving, setSaving] = useState(false);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<VehicleServiceDocument | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(saving);

    useEffect(() => {
        const firstType = options.data?.document_types[0];
        if (type === '' && firstType) setType(firstType);
    }, [options.data, type]);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!file) return;

        setSaving(true);
        setError(null);
        const payload = new FormData();
        payload.set('document_type', type);
        if (description.trim() !== '') payload.set('description', description.trim());
        payload.set('file', file);

        try {
            const created = await createVehicleServiceDocument(jobId, payload);
            result.setData([created, ...(result.data ?? [])]);
            setDescription('');
            setFile(null);
            setFileInputKey((current) => current + 1);
            formGuard.markSaved();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function download(document: VehicleServiceDocument) {
        setBusyId(document.id);
        setError(null);
        try {
            await downloadVehicleServiceDocument(jobId, document);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    }

    async function remove() {
        if (!deleteTarget) return;
        setBusyId(deleteTarget.id);
        setError(null);
        try {
            await deleteVehicleServiceDocument(jobId, deleteTarget.id);
            result.setData((result.data ?? []).filter((document) => document.id !== deleteTarget.id));
            setDeleteTarget(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    }

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            {options.error && (
                <div className="space-y-3">
                    <ErrorAlert error={options.error} title="Document upload rules unavailable" />
                    <Button type="button" variant="secondary" onClick={options.reload}>Retry upload rules</Button>
                </div>
            )}
            <form className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-3" onSubmit={submit}>
                <Select
                    label="Document type"
                    value={type}
                    error={fieldError(error, 'document_type')}
                    options={(options.data?.document_types ?? []).map((value) => ({
                        value,
                        label: value.replaceAll('_', ' '),
                    }))}
                    onChange={(event) => { formGuard.markDirty(); setType(event.target.value); }}
                />
                <Input
                    label="Description"
                    value={description}
                    error={fieldError(error, 'description')}
                    onChange={(event) => { formGuard.markDirty(); setDescription(event.target.value); }}
                />
                <Input
                    key={fileInputKey}
                    label="Private document"
                    type="file"
                    accept={(options.data?.mime_types ?? []).join(',')}
                    error={fieldError(error, 'file')}
                    hint={options.data
                        ? `${options.data.mime_types.map(readableMimeType).join(', ')} up to ${formatBytes(options.data.max_size_bytes)}. Files are stored privately and downloaded through an authorized endpoint.`
                        : 'Loading authorized upload rules…'}
                    required
                    onChange={(event) => { formGuard.markDirty(); setFile(event.target.files?.[0] ?? null); }}
                />
                <Button type="submit" loading={saving || options.loading} disabled={!file || !type || !options.data}>Upload document</Button>
            </form>
            {result.loading ? <LoadingState /> : (
                <DataTable
                    rows={result.data ?? []}
                    rowKey={(document) => document.id}
                    columns={[
                        { key: 'type', header: 'Type', render: (document) => document.document_type.replaceAll('_', ' ') },
                        { key: 'description', header: 'Description', render: (document) => document.description ?? '-' },
                        {
                            key: 'file',
                            header: 'File',
                            render: (document) => (
                                <div>
                                    <p className="font-medium text-slate-900">{document.original_filename}</p>
                                    <p className="text-xs text-slate-500">{document.mime_type} · {formatBytes(document.size_bytes)}</p>
                                </div>
                            ),
                        },
                        {
                            key: 'actions',
                            header: 'Actions',
                            render: (document) => (
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        loading={busyId === document.id && deleteTarget?.id !== document.id}
                                        disabled={busyId !== null && busyId !== document.id}
                                        onClick={() => void download(document)}
                                    >
                                        Download
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="danger"
                                        disabled={busyId !== null}
                                        onClick={() => setDeleteTarget(document)}
                                    >
                                        Delete
                                    </Button>
                                </div>
                            ),
                        },
                    ]}
                />
            )}
            <ConfirmDialog
                open={deleteTarget !== null}
                title="Delete document?"
                message={deleteTarget ? `Delete “${deleteTarget.original_filename}” and its private stored file? This cannot be undone.` : ''}
                confirmLabel="Delete document"
                loading={deleteTarget !== null && busyId === deleteTarget.id}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void remove()}
            />
        </div>
    );
}

function formatBytes(value: number): string {
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}


function readableMimeType(value: string): string {
    const labels: Record<string, string> = {
        'application/pdf': 'PDF',
        'image/jpeg': 'JPEG',
        'image/png': 'PNG',
    };

    return labels[value] ?? value;
}
