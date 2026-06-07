import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { FormDrawer } from '@/shared/components/Drawer';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { NamedResource } from '@/shared/types/common';
import { createItemUnit, deleteItemUnit, listItemUnits, updateItemUnit } from '../itemApi';
import { itemUnitRoles, type ItemUnit, type ItemUnitPayload } from '../itemTypes';
import { ItemRelationHeader } from './ItemRelationHeader';
import { ItemUomSelect } from './ItemUomSelect';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemUnits(itemId, { page, per_page: 20 }, signal);

export default function ItemUnitTab({ itemId }: { itemId: number }) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemUnit, update: updateItemUnit, remove: deleteItemUnit });
    const columns: DataColumn<ItemUnit>[] = [
        { key: 'uom', header: 'UOM', render: (row) => row.uom ? `${row.uom.code} - ${row.uom.name}${row.uom.symbol ? ` (${row.uom.symbol})` : ''}` : '-' },
        { key: 'role', header: 'Role', render: (row) => row.unit_role },
        { key: 'factor', header: 'Factor', render: (row) => row.conversion_factor },
        { key: 'default', header: 'Default', render: (row) => row.is_default ? 'Yes' : 'No' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <RelationActions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];

    return (
        <>
            <ItemRelationHeader title="Item units" description="Map generic UOMs to item-specific usage roles." onAdd={crud.startCreate} />
            <ErrorAlert error={crud.actionError ?? crud.error} />
            {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
            <FormDrawer open={crud.open} title={crud.editing ? 'Edit item unit' : 'Add item unit'} onClose={crud.close}>
                {crud.open && <UnitForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
            </FormDrawer>
            {crud.confirmDialog}
        </>
    );
}

function UnitForm({ row, error, submitting, onCancel, onSubmit }: {
    row: ItemUnit | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemUnitPayload) => Promise<void>;
}) {
    const [uom, setUom] = useState<NamedResource | null>(row?.uom ?? null);
    const [role, setRole] = useState(row?.unit_role ?? 'base');
    const [factor, setFactor] = useState(row?.conversion_factor ?? '1.000000');
    const [isDefault, setDefault] = useState(row?.is_default ?? false);
    const [isActive, setActive] = useState(row?.is_active ?? true);

    return (
        <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            if (!uom) return;
            void onSubmit({ uom_id: Number(uom.id), unit_role: role, conversion_factor: factor, is_default: isDefault, is_active: isActive });
        }}>
            <ErrorAlert error={error} />
            <ItemUomSelect value={uom} onChange={setUom} error={fieldError(error, 'uom_id')} />
            <Select label="Unit role" value={role} onChange={(event) => setRole(event.target.value)} options={itemUnitRoles.map((value) => ({ value, label: value }))} error={fieldError(error, 'unit_role')} />
            <Input label="Conversion factor" value={factor} onChange={(event) => setFactor(event.target.value)} error={fieldError(error, 'conversion_factor')} required />
            <label className="block text-sm"><input className="mr-2" type="checkbox" checked={isDefault} onChange={(event) => setDefault(event.target.checked)} />Default for this role</label>
            <label className="block text-sm"><input className="mr-2" type="checkbox" checked={isActive} onChange={(event) => setActive(event.target.checked)} />Active</label>
            <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting} disabled={!uom}>Save</Button></div>
        </form>
    );
}

function RelationActions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>;
}
