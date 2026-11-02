import { useState } from 'react';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { ApiCollection } from '@/shared/types/api';
import { EmployeeRelationTab } from './EmployeeRelationTab';
import { useEmployeeRelationCrud } from './useEmployeeRelationCrud';

type Field = {
    key: string;
    label: string;
    type?: 'text' | 'date' | 'checkbox' | 'select';
    options?: string[];
};

export function SimpleEmployeeRelationTab<T extends { id: number }, P extends object>({
    employeeId,
    title,
    api,
    fields,
    defaults,
    tableFields,
}: {
    employeeId: number;
    title: string;
    fields: Field[];
    defaults: P;
    tableFields: string[];
    api: {
        list: (id: number, page: number, signal: AbortSignal) => Promise<ApiCollection<T>>;
        create: (id: number, payload: P) => Promise<T>;
        update: (id: number, rowId: number, payload: P) => Promise<T>;
        remove: (id: number, rowId: number) => Promise<unknown>;
    };
}) {
    const crud = useEmployeeRelationCrud<T, P>(employeeId, api);
    const [draft, setDraft] = useState<P>(defaults);

    const startCreate = () => {
        setDraft({ ...defaults });
        crud.startCreate();
    };

    const startEdit = (row: T) => {
        const rowValues = row as Record<string, unknown>;
        const nextDraft = { ...defaults } as Record<string, unknown>;
        for (const field of fields) {
            if (field.key in rowValues) nextDraft[field.key] = rowValues[field.key];
        }
        setDraft(nextDraft as P);
        crud.startEdit(row);
    };

    const values = draft as Record<string, unknown>;

    return (
        <EmployeeRelationTab
            title={title}
            fields={tableFields}
            result={crud}
            open={crud.open}
            editing={crud.editing}
            submitting={crud.submitting}
            actionError={crud.actionError}
            onCreate={startCreate}
            onEdit={startEdit}
            onDelete={crud.destroy}
            onClose={crud.close}
            onSubmit={() => void crud.submit(draft)}
        >
            <div className="grid gap-3 md:grid-cols-2">
                {fields.map((field) => field.type === 'checkbox'
                    ? (
                        <label key={field.key} className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={Boolean(values[field.key])}
                                onChange={(event) => setDraft({ ...draft, [field.key]: event.target.checked })}
                            />
                            {field.label}
                        </label>
                    )
                    : field.type === 'select'
                        ? (
                            <Select
                                key={field.key}
                                label={field.label}
                                value={String(values[field.key] ?? '')}
                                options={(field.options ?? []).map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                                onChange={(event) => setDraft({ ...draft, [field.key]: event.target.value })}
                            />
                        )
                        : (
                            <Input
                                key={field.key}
                                label={field.label}
                                type={field.type === 'date' ? 'date' : 'text'}
                                value={String(values[field.key] ?? '')}
                                onChange={(event) => setDraft({ ...draft, [field.key]: event.target.value })}
                            />
                        ))}
            </div>
        </EmployeeRelationTab>
    );
}
