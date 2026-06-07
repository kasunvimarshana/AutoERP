import type { ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { RecordTable } from '@/shared/components/RecordTable';
import type { ApiError } from '@/shared/api/apiError';
import type { ApiCollection } from '@/shared/types/api';

export function EmployeeRelationTab<T extends { id: number }>({ title, fields, result, open, editing, submitting, actionError, onCreate, onEdit, onDelete, onClose, onSubmit, children }: {
    title: string; fields: string[]; result: { data: ApiCollection<T> | null; loading: boolean; error: ApiError | null; page: number; setPage: (page: number) => void };
    open: boolean; editing: T | null; submitting: boolean; actionError: ApiError | null; onCreate: () => void; onEdit: (row: T) => void; onDelete: (row: T) => void; onClose: () => void; onSubmit: () => void; children: ReactNode;
}) {
    return <div className="space-y-4">
        <div className="flex justify-end"><Button onClick={onCreate}>Add {title}</Button></div>
        <ErrorAlert error={result.error ?? actionError} />
        {result.loading ? <LoadingState label={`Loading ${title.toLowerCase()}...`} /> : <><RecordTable rows={(result.data?.data ?? []) as unknown as Record<string, unknown>[]} fields={fields} /><div className="flex flex-wrap gap-2">{(result.data?.data ?? []).map((row) => <div key={row.id} className="flex gap-1"><Button variant="ghost" onClick={() => onEdit(row)}>Edit #{row.id}</Button><Button variant="ghost" onClick={() => onDelete(row)}>Delete</Button></div>)}</div><Pagination meta={result.data?.meta} onPageChange={result.setPage} /></>}
        <Modal open={open} title={`${editing ? 'Edit' : 'Add'} ${title}`} onClose={onClose}><div className="space-y-4"><ErrorAlert error={actionError} />{children}<div className="flex justify-end"><Button loading={submitting} onClick={onSubmit}>Save</Button></div></div></Modal>
    </div>;
}
