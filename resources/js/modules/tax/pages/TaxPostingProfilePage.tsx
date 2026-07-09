import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getTaxLookups, listTaxPostingProfiles, saveTaxPostingProfile } from '../taxApi';
import { taxPermissions } from '../taxPermissions';
import type { TaxPostingProfile } from '../taxTypes';

export default function TaxPostingProfilePage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, taxPermissions.postingProfilesManage);
    const [page, setPage] = useState(1);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [form, setForm] = useState({ tax_id: '', direction: 'tax', posting_key: '', active: true });
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const profiles = useApi((signal) => listTaxPostingProfiles({ page, per_page: 25 }, signal), [page, refresh]);
    const lookups = useApi((signal) => getTaxLookups(signal), []);

    const columns: DataColumn<TaxPostingProfile>[] = [
        { key: 'tax', header: 'Tax', render: (row) => row.tax ? `${row.tax.code ?? ''} ${row.tax.name}` : '-' },
        { key: 'direction', header: 'Direction', render: (row) => row.direction },
        { key: 'posting_key', header: 'Finance posting key', render: (row) => row.posting_key },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.active ? 'active' : 'inactive'} /> },
        ...(canManage ? [{ key: 'edit', header: '', className: 'text-right', render: (row: TaxPostingProfile) => <button type="button" className="text-sm font-semibold text-sky-700" onClick={() => { setSelectedId(row.id); setForm({ tax_id: String(row.tax_id), direction: row.direction, posting_key: row.posting_key, active: row.active }); }}>Edit</button> }] : []),
    ];

    return (
        <>
            <ContentHeader title="Tax posting profiles" description="Map each tax direction to a Finance posting key. Tax calculates; Finance resolves accounts." />
            <ErrorAlert error={error ?? profiles.error ?? lookups.error} />
            <div className={canManage ? 'grid gap-4 xl:grid-cols-[1fr_420px]' : ''}>
                <div>
                    {profiles.loading ? <LoadingState /> : <DataTable rows={profiles.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
                    <Pagination meta={profiles.data?.meta} onPageChange={setPage} />
                </div>
                {canManage && (
                    <Panel title={selectedId ? 'Edit mapping' : 'Create mapping'}>
                        <div className="space-y-3">
                            <Select label="Tax" value={form.tax_id} error={fieldError(error, 'tax_id')} options={(lookups.data?.taxes ?? []).map((tax) => ({ value: tax.id, label: `${tax.code ?? ''} - ${tax.name}` }))} onChange={(event) => setForm({ ...form, tax_id: event.target.value })} />
                            <Select label="Direction" value={form.direction} error={fieldError(error, 'direction')} options={(lookups.data?.posting_directions ?? ['tax']).map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, direction: event.target.value })} />
                            <Select label="Finance posting key" value={form.posting_key} error={fieldError(error, 'posting_key')} options={(lookups.data?.posting_keys ?? []).map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, posting_key: event.target.value })} />
                            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.active} onChange={(event) => setForm({ ...form, active: event.target.checked })} /> Active</label>
                            <Button onClick={async () => {
                                setError(null);
                                try {
                                    await saveTaxPostingProfile(selectedId, {
                                        tax_id: Number(form.tax_id),
                                        direction: form.direction,
                                        posting_key: form.posting_key,
                                        active: form.active,
                                    });
                                    setSelectedId(null);
                                    setForm({ tax_id: '', direction: 'tax', posting_key: '', active: true });
                                    setRefresh((value) => value + 1);
                                } catch (requestError) {
                                    setError(toApiError(requestError));
                                }
                            }}>{selectedId ? 'Update mapping' : 'Create mapping'}</Button>
                        </div>
                    </Panel>
                )}
            </div>
        </>
    );
}
