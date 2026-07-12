import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
import type { ApiCollection } from '@/shared/types/api';

export function useItemRelationCrud<T extends { id: number }, P>({
    itemId,
    list,
    create,
    update,
    remove,
}: {
    itemId: number;
    list: (itemId: number, page: number, signal: AbortSignal) => Promise<ApiCollection<T>>;
    create: (itemId: number, payload: P) => Promise<T>;
    update: (itemId: number, id: number, payload: P) => Promise<T>;
    remove?: (itemId: number, id: number) => Promise<unknown>;
}) {
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<T | null>(null);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => list(itemId, page, signal), [itemId, page]);
    const [collection, setCollection] = useState<ApiCollection<T> | null>(result.data);

    useEffect(() => {
        if (result.data !== null) {
            setCollection(result.data);
        }
    }, [result.data]);

    return {
        ...result,
        data: collection,
        page,
        setPage,
        editing,
        open,
        submitting,
        actionError,
        confirmDialog,
        startCreate: () => {
            setEditing(null);
            setActionError(null);
            setOpen(true);
        },
        startEdit: (row: T) => {
            setEditing(row);
            setActionError(null);
            setOpen(true);
        },
        close: () => {
            if (!submitting) setOpen(false);
        },
        submit: async (payload: P) => {
            setSubmitting(true);
            setActionError(null);
            try {
                const saved = editing
                    ? await update(itemId, editing.id, payload)
                    : await create(itemId, payload);
                setOpen(false);
                setEditing(null);
                setCollection((current) => upsertRelationCollection(current, saved, !editing && page === 1));
                notifySuccess(editing ? 'Record updated successfully.' : 'Record created successfully.');
            } catch (error) {
                setActionError(toApiError(error));
            } finally {
                setSubmitting(false);
            }
        },
        destroy: async (row: T) => {
            if (!remove) return;
            const confirmed = await confirm({ title: 'Delete relation record', message: 'This relation record will be permanently deleted.', confirmLabel: 'Delete' });
            if (!confirmed) return;
            setActionError(null);
            try {
                await remove(itemId, row.id);
                setCollection((current) => removeRelationFromCollection(current, row.id));
                notifySuccess('Record deleted successfully.');
            } catch (error) {
                setActionError(toApiError(error));
            }
        },
    };
}

function upsertRelationCollection<T extends { id: number }>(
    collection: ApiCollection<T> | null,
    saved: T,
    prependWhenMissing: boolean,
) {
    if (collection === null) return collection;

    const rows = collection.data ?? [];
    const currentIndex = rows.findIndex((row) => row.id === saved.id);

    if (currentIndex >= 0) {
        return {
            ...collection,
            data: rows.map((row) => row.id === saved.id ? saved : row),
        };
    }

    if (!prependWhenMissing) {
        return collection;
    }

    const perPage = collection.meta?.per_page ?? rows.length + 1;
    const nextRows = [saved, ...rows].slice(0, perPage);

    return {
        ...collection,
        data: nextRows,
        meta: collection.meta ? {
            ...collection.meta,
            total: collection.meta.total + 1,
            from: nextRows.length > 0 ? 1 : null,
            to: nextRows.length === 0 ? null : nextRows.length,
        } : collection.meta,
    };
}

function removeRelationFromCollection<T extends { id: number }>(
    collection: ApiCollection<T> | null,
    id: number,
) {
    if (collection === null) return collection;

    const rows = collection.data ?? [];
    const nextRows = rows.filter((row) => row.id !== id);
    if (nextRows.length === rows.length) return collection;

    return {
        ...collection,
        data: nextRows,
        meta: collection.meta ? {
            ...collection.meta,
            total: Math.max(0, collection.meta.total - 1),
            to: nextRows.length === 0 ? null : nextRows.length,
        } : collection.meta,
    };
}
