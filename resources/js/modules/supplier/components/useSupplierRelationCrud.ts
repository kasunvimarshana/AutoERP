import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
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
                const saved = editing ? await update(supplierId, editing.id, payload) : await create(supplierId, payload);
                setOpen(false);
                result.setData((current) => upsertCollection(current, saved, !editing && page === 1));
                notifySuccess(editing ? 'Supplier record updated successfully.' : 'Supplier record created successfully.');
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
            try {
                await remove(supplierId, row.id);
                result.setData((current) => removeFromCollection(current, row.id));
                notifySuccess('Supplier record deleted successfully.');
            }
            catch (error) { setActionError(toApiError(error)); }
        },
    };
}

function upsertCollection<T extends { id: number }>(collection: ApiCollection<T> | null, saved: T, prependWhenMissing: boolean) {
    if (collection === null) return collection;
    const rows = collection.data ?? [];
    const currentIndex = rows.findIndex((row) => row.id === saved.id);
    if (currentIndex >= 0) {
        return { ...collection, data: rows.map((row) => row.id === saved.id ? saved : row) };
    }
    if (!prependWhenMissing) return collection;
    const perPage = collection.meta?.per_page ?? rows.length + 1;
    const nextRows = [saved, ...rows].slice(0, perPage);
    return {
        ...collection,
        data: nextRows,
        meta: collection.meta ? { ...collection.meta, total: collection.meta.total + 1, from: 1, to: nextRows.length } : collection.meta,
    };
}

function removeFromCollection<T extends { id: number }>(collection: ApiCollection<T> | null, id: number) {
    if (collection === null) return collection;
    const rows = collection.data ?? [];
    const nextRows = rows.filter((row) => row.id !== id);
    if (nextRows.length === rows.length) return collection;
    return {
        ...collection,
        data: nextRows,
        meta: collection.meta ? { ...collection.meta, total: Math.max(0, collection.meta.total - 1), to: nextRows.length === 0 ? null : nextRows.length } : collection.meta,
    };
}
