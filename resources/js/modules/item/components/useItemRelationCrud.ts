import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
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
    remove: (itemId: number, id: number) => Promise<unknown>;
}) {
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<T | null>(null);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => list(itemId, page, signal), [itemId, page]);

    return {
        ...result,
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
                if (editing) await update(itemId, editing.id, payload);
                else await create(itemId, payload);
                setOpen(false);
                result.reload();
            } catch (error) {
                setActionError(toApiError(error));
            } finally {
                setSubmitting(false);
            }
        },
        destroy: async (row: T) => {
            const confirmed = await confirm({ title: 'Delete relation record', message: 'This relation record will be permanently deleted.', confirmLabel: 'Delete' });
            if (!confirmed) return;
            setActionError(null);
            try {
                await remove(itemId, row.id);
                result.reload();
            } catch (error) {
                setActionError(toApiError(error));
            }
        },
    };
}
