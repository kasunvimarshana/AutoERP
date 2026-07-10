import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import type { ApiCollection } from '@/shared/types/api';

const UPDATE_NOT_SUPPORTED_MESSAGE = 'Employee relation update is not supported.';
const DELETE_NOT_SUPPORTED_MESSAGE = 'Employee relation delete is not supported.';

export function useEmployeeRelationCrud<T extends { id: number }, P>(employeeId: number, api: {
    list: (employeeId: number, page: number, signal: AbortSignal) => Promise<ApiCollection<T>>;
    create: (employeeId: number, payload: P) => Promise<T>;
    update?: (employeeId: number, id: number, payload: P) => Promise<T>;
    remove?: (employeeId: number, id: number) => Promise<unknown>;
}) {
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<T | null>(null);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => api.list(employeeId, page, signal), [employeeId, page]);
    return {
        ...result, page, setPage, editing, open, submitting, actionError, confirmDialog,
        startCreate: () => { setEditing(null); setActionError(null); setOpen(true); },
        startEdit: api.update ? (row: T) => { setEditing(row); setActionError(null); setOpen(true); } : undefined,
        close: () => { if (!submitting) setOpen(false); },
        submit: async (payload: P) => { setSubmitting(true); setActionError(null); try { if (editing) { if (!api.update) throw new Error(UPDATE_NOT_SUPPORTED_MESSAGE); await api.update(employeeId, editing.id, payload); } else await api.create(employeeId, payload); setOpen(false); result.reload(); } catch (error) { setActionError(toApiError(error)); } finally { setSubmitting(false); } },
        destroy: api.remove ? async (row: T) => { const confirmed = await confirm({ title: 'Delete employee record', message: 'This employee relation record will be permanently deleted.', confirmLabel: 'Delete' }); if (!confirmed) return; try { await api.remove?.(employeeId, row.id); result.reload(); } catch (error) { setActionError(toApiError(error)); } } : async () => { setActionError(toApiError(new Error(DELETE_NOT_SUPPORTED_MESSAGE))); },
    };
}
