import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { DataToolbar, type DataToolbarFilterConfig, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { salesApi } from '../services/salesApi';
import type { SalesLedgerNote } from '../types/sales.types';

const noteTypeFilters: DataToolbarFilterConfig[] = [
    {
        id: 'noteType',
        label: 'Note type',
        options: [
            { label: 'Debit', value: 'debit' },
            { label: 'Credit', value: 'credit' },
        ],
        type: 'status',
    },
];

const columns: Array<DataTableColumn<SalesLedgerNote>> = [
    { header: 'Source', key: 'sourceReference', render: (row) => <span className="font-semibold text-slate-900">{row.sourceReference}</span> },
    { header: 'Source Type', key: 'sourceType', render: (row) => sourceTypeLabel(row.sourceType) },
    { header: 'Customer', key: 'customer' },
    { header: 'Type', key: 'noteType', render: (row) => row.noteType === 'debit' ? 'Debit note' : 'Credit note' },
    { className: 'text-right', header: 'Amount', key: 'amount' },
    { header: 'Status', key: 'status' },
    { header: 'Updated', key: 'updatedAt' },
];

export function SalesLedgerNoteListPage() {
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [noteType, setNoteType] = useState('');
    const [query, setQuery] = useState('');
    const [rows, setRows] = useState<SalesLedgerNote[]>([]);
    const filterValues = useMemo(() => ({ noteType }), [noteType]);

    useEffect(() => {
        let mounted = true;
        setError('');
        setIsLoading(true);
        salesApi.ledgerNotes.list({ noteType, search: query })
            .then((response) => {
                if (mounted) setRows(response.data);
            })
            .catch((caught: unknown) => {
                if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load sales ledger notes.');
            })
            .finally(() => {
                if (mounted) setIsLoading(false);
            });

        return () => { mounted = false; };
    }, [noteType, query]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'noteType') {
            setNoteType(typeof value === 'string' ? value : '');
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Sales"
                subtitle="Debit and credit note totals from sales orders, deliveries, and returns. Amounts are persisted and calculated by backend source records."
                title="Ledger Notes"
            />
            <DataToolbar
                filterValues={filterValues}
                filters={noteTypeFilters}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setNoteType('')}
                onResetFilters={() => setNoteType('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists."
                searchPlaceholder="Search SO, GDN, return, or customer..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Ledger notes unavailable" /> : null}
            {!error && rows.length ? <DataTable columns={columns} getRowKey={(row) => row.id} rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? (
                <EmptyState
                    description="No sales debit or credit note totals were returned by sales orders, deliveries, or returns for the current filters."
                    title="No ledger notes"
                />
            ) : null}
        </div>
    );
}

function sourceTypeLabel(sourceType: SalesLedgerNote['sourceType']): string {
    if (sourceType === 'sales_order') return 'Sales Order';
    if (sourceType === 'gdn_header') return 'Delivery / GDN';
    return 'Sales Return';
}
