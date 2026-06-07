import { useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { createVehicleAttribute, deleteVehicleAttribute, listVehicleAttributes, updateVehicleAttribute } from '../vehicleApi';
import type { VehicleAttribute, VehicleAttributePayload } from '../vehicleTypes';

const dataTypes = ['text', 'number', 'date', 'boolean', 'decimal'];
const emptyPayload: VehicleAttributePayload = { attribute_key: '', attribute_value: '', data_type: 'text', sort_order: 0 };

export function VehicleAttributeTab({ vehicleId }: { vehicleId: number }) {
    const [rows, setRows] = useState<VehicleAttribute[]>([]);
    const [form, setForm] = useState<VehicleAttributePayload>(emptyPayload);
    const [editing, setEditing] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const load = () => {
        const controller = new AbortController();
        setLoading(true);
        listVehicleAttributes(vehicleId, { per_page: 50 }, controller.signal)
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
            if (editing) await updateVehicleAttribute(vehicleId, editing, form);
            else await createVehicleAttribute(vehicleId, form);
            setForm(emptyPayload);
            setEditing(null);
            load();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <LoadingState label="Loading attributes..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} />
            <div className="grid gap-3 md:grid-cols-4">
                <Input label="Key" value={form.attribute_key} onChange={(event) => setForm({ ...form, attribute_key: event.target.value })} error={fieldError(error, 'attribute_key')} />
                <Input label="Value" value={form.attribute_value ?? ''} onChange={(event) => setForm({ ...form, attribute_value: event.target.value })} error={fieldError(error, 'attribute_value')} />
                <Select label="Data Type" value={form.data_type} options={dataTypes.map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, data_type: event.target.value as VehicleAttributePayload['data_type'] })} />
                <Input label="Sort" type="number" min={0} value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: Number(event.target.value) })} error={fieldError(error, 'sort_order')} />
            </div>
            <div className="flex gap-2">
                <Button type="button" loading={submitting} onClick={submit}>{editing ? 'Update Attribute' : 'Add Attribute'}</Button>
                {editing && <Button type="button" variant="secondary" onClick={() => { setEditing(null); setForm(emptyPayload); }}>Cancel</Button>}
            </div>
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'key', header: 'Key', render: (row) => row.attribute_key },
                    { key: 'value', header: 'Value', render: (row) => row.attribute_value ?? '-' },
                    { key: 'type', header: 'Type', render: (row) => row.data_type },
                    { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><Button variant="ghost" onClick={() => { setEditing(row.id); setForm({ attribute_key: row.attribute_key, attribute_value: row.attribute_value ?? '', data_type: row.data_type, sort_order: row.sort_order }); }}>Edit</Button><Button variant="danger" onClick={() => deleteVehicleAttribute(vehicleId, row.id).then(() => load()).catch((requestError) => setError(toApiError(requestError)))}>Delete</Button></div> },
                ]}
            />
        </div>
    );
}
