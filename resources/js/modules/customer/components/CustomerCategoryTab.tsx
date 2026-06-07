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
import { assignCustomerCategory, listCustomerCategories, removeCustomerCategory } from '../customerApi';
import type { CustomerCategory } from '../customerTypes';
import { CustomerCategorySelect } from './CustomerCategorySelect';
import { CustomerRelationHeader } from './CustomerRelationHeader';

export default function CustomerCategoryTab({ customerId }: { customerId: number }) {
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [category, setCategory] = useState<CustomerCategory | null>(null);
    const [removeTarget, setRemoveTarget] = useState<CustomerCategory | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listCustomerCategories(customerId, { page, per_page: 20 }, signal), [customerId, page]);
    const columns: DataColumn<CustomerCategory>[] = [
        { key: 'category', header: 'Category', render: (row) => <>{row.code} - {row.name}</> },
        { key: 'parent', header: 'Parent', render: (row) => row.parent ? `${row.parent.code} - ${row.parent.name}` : '-' },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <button type="button" className="font-semibold text-rose-600" onClick={() => setRemoveTarget(row)}>Remove</button> },
    ];
    async function remove(row: CustomerCategory) {
        setActionError(null);
        try { await removeCustomerCategory(customerId, Number(row.id)); setRemoveTarget(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
    }
    async function assign() {
        if (!category) return;
        setSubmitting(true); setActionError(null);
        try { await assignCustomerCategory(customerId, Number(category.id)); setOpen(false); setCategory(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
        finally { setSubmitting(false); }
    }
    return <><CustomerRelationHeader title="Categories" description="Active customer classification assignments." onAdd={() => { setActionError(null); setOpen(true); }} addLabel="Assign category" /><ErrorAlert error={actionError ?? result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} /><FormDrawer open={open} title="Assign category" onClose={() => !submitting && setOpen(false)}><div className="space-y-4"><ErrorAlert error={actionError} /><CustomerCategorySelect value={category} onChange={setCategory} /><div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button loading={submitting} disabled={!category} onClick={() => void assign()}>Assign</Button></div></div></FormDrawer><ConfirmDialog open={Boolean(removeTarget)} title="Remove category" message="This category assignment will be removed from the customer." confirmLabel="Remove" onCancel={() => setRemoveTarget(null)} onConfirm={() => removeTarget && void remove(removeTarget)} /></>;
}
