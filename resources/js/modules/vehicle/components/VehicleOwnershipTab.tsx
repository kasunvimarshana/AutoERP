import { useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Select } from '@/shared/components/Select';
import { lookupApi } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { createVehicleOwnership, deleteVehicleOwnership, listVehicleOwnerships, updateVehicleOwnership } from '../vehicleApi';
import type { VehicleOwnerType, VehicleOwnership, VehicleOwnershipPayload } from '../vehicleTypes';

const ownershipTypes = ['owned', 'customer_owned', 'leased', 'rented', 'company_owned', 'third_party'];
const ownerTypes: VehicleOwnerType[] = ['customer', 'supplier', 'company'];
const emptyPayload: VehicleOwnershipPayload = { owner_type: 'customer', owner_id: null, ownership_type: 'customer_owned', started_at: businessDateInputValue(), is_current: true, notes: '' };

export function VehicleOwnershipTab({ vehicleId }: { vehicleId: number }) {
    const [rows, setRows] = useState<VehicleOwnership[]>([]);
    const [form, setForm] = useState<VehicleOwnershipPayload>(emptyPayload);
    const [owner, setOwner] = useState<NamedResource | null>(null);
    const [editing, setEditing] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const load = () => {
        const controller = new AbortController();
        setLoading(true);
        listVehicleOwnerships(vehicleId, { per_page: 50 }, controller.signal)
            .then((response) => {
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
            const payload = {
                ...form,
                owner_id: form.owner_type === 'company' ? null : owner?.id ?? form.owner_id ?? null,
            };
            if (editing) await updateVehicleOwnership(vehicleId, editing, payload);
            else await createVehicleOwnership(vehicleId, payload);
            setForm(emptyPayload);
            setOwner(null);
            setEditing(null);
            load();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <LoadingState label="Loading ownerships..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            <div className="grid gap-3 md:grid-cols-4">
                <Select label="Owner Type" value={form.owner_type} options={ownerTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => {
                    const ownerType = event.target.value as VehicleOwnerType;
                    setOwner(null);
                    setForm({
                        ...form,
                        owner_type: ownerType,
                        owner_id: null,
                        ownership_type: ownerType === 'customer' ? 'customer_owned' : ownerType === 'company' ? 'company_owned' : 'third_party',
                    });
                }} error={fieldError(error, 'owner_type')} />
                <Select label="Ownership" value={form.ownership_type} options={ownershipTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, ownership_type: event.target.value as VehicleOwnershipPayload['ownership_type'] })} error={fieldError(error, 'ownership_type')} />
                {form.owner_type !== 'company' && (
                    <LookupSelect label={form.owner_type === 'customer' ? 'Customer' : 'Supplier'} value={owner} onChange={setOwner} search={form.owner_type === 'customer' ? lookupApi.customers : lookupApi.suppliers} error={fieldError(error, 'owner_id')} />
                )}
                <Input label="Started" type="date" value={form.started_at} onChange={(event) => setForm({ ...form, started_at: event.target.value })} error={fieldError(error, 'started_at')} />
                <Input label="Ended" type="date" value={form.ended_at ?? ''} onChange={(event) => setForm({ ...form, ended_at: event.target.value })} error={fieldError(error, 'ended_at')} />
            </div>
            <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" checked={Boolean(form.is_current)} onChange={(event) => setForm({ ...form, is_current: event.target.checked })} />
                Current ownership
            </label>
            <div className="flex gap-2">
                <Button type="button" loading={submitting} onClick={submit}>{editing ? 'Update Ownership' : 'Add Ownership'}</Button>
                {editing && <Button type="button" variant="secondary" onClick={() => { setEditing(null); setForm(emptyPayload); setOwner(null); }}>Cancel</Button>}
            </div>
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'type', header: 'Ownership', render: (row) => row.ownership_type.replaceAll('_', ' ') },
                    { key: 'ownerType', header: 'Owner Type', render: (row) => row.owner_type.replaceAll('_', ' ') },
                    { key: 'owner', header: 'Owner', render: (row) => row.owner?.name ?? '-' },
                    { key: 'started', header: 'Started', render: (row) => row.started_at?.slice(0, 10) ?? '-' },
                    { key: 'current', header: 'Current', render: (row) => row.is_current ? 'Yes' : 'No' },
                    { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><Button variant="ghost" onClick={() => { setEditing(row.id); setOwner(row.owner ?? null); setForm({ owner_type: row.owner_type, owner_id: row.owner_id ?? null, ownership_type: row.ownership_type, started_at: row.started_at?.slice(0, 10) ?? '', ended_at: row.ended_at?.slice(0, 10) ?? '', is_current: row.is_current, notes: row.notes ?? '' }); }}>Edit</Button><Button variant="danger" disabled={row.is_current} onClick={() => deleteVehicleOwnership(vehicleId, row.id).then(() => load()).catch((requestError) => setError(toApiError(requestError)))}>Delete</Button></div> },
                ]}
            />
        </div>
    );
}
