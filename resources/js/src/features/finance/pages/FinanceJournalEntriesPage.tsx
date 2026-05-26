import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { Button } from '../../../components/ui/Button';
import { useTenant } from '../../auth/context/TenantContext';
import { useJournalEntries } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import type { JournalEntryLineRecord, JournalEntryRecord } from '../types';

export function FinanceJournalEntriesPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const entryNumber = searchParams.get('entry_number') ?? '';
    const status = searchParams.get('status') ?? '';
    const selectedEntryId = parsePositiveInteger(searchParams.get('entry_id'), 0);

    const journalEntriesQuery = useJournalEntries({
        tenant_id: tenantId,
        page,
        per_page: 10,
        entry_number: entryNumber || undefined,
        status: status || undefined,
        sort: '-entry_date',
    });

    const activeEntryId = selectedEntryId || journalEntriesQuery.data?.items[0]?.id || 0;
    const activeEntry = journalEntriesQuery.data?.items.find((entry) => entry.id === activeEntryId) ?? null;

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('entry_number' in updates || 'status' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const entryColumns: DataTableColumn<JournalEntryRecord>[] = useMemo(
        () => [
            {
                key: 'entry_number',
                header: 'Entry',
                render: (entry) => (
                    <div>
                        <p className="font-medium text-stone-950">{entry.entry_number}</p>
                        <p className="mt-1 text-xs text-stone-500">{entry.description || entry.reference_type || 'No description'}</p>
                    </div>
                ),
            },
            { key: 'entry_type', header: 'Type', render: (entry) => <StatusBadge>{entry.entry_type}</StatusBadge> },
            { key: 'status', header: 'Status', render: (entry) => <StatusBadge tone={entry.status === 'posted' ? 'success' : 'default'}>{entry.status}</StatusBadge> },
            { key: 'entry_date', header: 'Entry Date', render: (entry) => <span className="text-sm text-stone-700">{formatDate(entry.entry_date)}</span> },
            { key: 'lines', header: 'Lines', render: (entry) => <span className="text-sm text-stone-700">{entry.lines.length}</span> },
            {
                key: 'detail',
                header: 'Detail',
                className: 'w-[8rem]',
                render: (entry) => (
                    <Button className="h-9 px-3 text-xs" onClick={() => updateParams({ entry_id: entry.id })} type="button" variant="secondary">
                        Inspect
                    </Button>
                ),
            },
        ],
        [],
    );

    const lineColumns: DataTableColumn<JournalEntryLineRecord>[] = useMemo(
        () => [
            { key: 'account_id', header: 'Account', render: (line) => <span className="text-sm text-stone-700">{line.account_id}</span> },
            { key: 'description', header: 'Description', render: (line) => <span className="text-sm text-stone-700">{line.description || '-'}</span> },
            { key: 'debit_amount', header: 'Debit', render: (line) => <span className="text-sm text-stone-700">{formatCurrency(line.base_debit_amount)}</span> },
            { key: 'credit_amount', header: 'Credit', render: (line) => <span className="text-sm text-stone-700">{formatCurrency(line.base_credit_amount)}</span> },
            { key: 'cost_center_id', header: 'Cost Center', render: (line) => <span className="text-sm text-stone-700">{line.cost_center_id ?? '-'}</span> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Finance' }, { label: 'Journal Entries' }]}
                description="Journal entry listing and line inspection are now connected to the finance APIs, replacing the unfinished Phase 5 placeholder route."
                title="Journal Entries"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Filter entries by document number or posting status, then inspect the line distribution below." title="Journal register">
                    <SearchFilterToolbar
                        filters={
                            <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                <option value="">All statuses</option>
                                <option value="draft">Draft</option>
                                <option value="posted">Posted</option>
                                <option value="reversed">Reversed</option>
                            </Select>
                        }
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ entry_number: event.target.value || undefined })} placeholder="Search entry number" value={entryNumber} />}
                        trailing={<div className="text-sm text-stone-500">{journalEntriesQuery.data?.meta?.total ?? 0} journal entries</div>}
                    />
                </TableToolbar>

                {journalEntriesQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : journalEntriesQuery.isError ? (
                    isForbiddenError(journalEntriesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={journalEntriesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={journalEntriesQuery.error.message} title="Unable to load journal entries" />
                    )
                ) : (
                    <DataTable
                        columns={entryColumns}
                        emptyState={<EmptyState className="m-6" description="No journal entries match the current filters." title="No journal entries found" />}
                        footer={<TablePagination meta={journalEntriesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(entry) => entry.id}
                        rows={journalEntriesQuery.data.items}
                    />
                )}
            </ContentCard>

            <ContentCard className="p-0">
                <TableToolbar
                    description={activeEntry ? `Inspecting ${activeEntry.entry_number}. The table below uses the line payload already included in the journal entry resource.` : 'Select an entry above to inspect its lines.'}
                    title="Entry lines"
                />

                {!activeEntry ? (
                    <EmptyState className="m-6" description="Select a journal entry from the table above to inspect debits and credits." title="No journal entry selected" />
                ) : (
                    <DataTable
                        columns={lineColumns}
                        emptyState={<EmptyState className="m-6" description="This journal entry does not include line rows." title="No lines found" />}
                        getRowKey={(line) => line.id}
                        rows={activeEntry.lines}
                    />
                )}
            </ContentCard>
        </div>
    );
}
