import { useCallback, useEffect, useRef, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { useAuth } from '@/modules/auth/AuthProvider';
import type { ApiCollection } from '@/shared/types/api';
import { createVehicleAttribute, deleteVehicleAttribute, listVehicleAttributes, updateVehicleAttribute } from '../vehicleApi';
import { hasVehiclePermission, vehiclePermissions } from '../vehiclePermissions';
import type { VehicleAttribute, VehicleAttributePayload } from '../vehicleTypes';

const dataTypes = ['text', 'number', 'date', 'boolean', 'decimal'];
const emptyPayload: VehicleAttributePayload = { attribute_key: '', attribute_value: '', data_type: 'text', sort_order: 0 };

export function VehicleAttributeTab({ vehicleId }: { vehicleId: number }) {
    const auth = useAuth();
    const canManage = hasVehiclePermission(auth.permissions, vehiclePermissions.manageAttributes);
    const [rows, setRows] = useState<VehicleAttribute[]>([]);
    const [form, setForm] = useState<VehicleAttributePayload>(emptyPayload);
    const [editing, setEditing] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);
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
            const response: ApiCollection<VehicleAttribute> = await listVehicleAttributes(vehicleId, { per_page: 50 }, controller.signal);
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
            if (editing) await updateVehicleAttribute(vehicleId, editing, form);
            else await createVehicleAttribute(vehicleId, form);
            await load();
            setForm(emptyPayload);
            setEditing(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const destroy = async (row: VehicleAttribute) => {
        if (deletingId !== null || !canManage) return;
        if (!window.confirm('Delete this vehicle attribute?')) return;
        setDeletingId(row.id);
        setError(null);
        try {
            await deleteVehicleAttribute(vehicleId, row.id);
            await load();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setDeletingId(null);
        }
    };

    if (loading) return <LoadingState label="Loading attributes..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            {canManage && (
                <div className="space-y-3">
                    <div className="grid gap-3 md:grid-cols-4">
                        <Input label="Key" value={form.attribute_key} onChange={(event) => setForm({ ...form, attribute_key: event.target.value })} error={fieldError(error, 'attribute_key')} />
                        <Input label="Value" value={form.attribute_value ?? ''} onChange={(event) => setForm({ ...form, attribute_value: event.target.value })} error={fieldError(error, 'attribute_value')} />
                        <Select label="Data Type" value={form.data_type} options={dataTypes.map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, data_type: event.target.value as VehicleAttributePayload['data_type'] })} />
                        <Input label="Sort" type="number" min={0} value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: Number(event.target.value) })} error={fieldError(error, 'sort_order')} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="button" loading={submitting} onClick={submit}>{editing ? 'Update Attribute' : 'Add Attribute'}</Button>
                        {editing && <Button type="button" variant="secondary" disabled={submitting} onClick={() => { setEditing(null); setForm(emptyPayload); }}>Cancel</Button>}
                    </div>
                </div>
            )}
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'key', header: 'Key', render: (row) => row.attribute_key },
                    { key: 'value', header: 'Value', render: (row) => row.attribute_value ?? '-' },
                    { key: 'type', header: 'Type', render: (row) => row.data_type },
                    { key: 'actions', header: '', render: (row) => canManage ? <div className="flex justify-end gap-2"><Button variant="ghost" disabled={submitting || deletingId !== null} onClick={() => { setEditing(row.id); setForm({ attribute_key: row.attribute_key, attribute_value: row.attribute_value ?? '', data_type: row.data_type, sort_order: row.sort_order }); }}>Edit</Button><Button variant="danger" loading={deletingId === row.id} disabled={submitting || (deletingId !== null && deletingId !== row.id)} onClick={() => void destroy(row)}>Delete</Button></div> : null },
                ]}
            />
        </div>
    );
}
