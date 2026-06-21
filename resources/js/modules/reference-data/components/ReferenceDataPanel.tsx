import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import {
    createReferenceRecord,
    listReferenceRecords,
    setReferenceRecordStatus,
    updateReferenceRecord,
} from '../referenceDataApi';
import type { ReferenceCatalog, ReferenceRecord } from '../referenceDataTypes';

const catalogOptions: Array<{ value: ReferenceCatalog; label: string }> = [
    { value: 'currencies', label: 'Currencies' },
    { value: 'countries', label: 'Countries' },
    { value: 'languages', label: 'Languages' },
    { value: 'timezones', label: 'Timezones' },
];

export function ReferenceDataPanel({ canManage }: { canManage: boolean }) {
    const [catalog, setCatalog] = useState<ReferenceCatalog>('currencies');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<ReferenceRecord | 'create' | null>(null);
    const [working, setWorking] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const records = useApi((signal) => listReferenceRecords(catalog, search, page, signal), [catalog, search, page]);

    const columns: DataColumn<ReferenceRecord>[] = [
        {
            key: 'record',
            header: 'Record',
            render: (row) => (
                <div>
                    <p className="font-semibold text-slate-900">{row.display_name ?? row.name}</p>
                    <p className="text-xs text-slate-500">{row.code ?? row.name}</p>
                </div>
            ),
        },
        { key: 'details', header: 'Details', render: (row) => detailText(row) },
        { key: 'status', header: 'Status', render: (row) => row.is_active ? 'Active' : 'Inactive' },
        {
            key: 'actions',
            header: '',
            render: (row) => canManage ? (
                <div className="flex justify-end gap-2">
                    <Button variant="secondary" onClick={() => setEditing(row)}>Edit</Button>
                    <Button
                        variant={row.is_active ? 'danger' : 'secondary'}
                        loading={working}
                        onClick={() => void changeStatus(row)}
                    >{row.is_active ? 'Deactivate' : 'Activate'}</Button>
                </div>
            ) : null,
        },
    ];

    async function changeStatus(record: ReferenceRecord) {
        setWorking(true);
        setActionError(null);
        try {
            await setReferenceRecordStatus(catalog, record, !record.is_active);
            records.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setWorking(false);
        }
    }

    return (
        <section className="space-y-5">
            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="grid gap-4 lg:grid-cols-[220px_minmax(260px,1fr)_auto] lg:items-end">
                    <Select
                        label="Reference catalog"
                        value={catalog}
                        options={catalogOptions}
                        onChange={(event) => { setCatalog(event.target.value as ReferenceCatalog); setPage(1); }}
                    />
                    <Input
                        label="Search by code or name"
                        value={search}
                        onChange={(event) => { setSearch(event.target.value); setPage(1); }}
                    />
                    {canManage && <Button onClick={() => { setActionError(null); setEditing('create'); }}>Add record</Button>}
                </div>
                <p className="mt-3 text-sm text-slate-500">Codes are stable identifiers. Existing codes cannot be changed; deactivate records that should no longer be selected.</p>
            </div>

            <ErrorAlert error={actionError ?? records.error} />
            {records.loading ? <LoadingState label="Loading reference data..." /> : (
                <DataTable rows={records.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />
            )}
            <Pagination meta={records.data?.meta} onPageChange={setPage} />

            <Modal open={editing !== null} title={editing === 'create' ? `Add ${singular(catalog)}` : `Edit ${singular(catalog)}`} onClose={() => !working && setEditing(null)}>
                {editing && (
                    <ReferenceForm
                        catalog={catalog}
                        record={editing === 'create' ? null : editing}
                        submitting={working}
                        error={actionError}
                        onCancel={() => setEditing(null)}
                        onSubmit={async (payload) => {
                            setWorking(true);
                            setActionError(null);
                            try {
                                if (editing === 'create') await createReferenceRecord(catalog, payload);
                                else await updateReferenceRecord(catalog, editing, payload);
                                setEditing(null);
                                records.reload();
                            } catch (error) {
                                setActionError(toApiError(error));
                            } finally {
                                setWorking(false);
                            }
                        }}
                    />
                )}
            </Modal>
        </section>
    );
}

function ReferenceForm({ catalog, record, submitting, error, onCancel, onSubmit }: {
    catalog: ReferenceCatalog;
    record: ReferenceRecord | null;
    submitting: boolean;
    error: ApiError | null;
    onCancel: () => void;
    onSubmit: (payload: Record<string, unknown>) => Promise<void>;
}) {
    const [code, setCode] = useState(record?.code ?? (catalog === 'timezones' ? record?.name ?? '' : ''));
    const [name, setName] = useState(catalog === 'timezones' ? record?.display_name ?? '' : record?.name ?? '');
    const [extra, setExtra] = useState(extraValue(catalog, record));

    return (
        <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            const payload: Record<string, unknown> = {};
            if (record === null) {
                if (catalog === 'timezones') payload.name = code;
                else payload.code = code;
            }
            if (catalog === 'timezones') payload.display_name = name;
            else payload.name = name;
            if (catalog === 'countries') payload.phone_code = extra || null;
            if (catalog === 'currencies') {
                payload.symbol = extra || null;
                if (record === null) payload.decimal_places = 2;
            }
            if (catalog === 'languages') payload.native_name = extra || null;
            void onSubmit(payload);
        }}>
            <ErrorAlert error={error} />
            <Input
                label={catalog === 'timezones' ? 'IANA timezone' : 'Code'}
                value={code}
                disabled={record !== null}
                required
                hint={catalog === 'timezones' ? 'Example: Asia/Colombo' : 'Codes become permanent after creation.'}
                onChange={(event) => setCode(event.target.value)}
            />
            <Input label={catalog === 'timezones' ? 'Display name' : 'Name'} value={name} required onChange={(event) => setName(event.target.value)} />
            {catalog !== 'timezones' && (
                <Input
                    label={catalog === 'countries' ? 'Phone code' : catalog === 'currencies' ? 'Symbol' : 'Native name'}
                    value={extra}
                    placeholder={catalog === 'countries' ? '+94' : undefined}
                    onChange={(event) => setExtra(event.target.value)}
                />
            )}
            <div className="flex justify-end gap-2">
                <Button variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={submitting}>Save record</Button>
            </div>
        </form>
    );
}

function detailText(record: ReferenceRecord): string {
    if (record.current_utc_offset) return `UTC ${record.current_utc_offset}`;
    if (record.symbol) return `${record.symbol} · ${record.decimal_places ?? 2} decimals`;
    return record.phone_code ?? record.native_name ?? '-';
}

function extraValue(catalog: ReferenceCatalog, record: ReferenceRecord | null): string {
    if (!record) return '';
    if (catalog === 'countries') return record.phone_code ?? '';
    if (catalog === 'currencies') return record.symbol ?? '';
    if (catalog === 'languages') return record.native_name ?? '';
    return '';
}

function singular(catalog: ReferenceCatalog): string {
    return { countries: 'country', currencies: 'currency', languages: 'language', timezones: 'timezone' }[catalog];
}
