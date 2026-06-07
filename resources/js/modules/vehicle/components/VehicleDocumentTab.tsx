import { useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import type { ApiCollection } from '@/shared/types/api';
import { createVehicleDocument, deleteVehicleDocument, listVehicleDocuments, updateVehicleDocument } from '../vehicleApi';
import type { VehicleDocument, VehicleDocumentPayload } from '../vehicleTypes';

const documentTypes = ['registration', 'insurance', 'emission_test', 'revenue_license', 'fitness_certificate', 'lease_document', 'ownership_document', 'warranty', 'other'];
const statuses = ['active', 'expired', 'revoked', 'pending'];
const emptyPayload: VehicleDocumentPayload = { document_type: 'registration', document_number: '', issued_date: '', expiry_date: '', file_path: '', status: 'pending', notes: '' };

export function VehicleDocumentTab({ vehicleId }: { vehicleId: number }) {
    const [rows, setRows] = useState<VehicleDocument[]>([]);
    const [form, setForm] = useState<VehicleDocumentPayload>(emptyPayload);
    const [editing, setEditing] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const load = () => {
        const controller = new AbortController();
        setLoading(true);
        listVehicleDocuments(vehicleId, { per_page: 50 }, controller.signal)
            .then((response: ApiCollection<VehicleDocument>) => {
                if (!controller.signal.aborted) setRows(response.data);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return controller;
    };

    useEffect(() => {
        const controller = load();
        return () => controller.abort();
    }, [vehicleId]);

    const submit = async () => {
        setSubmitting(true);
        setError(null);
        try {
            if (editing) await updateVehicleDocument(vehicleId, editing, form);
            else await createVehicleDocument(vehicleId, form);
            setForm(emptyPayload);
            setEditing(null);
            load();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <LoadingState label="Loading documents..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            <div className="grid gap-3 md:grid-cols-4">
                <Select label="Type" value={form.document_type} options={documentTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, document_type: event.target.value as VehicleDocumentPayload['document_type'] })} error={fieldError(error, 'document_type')} />
                <Input label="Number" value={form.document_number ?? ''} onChange={(event) => setForm({ ...form, document_number: event.target.value })} error={fieldError(error, 'document_number')} />
                <Input label="Expiry Date" type="date" value={form.expiry_date ?? ''} onChange={(event) => setForm({ ...form, expiry_date: event.target.value })} error={fieldError(error, 'expiry_date')} />
                <Select label="Status" value={form.status} options={statuses.map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, status: event.target.value as VehicleDocumentPayload['status'] })} error={fieldError(error, 'status')} />
            </div>
            <div className="flex gap-2">
                <Button type="button" loading={submitting} onClick={submit}>{editing ? 'Update Document' : 'Add Document'}</Button>
                {editing && <Button type="button" variant="secondary" onClick={() => { setEditing(null); setForm(emptyPayload); }}>Cancel</Button>}
            </div>
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'type', header: 'Type', render: (row) => row.document_type.replaceAll('_', ' ') },
                    { key: 'number', header: 'Number', render: (row) => row.document_number ?? '-' },
                    { key: 'expiry', header: 'Expiry', render: (row) => row.expiry_date ?? '-' },
                    { key: 'status', header: 'Status', render: (row) => row.status },
                    { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><Button variant="ghost" onClick={() => { setEditing(row.id); setForm({ document_type: row.document_type, document_number: row.document_number ?? '', issued_date: row.issued_date ?? '', expiry_date: row.expiry_date ?? '', file_path: row.file_path ?? '', status: row.status, notes: row.notes ?? '' }); }}>Edit</Button><Button variant="danger" onClick={() => deleteVehicleDocument(vehicleId, row.id).then(() => load()).catch((requestError) => setError(toApiError(requestError)))}>Delete</Button></div> },
                ]}
            />
        </div>
    );
}
