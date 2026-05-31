import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { DocumentRecordTable } from '../components/DocumentComponents';
import { documentApi } from '../services/documentApi';
import type { DocumentRecord } from '../types/document.types';

export function DocumentRecordListPage() {
    const [records, setRecords] = useState<DocumentRecord[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [sourceModule, setSourceModule] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.listDocuments()
            .then((response) => { if (mounted) setRecords(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load documents.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleRecords = useMemo(() => {
        const q = query.trim().toLowerCase();
        return records.filter((record) => {
            const matchesQuery = q ? [record.documentNumber, record.title, record.typeName, record.sourceReference].some((value) => value.toLowerCase().includes(q)) : true;
            return matchesQuery && (!status || record.status === status) && (!sourceModule || record.sourceModule === sourceModule);
        });
    }, [query, records, sourceModule, status]);

    const sourceModuleOptions = useMemo(() => {
        const modules = Array.from(new Set(records.map((record) => record.sourceModule).filter(Boolean))).sort();
        return modules.map((module) => ({ label: module, value: module }));
    }, [records]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        const next = typeof value === 'string' ? value : '';
        if (filterId === 'sourceModule') setSourceModule(next);
        if (filterId === 'status') setStatus(next);
    }

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Documents" subtitle="Document records from all source modules. Document UI does not calculate source totals or workflow effects." title="Document Records" />
            <DataToolbar
                filterValues={{ sourceModule, status }}
                filters={[
                    { id: 'sourceModule', label: 'Source module', options: sourceModuleOptions, placeholder: 'All source modules', type: 'select' },
                    { id: 'status', label: 'Status', options: [{ label: 'Draft', value: 'draft' }, { label: 'Submitted', value: 'submitted' }, { label: 'Approved', value: 'approved' }, { label: 'Posted', value: 'posted' }, { label: 'Finalized', value: 'finalized' }, { label: 'Cancelled', value: 'cancelled' }], placeholder: 'All statuses', type: 'status' },
                ]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={() => { setSourceModule(''); setStatus(''); }}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for document records."
                searchPlaceholder="Search document number, title, type, or source reference..."
                searchValue={query}
            />
            {isLoading ? <EmptyState description="Loading document records..." title="Loading records" /> : null}
            {error ? <EmptyState description={error} title="Document service unavailable" /> : null}
            {!isLoading && !error && visibleRecords.length ? <DocumentRecordTable records={visibleRecords} /> : null}
            {!isLoading && !error && !visibleRecords.length ? <EmptyState description="No records match the current filters." title="No documents found" /> : null}
        </div>
    );
}
