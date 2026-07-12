import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
import type { ApiCollection } from '@/shared/types/api';

export function useCustomerRelationCrud<T extends { id: number }, P>({ customerId, list, create, update, remove }: {
    customerId: number;
    list: (customerId: number, page: number, signal: AbortSignal) => Promise<ApiCollection<T>>;
    create: (customerId: number, payload: P) => Promise<T>;
    update: (customerId: number, id: number, payload: P) => Promise<T>;
    remove: (customerId: number, id: number) => Promise<unknown>;
}) {
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<T | null>(null);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => list(customerId, page, signal), [customerId, page]);
    const [collection, setCollection] = useState<ApiCollection<T> | null>(result.data);

    useEffect(() => {
        if (result.data !== null) {
            setCollection(result.data);
        }
    }, [result.data]);

    return {
        ...result, data: collection, page, setPage, editing, open, submitting, actionError, confirmDialog,
        startCreate: () => { setEditing(null); setActionError(null); setOpen(true); },
        startEdit: (row: T) => { setEditing(row); setActionError(null); setOpen(true); },
        close: () => { if (!submitting) setOpen(false); },
        submit: async (payload: P) => {
            setSubmitting(true); setActionError(null);
            try {
                const saved = editing ? await update(customerId, editing.id, payload) : await create(customerId, payload);
                setOpen(false);
                setCollection((current) => upsertCollection(current, saved, !editing && page === 1));
                notifySuccess(editing ? 'Customer record updated successfully.' : 'Customer record created successfully.');
            } catch (error) {
                setActionError(toApiError(error));
            } finally {
                setSubmitting(false);
            }
        },
        destroy: async (row: T) => {
            const confirmed = await confirm({ title: 'Delete customer relation', message: 'This customer relation record will be permanently deleted.', confirmLabel: 'Delete' });
            if (!confirmed) return;
            setActionError(null);
            try {
                await remove(customerId, row.id);
                setCollection((current) => removeFromCollection(current, row.id));
                notifySuccess('Customer record deleted successfully.');
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
