import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { useApi } from '@/shared/hooks/useApi';
import { assignSupplierCategory, listSupplierCategories, removeSupplierCategory } from '../supplierApi';
import type { SupplierCategory } from '../supplierTypes';
import { SupplierCategorySelect } from './SupplierCategorySelect';
import { SupplierRelationHeader } from './SupplierRelationHeader';

export default function SupplierCategoryTab({ supplierId }: { supplierId: number }) {
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [category, setCategory] = useState<SupplierCategory | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listSupplierCategories(supplierId, { page, per_page: 20 }, signal), [supplierId, page]);
    const columns: DataColumn<SupplierCategory>[] = [
        { key: 'category', header: 'Category', render: (row) => <>{row.code} - {row.name}</> },
        { key: 'parent', header: 'Parent', render: (row) => row.parent ? `${row.parent.code} - ${row.parent.name}` : '-' },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <button className="font-semibold text-rose-600" onClick={() => void remove(row)}>Remove</button> },
    ];
    async function remove(row: SupplierCategory) {
        if (!window.confirm('Remove this supplier category?')) return;
        setActionError(null);
        try { await removeSupplierCategory(supplierId, Number(row.id)); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
    }
    async function assign() {
        if (!category) return;
        setSubmitting(true); setActionError(null);
        try { await assignSupplierCategory(supplierId, Number(category.id)); setOpen(false); setCategory(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
        finally { setSubmitting(false); }
    }
    return <><SupplierRelationHeader title="Categories" description="Active supplier classification assignments." onAdd={() => { setActionError(null); setOpen(true); }} addLabel="Assign category" /><ErrorAlert error={actionError ?? result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} /><Modal open={open} title="Assign category" onClose={() => !submitting && setOpen(false)}><div className="space-y-4"><ErrorAlert error={actionError} /><SupplierCategorySelect value={category} onChange={setCategory} /><div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button loading={submitting} disabled={!category} onClick={() => void assign()}>Assign</Button></div></div></Modal></>;
}
