import { useCallback, useEffect, useRef, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useAuth } from '@/modules/auth/AuthProvider';
import type { ApiCollection } from '@/shared/types/api';
import { createVehicleDocument, deleteVehicleDocument, fetchVehicleDocumentFile, listVehicleDocuments, updateVehicleDocument } from '../vehicleApi';
import { hasVehiclePermission, vehiclePermissions } from '../vehiclePermissions';
import type { VehicleDocument, VehicleDocumentPayload } from '../vehicleTypes';

const documentTypes = ['registration', 'insurance', 'emission_test', 'revenue_license', 'fitness_certificate', 'lease_document', 'ownership_document', 'warranty', 'other'];
const statuses = ['pending', 'active', 'expired', 'revoked'];
const emptyPayload: VehicleDocumentPayload = { document_type: 'registration', document_number: '', issued_date: '', expiry_date: '', file: null, status: 'pending', notes: '' };

export function VehicleDocumentTab({ vehicleId }: { vehicleId: number }) {
    const auth = useAuth();
    const canManage = hasVehiclePermission(auth, vehiclePermissions.manageDocuments);
    const canDownload = hasVehiclePermission(auth, vehiclePermissions.downloadDocuments);
    const [rows, setRows] = useState<VehicleDocument[]>([]);
    const [form, setForm] = useState<VehicleDocumentPayload>(emptyPayload);
    const [editing, setEditing] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [fileAction, setFileAction] = useState<string | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const controllerRef = useRef<AbortController | null>(null);
    const requestSeq = useRef(0);

    const load = useCallback(async () => {
        controllerRef.current?.abort();
        const controller = new AbortController();
        const seq = requestSeq.current + 1;
        requestSeq.current = seq;
        controllerRef.current = controller;
        setLoading(true);
        try {
            const response: ApiCollection<VehicleDocument> = await listVehicleDocuments(vehicleId, { per_page: 50 }, controller.signal);
            if (!controller.signal.aborted && requestSeq.current === seq) {
                setRows(response.data);
                setError(null);
            }
        } catch (requestError) {
            if (!controller.signal.aborted && requestSeq.current === seq) setError(toApiError(requestError));
        } finally {
            if (!controller.signal.aborted && requestSeq.current === seq) setLoading(false);
        }
    }, [vehicleId]);

    useEffect(() => {
        void load();
        return () => controllerRef.current?.abort();
    }, [load]);

    const submit = async () => {
        if (submitting || !canManage) return;
        setSubmitting(true);
        setError(null);
        try {
            if (editing) await updateVehicleDocument(vehicleId, editing, form);
            else await createVehicleDocument(vehicleId, form);
            await load();
            setForm(emptyPayload);
            setEditing(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const destroy = async (row: VehicleDocument) => {
        if (deletingId !== null || !canManage) return;
        if (!window.confirm('Delete this vehicle document?')) return;
        setDeletingId(row.id);
        setError(null);
        try {
            await deleteVehicleDocument(vehicleId, row.id);
            await load();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setDeletingId(null);
        }
    };

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

    if (loading) return <LoadingState label="Loading documents..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            {canManage && (
                <div className="space-y-3">
                    <div className="grid gap-3 md:grid-cols-3">
                        <Select label="Document Type" value={form.document_type} options={documentTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, document_type: event.target.value as VehicleDocumentPayload['document_type'] })} error={fieldError(error, 'document_type')} />
                        <Input label="Reference Number" value={form.document_number ?? ''} onChange={(event) => setForm({ ...form, document_number: event.target.value })} error={fieldError(error, 'document_number')} />
                        <Input key={`${editing ?? 'new'}-${form.file?.name ?? 'empty'}`} label={editing ? 'Replacement File' : 'File'} type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" onChange={(event) => setForm({ ...form, file: event.target.files?.[0] ?? null })} error={fieldError(error, 'file')} />
                        <Input label="Issue Date" type="date" value={form.issued_date ?? ''} onChange={(event) => setForm({ ...form, issued_date: event.target.value })} error={fieldError(error, 'issued_date')} />
                        <Input label="Expiry Date" type="date" value={form.expiry_date ?? ''} onChange={(event) => setForm({ ...form, expiry_date: event.target.value })} error={fieldError(error, 'expiry_date')} />
                        <Select label="Status" value={form.status} options={statuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, status: event.target.value as VehicleDocumentPayload['status'] })} error={fieldError(error, 'status')} />
                        <div className="md:col-span-3">
                            <Textarea label="Remarks" value={form.notes ?? ''} onChange={(event) => setForm({ ...form, notes: event.target.value })} error={fieldError(error, 'notes')} />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="button" loading={submitting} onClick={submit}>{editing ? 'Update Document' : 'Add Document'}</Button>
                        {editing && <Button type="button" variant="secondary" disabled={submitting} onClick={() => { setEditing(null); setForm(emptyPayload); }}>Cancel</Button>}
                    </div>
                </div>
            )}
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'type', header: 'Type', render: (row) => <div><p className="font-medium text-slate-900">{row.document_type.replaceAll('_', ' ')}</p><p className="text-xs text-slate-500">{row.file_name ?? 'No file'}</p></div> },
                    { key: 'number', header: 'Reference', render: (row) => row.document_number ?? '-' },
                    { key: 'issued', header: 'Issued', render: (row) => row.issued_date ?? '-' },
                    { key: 'expiry', header: 'Expiry', render: (row) => row.expiry_date ?? '-' },
                    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                    { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2">
                        {canDownload && row.has_file && <Button variant="ghost" loading={fileAction === `preview-${row.id}`} onClick={() => void openFile(row, 'preview')}>Preview</Button>}
                        {canDownload && row.has_file && <Button variant="ghost" loading={fileAction === `download-${row.id}`} onClick={() => void openFile(row, 'download')}>Download</Button>}
                        {canManage && <Button variant="ghost" disabled={submitting || deletingId !== null} onClick={() => { setEditing(row.id); setForm({ document_type: row.document_type, document_number: row.document_number ?? '', issued_date: row.issued_date ?? '', expiry_date: row.expiry_date ?? '', file: null, status: row.status, notes: row.notes ?? '' }); }}>Edit</Button>}
                        {canManage && <Button variant="danger" loading={deletingId === row.id} disabled={submitting || (deletingId !== null && deletingId !== row.id)} onClick={() => void destroy(row)}>Delete</Button>}
                    </div> },
                ]}
            />
        </div>
    );
}
