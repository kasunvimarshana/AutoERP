import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useApi } from '@/shared/hooks/useApi';
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
    const result = useApi((signal) => list(customerId, page, signal), [customerId, page]);

    return {
        ...result, page, setPage, editing, open, submitting, actionError,
        startCreate: () => { setEditing(null); setActionError(null); setOpen(true); },
        startEdit: (row: T) => { setEditing(row); setActionError(null); setOpen(true); },
        close: () => { if (!submitting) setOpen(false); },
        submit: async (payload: P) => {
            setSubmitting(true); setActionError(null);
            try {
                if (editing) await update(customerId, editing.id, payload);
                else await create(customerId, payload);
                setOpen(false); result.reload();
            } catch (error) {
                setActionError(toApiError(error));
            } finally {
                setSubmitting(false);
            }
        },
        destroy: async (row: T) => {
            if (!window.confirm('Delete this customer relation record?')) return;
            setActionError(null);
            try { await remove(customerId, row.id); result.reload(); }
            catch (error) { setActionError(toApiError(error)); }
        },
    };
}
