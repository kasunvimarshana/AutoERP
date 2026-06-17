import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { FormDrawer } from '@/shared/components/Drawer';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { createItemUsageRule, deleteItemUsageRule, listItemUsageModules, listItemUsageRules, updateItemUsageRule } from '../itemApi';
import type { ItemUsageModule, ItemUsageRule, ItemUsageRulePayload } from '../itemTypes';
import { ItemRelationHeader } from './ItemRelationHeader';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemUsageRules(itemId, { page, per_page: 20 }, signal);

export default function ItemUsageRuleTab({ itemId, readOnly = false }: { itemId: number; readOnly?: boolean }) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemUsageRule, update: updateItemUsageRule, remove: deleteItemUsageRule });
    const columns: DataColumn<ItemUsageRule>[] = [
        { key: 'module', header: 'Module', render: (row) => <span className="font-medium">{row.module_code}</span> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_enabled ? 'enabled' : 'disabled'} /> },
    ];
    if (!readOnly) {
        columns.push({ key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> });
    }
    return <>
        <ItemRelationHeader title="Usage rules" description="Opt the item into module-specific usage without adding workflow fields to the item table." onAdd={readOnly ? undefined : crud.startCreate} />
        <ErrorAlert error={crud.actionError ?? crud.error} />
        {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
        <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        {!readOnly && <FormDrawer open={crud.open} title={crud.editing ? 'Edit usage rule' : 'Add usage rule'} onClose={crud.close}>
            {crud.open && <UsageRuleForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
        </FormDrawer>}
        {!readOnly && crud.confirmDialog}
    </>;
}

function UsageRuleForm({ row, error, submitting, onCancel, onSubmit }: {
    row: ItemUsageRule | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemUsageRulePayload) => Promise<void>;
}) {
    const modules = useApi((signal) => listItemUsageModules(signal), []);
    const [form, setForm] = useState<ItemUsageRulePayload>({
        module_code: row?.module_code ?? '',
        is_enabled: row?.is_enabled ?? true,
    });
    const options = moduleOptions(modules.data);

    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}>
        <ErrorAlert error={error ?? modules.error} />
        <Select
            label="Module"
            value={form.module_code}
            onChange={(event) => setForm({ ...form, module_code: event.target.value })}
            options={options}
            error={fieldError(error, 'module_code')}
            disabled={modules.loading || options.length === 0}
            required
        />
        <label className="block text-sm"><input className="mr-2" type="checkbox" checked={form.is_enabled} onChange={(event) => setForm({ ...form, is_enabled: event.target.checked })} />Enabled</label>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>
    </form>;
}

function moduleOptions(modules: ItemUsageModule[] | null) {
    return (modules ?? []).map((module) => ({
        value: module.code,
        label: module.name,
    }));
}

function Actions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>;
}
