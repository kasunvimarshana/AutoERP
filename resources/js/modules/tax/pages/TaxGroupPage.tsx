import { useState } from 'react';
import { Link } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getTaxLookups, listTaxGroups, saveTaxGroup } from '../taxApi';
import type { TaxGroup, TaxGroupLine } from '../taxTypes';

type GroupForm = Omit<TaxGroup, 'id'>;

const blank: GroupForm = { code: '', name: '', is_default: false, active: true, lines: [] };

export default function TaxGroupPage() {
    const [page, setPage] = useState(1);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [form, setForm] = useState<GroupForm>(blank);
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const groups = useApi((signal) => listTaxGroups({ page, per_page: 25 }, signal), [page, refresh]);
    const lookups = useApi((signal) => getTaxLookups(signal), []);
    const taxOptions = (lookups.data?.taxes ?? []).map((tax) => ({ value: tax.id, label: `${tax.code} - ${tax.name}` }));

    const columns: DataColumn<TaxGroup>[] = [
        { key: 'group', header: 'Group', render: (row) => <button type="button" className="text-left font-semibold text-sky-700 hover:underline" onClick={() => { setSelectedId(row.id); setForm({ code: row.code, name: row.name, is_default: row.is_default, active: row.active, lines: row.lines ?? [] }); }}>{row.code}<span className="block text-xs font-normal text-slate-500">{row.name}</span></button> },
        { key: 'default', header: 'Default', render: (row) => row.is_default ? 'Yes' : 'No' },
        { key: 'lines', header: 'Taxes', render: (row) => (row.lines ?? []).map((line) => line.tax ? `${line.sequence}. ${line.tax.code}` : `${line.sequence}. Tax`).join(', ') || '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.active ? 'active' : 'inactive'} /> },
    ];

    const setLine = (index: number, patch: Partial<TaxGroupLine>) => {
        const lines = [...(form.lines ?? [])];
        lines[index] = { ...(lines[index] ?? { tax_id: 0, sequence: index + 1, active: true }), ...patch };
        setForm({ ...form, lines });
    };

    return (
        <>
            <ContentHeader title="Tax groups" description="Sequence-driven reusable tax bundles for item, party, and document determination." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/tax/taxes">Taxes</Link>} />
            <ErrorAlert error={error ?? groups.error ?? lookups.error} />
            <div className="grid gap-4 xl:grid-cols-[1fr_420px]">
                <div>
                    {groups.loading ? <LoadingState /> : <DataTable rows={groups.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
                    <Pagination meta={groups.data?.meta} onPageChange={setPage} />
                </div>
                <Panel title={selectedId ? 'Edit tax group' : 'Create tax group'}>
                    <div className="space-y-3">
                        <Input label="Code" value={form.code} error={fieldError(error, 'code')} onChange={(event) => setForm({ ...form, code: event.target.value })} />
                        <Input label="Name" value={form.name} error={fieldError(error, 'name')} onChange={(event) => setForm({ ...form, name: event.target.value })} />
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_default} onChange={(event) => setForm({ ...form, is_default: event.target.checked })} /> System default for this scope</label>
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.active} onChange={(event) => setForm({ ...form, active: event.target.checked })} /> Active</label>
                        <div className="space-y-2">
                            {(form.lines ?? []).map((line, index) => (
                                <div key={index} className="grid grid-cols-[1fr_80px_32px] gap-2">
                                    <Select value={line.tax_id || ''} options={taxOptions} placeholder="Tax" onChange={(event) => setLine(index, { tax_id: Number(event.target.value) })} />
                                    <Input value={String(line.sequence)} onChange={(event) => setLine(index, { sequence: Number(event.target.value) })} />
                                    <button type="button" className="text-rose-600" onClick={() => setForm({ ...form, lines: (form.lines ?? []).filter((_, lineIndex) => lineIndex !== index) })}>x</button>
                                </div>
                            ))}
                        </div>
                        <Button variant="secondary" onClick={() => setForm({ ...form, lines: [...(form.lines ?? []), { tax_id: 0, sequence: (form.lines?.length ?? 0) + 1, active: true }] })}>Add tax line</Button>
                        <div className="flex gap-3">
                            <Button onClick={async () => {
                                setError(null);
                                try {
                                    await saveTaxGroup(selectedId, form);
                                    setForm(blank);
                                    setSelectedId(null);
                                    setRefresh((value) => value + 1);
                                } catch (requestError) {
                                    setError(toApiError(requestError));
                                }
                            }}>{selectedId ? 'Update group' : 'Create group'}</Button>
                            {selectedId && <Button variant="ghost" onClick={() => { setSelectedId(null); setForm(blank); }}>New</Button>}
                        </div>
                    </div>
                </Panel>
            </div>
        </>
    );
}
