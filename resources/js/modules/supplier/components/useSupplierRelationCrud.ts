import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import type { ApiCollection } from '@/shared/types/api';

export function useSupplierRelationCrud<T extends { id: number }, P>({ supplierId, list, create, update, remove }: {
    supplierId: number;
    list: (supplierId: number, page: number, signal: AbortSignal) => Promise<ApiCollection<T>>;
    create: (supplierId: number, payload: P) => Promise<T>;
    update: (supplierId: number, id: number, payload: P) => Promise<T>;
    remove: (supplierId: number, id: number) => Promise<unknown>;
}) {
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<T | null>(null);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => list(supplierId, page, signal), [supplierId, page]);

    return {
        ...result, page, setPage, editing, open, submitting, actionError, confirmDialog,
        startCreate: () => { setEditing(null); setActionError(null); setOpen(true); },
        startEdit: (row: T) => { setEditing(row); setActionError(null); setOpen(true); },
        close: () => { if (!submitting) setOpen(false); },
        submit: async (payload: P) => {
            setSubmitting(true); setActionError(null);
            try {
                if (editing) await update(supplierId, editing.id, payload);
                else await create(supplierId, payload);
                setOpen(false); result.reload();
            } catch (error) {
                setActionError(toApiError(error));
            } finally {
                setSubmitting(false);
            }
        },
        destroy: async (row: T) => {
            const confirmed = await confirm({ title: 'Delete supplier relation', message: 'This supplier relation record will be permanently deleted.', confirmLabel: 'Delete' });
            if (!confirmed) return;
            setActionError(null);
            try { await remove(supplierId, row.id); result.reload(); }
            catch (error) { setActionError(toApiError(error)); }
        },
    };
}
