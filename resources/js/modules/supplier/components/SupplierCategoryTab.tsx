import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { FormDrawer } from '@/shared/components/Drawer';
import { Pagination } from '@/shared/components/Pagination';
import { useApi } from '@/shared/hooks/useApi';
import { assignSupplierCategory, listSupplierCategories, removeSupplierCategory } from '../supplierApi';
import type { SupplierCategory } from '../supplierTypes';
import { SupplierCategorySelect } from './SupplierCategorySelect';
import { SupplierRelationHeader } from './SupplierRelationHeader';

export default function SupplierCategoryTab({ supplierId, canManage }: { supplierId: number; canManage: boolean }) {
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [category, setCategory] = useState<SupplierCategory | null>(null);
    const [removeTarget, setRemoveTarget] = useState<SupplierCategory | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listSupplierCategories(supplierId, { page, per_page: 20 }, signal), [supplierId, page]);
    const columns: DataColumn<SupplierCategory>[] = [
        { key: 'category', header: 'Category', render: (row) => <>{row.code} - {row.name}</> },
        { key: 'parent', header: 'Parent', render: (row) => row.parent ? `${row.parent.code} - ${row.parent.name}` : '-' },
        ...(canManage ? [{ key: 'actions', header: '', className: 'text-right', render: (row: SupplierCategory) => <button type="button" className="font-semibold text-rose-600" onClick={() => setRemoveTarget(row)}>Remove</button> }] : []),
    ];
    async function remove(row: SupplierCategory) {
        setActionError(null);
        try { await removeSupplierCategory(supplierId, Number(row.id)); setRemoveTarget(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
    }
    async function assign() {
        if (!category) return;
        setSubmitting(true); setActionError(null);
        try { await assignSupplierCategory(supplierId, Number(category.id)); setOpen(false); setCategory(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
        finally { setSubmitting(false); }
    }
    return <><SupplierRelationHeader title="Categories" description="Active supplier classification assignments." onAdd={canManage ? () => { setActionError(null); setOpen(true); } : undefined} addLabel="Assign category" /><ErrorAlert error={actionError ?? result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} />{canManage && <FormDrawer open={open} title="Assign category" onClose={() => !submitting && setOpen(false)}><div className="space-y-4"><ErrorAlert error={actionError} /><SupplierCategorySelect value={category} onChange={setCategory} /><div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button loading={submitting} disabled={!category} onClick={() => void assign()}>Assign</Button></div></div></FormDrawer>}{canManage && <ConfirmDialog open={Boolean(removeTarget)} title="Remove category" message="This category assignment will be removed from the supplier." confirmLabel="Remove" onCancel={() => setRemoveTarget(null)} onConfirm={() => removeTarget && void remove(removeTarget)} />}</>;
}
