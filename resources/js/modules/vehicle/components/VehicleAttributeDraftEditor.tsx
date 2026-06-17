import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { VehicleAttributePayload } from '../vehicleTypes';

const dataTypes = ['text', 'number', 'date', 'boolean', 'decimal'];
const emptyAttribute: VehicleAttributePayload = { attribute_key: '', attribute_value: '', data_type: 'text', sort_order: 0 };

export function VehicleAttributeDraftEditor({
    attributes,
    onChange,
    error,
}: {
    attributes: VehicleAttributePayload[];
    onChange: (attributes: VehicleAttributePayload[]) => void;
    error: ApiError | null;
}) {
    const [draft, setDraft] = useState<VehicleAttributePayload>(emptyAttribute);

    const addAttribute = () => {
        onChange([...attributes, { ...draft, sort_order: draft.sort_order ?? attributes.length }]);
        setDraft({ ...emptyAttribute, sort_order: attributes.length + 1 });
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-3 md:grid-cols-4">
                <Input label="Key" value={draft.attribute_key} onChange={(event) => setDraft({ ...draft, attribute_key: event.target.value })} error={fieldError(error, 'attributes.0.attribute_key')} />
                <Input label="Value" value={draft.attribute_value ?? ''} onChange={(event) => setDraft({ ...draft, attribute_value: event.target.value })} error={fieldError(error, 'attributes.0.attribute_value')} />
                <Select label="Data Type" value={draft.data_type} options={dataTypes.map((value) => ({ value, label: value }))} onChange={(event) => setDraft({ ...draft, data_type: event.target.value as VehicleAttributePayload['data_type'] })} />
                <Input label="Sort" type="number" min={0} value={draft.sort_order} onChange={(event) => setDraft({ ...draft, sort_order: Number(event.target.value) })} error={fieldError(error, 'attributes.0.sort_order')} />
            </div>
            <Button type="button" variant="secondary" onClick={addAttribute}>Add Attribute</Button>
            <DataTable
                rows={attributes.map((attribute, index) => ({ ...attribute, id: index }))}
                rowKey={(row) => row.id}
                columns={[
                    { key: 'key', header: 'Key', render: (row) => row.attribute_key },
                    { key: 'value', header: 'Value', render: (row) => row.attribute_value ?? '-' },
                    { key: 'type', header: 'Type', render: (row) => row.data_type },
                    { key: 'actions', header: '', render: (row) => <div className="flex justify-end"><Button type="button" variant="ghost" onClick={() => onChange(attributes.filter((_, index) => index !== row.id))}>Remove</Button></div> },
                ]}
            />
        </div>
    );
}
