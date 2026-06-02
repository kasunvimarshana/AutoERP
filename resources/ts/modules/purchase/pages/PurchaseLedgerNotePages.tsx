import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { DataToolbar, type DataToolbarFilterConfig, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseLedgerNote } from '../types/purchase.types';

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

const columns: Array<DataTableColumn<PurchaseLedgerNote>> = [
    { header: 'Source', key: 'sourceReference', render: (row) => <span className="font-semibold text-slate-900">{row.sourceReference}</span> },
    { header: 'Source Type', key: 'sourceType', render: (row) => sourceTypeLabel(row.sourceType) },
    { header: 'Supplier', key: 'supplier' },
    { header: 'Type', key: 'noteType', render: (row) => row.noteType === 'debit' ? 'Debit note' : 'Credit note' },
    { className: 'text-right', header: 'Amount', key: 'amount' },
    { header: 'Status', key: 'status' },
    { header: 'Updated', key: 'updatedAt' },
];

export function PurchaseLedgerNoteListPage() {
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [noteType, setNoteType] = useState('');
    const [debouncedQuery, setDebouncedQuery] = useState('');
    const [query, setQuery] = useState('');
    const [rows, setRows] = useState<PurchaseLedgerNote[]>([]);
    const filterValues = useMemo(() => ({ noteType }), [noteType]);

    useEffect(() => {
        const timeoutId = window.setTimeout(() => setDebouncedQuery(query), 300);
        return () => window.clearTimeout(timeoutId);
    }, [query]);

    useEffect(() => {
        let mounted = true;
        setError('');
        setIsLoading(true);
        purchaseApi.ledgerNotes.list({ noteType, search: debouncedQuery })
            .then((response) => {
                if (mounted) setRows(response.data);
            })
            .catch((caught: unknown) => {
                if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load purchase ledger notes.');
            })
            .finally(() => {
                if (mounted) setIsLoading(false);
            });

        return () => { mounted = false; };
    }, [debouncedQuery, noteType]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'noteType') {
            setNoteType(typeof value === 'string' ? value : '');
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Purchase"
                subtitle="Debit and credit note totals from purchase orders, GRNs, and returns. Amounts are persisted and calculated by the backend source records."
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
                savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists."
                searchPlaceholder="Search PO, GRN, return, or supplier..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Ledger notes unavailable" /> : null}
            {!error && rows.length ? <DataTable columns={columns} getRowKey={(row) => row.id} rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? (
                <EmptyState
                    description="No purchase debit or credit note totals were returned by purchase orders, GRNs, or returns for the current filters."
                    title="No ledger notes"
                />
            ) : null}
        </div>
    );
}

function sourceTypeLabel(sourceType: PurchaseLedgerNote['sourceType']): string {
    if (sourceType === 'purchase_order') return 'Purchase Order';
    if (sourceType === 'grn_header') return 'GRN';
    return 'Purchase Return';
}
