import type { ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import type { ApiError } from '@/shared/api/apiError';
import type { ApiCollection } from '@/shared/types/api';
import { humanize, readableRelation } from '@/shared/utils/object';

export function EmployeeRelationTab<T extends { id: number }>({ title, fields, result, open, editing, submitting, actionError, onCreate, onEdit, onDelete, onClose, onSubmit, children }: {
    title: string; fields: string[]; result: { data: ApiCollection<T> | null; loading: boolean; error: ApiError | null; page: number; setPage: (page: number) => void };
    open: boolean; editing: T | null; submitting: boolean; actionError: ApiError | null; onCreate: () => void; onEdit: (row: T) => void; onDelete: (row: T) => void; onClose: () => void; onSubmit: () => void; children: ReactNode;
}) {
    const columns: DataColumn<T>[] = [
        ...fields.map((field) => ({
            key: field,
            header: humanize(field),
            render: (row: T) => renderRelationValue((row as Record<string, unknown>)[field]),
        })),
        { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => <RelationActions edit={() => onEdit(row)} remove={() => onDelete(row)} /> },
    ];

    return <div className="space-y-4">
        <div className="flex justify-end"><Button onClick={onCreate}>Add {title}</Button></div>
        <ErrorAlert error={result.error ?? actionError} />
        {result.loading ? <LoadingState label={`Loading ${title.toLowerCase()}...`} /> : <>
            <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage={`No ${title.toLowerCase()} records yet. Click Add ${title} to start.`} />
            <Pagination meta={result.data?.meta} onPageChange={result.setPage} />
        </>}
        <Modal open={open} title={`${editing ? 'Edit' : 'Add'} ${title}`} onClose={onClose}><div className="space-y-4"><ErrorAlert error={actionError} />{children}<div className="flex justify-end"><Button loading={submitting} onClick={onSubmit}>Save {title.toLowerCase()}</Button></div></div></Modal>
    </div>;
}

function renderRelationValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return readableRelation(value);
    return String(value);
}

function RelationActions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>;
}
