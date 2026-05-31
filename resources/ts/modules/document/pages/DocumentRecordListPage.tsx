import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Select } from '../../../shared/components/ui/Select';
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

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Documents" subtitle="Document records from all source modules. Document UI does not calculate source totals or workflow effects." title="Document Records" />
            <SearchFilterBar onSearch={setQuery} />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-4">
                <Select onChange={(event) => setSourceModule(event.target.value)} options={[{ label: 'All source modules', value: '' }, { label: 'Purchase', value: 'purchase' }, { label: 'Sales', value: 'sales' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }, { label: 'Voucher', value: 'voucher' }, { label: 'Finance', value: 'finance' }]} value={sourceModule} />
                <Select onChange={(event) => setStatus(event.target.value)} options={[{ label: 'All statuses', value: '' }, { label: 'Draft', value: 'draft' }, { label: 'Submitted', value: 'submitted' }, { label: 'Approved', value: 'approved' }, { label: 'Posted', value: 'posted' }, { label: 'Finalized', value: 'finalized' }, { label: 'Cancelled', value: 'cancelled' }]} value={status} />
            </div>
            {isLoading ? <EmptyState description="Loading document records..." title="Loading records" /> : null}
            {error ? <EmptyState description={error} title="Document service unavailable" /> : null}
            {!isLoading && !error && visibleRecords.length ? <DocumentRecordTable records={visibleRecords} /> : null}
            {!isLoading && !error && !visibleRecords.length ? <EmptyState description="No records match the current filters." title="No documents found" /> : null}
        </div>
    );
}
