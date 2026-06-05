import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { InventoryTraceabilityTimeline, StockAvailabilityPreviewForm, StockAvailabilityResultPanel } from '../components/InventoryComponents';
import { inventoryApi } from '../services/inventoryApi';
import type { InventoryAuditEntry, StockAvailabilityPreviewResult } from '../types/inventory.types';

export function TraceabilityPage() {
    const [rows, setRows] = useState<InventoryAuditEntry[]>([]);
    const [query, setQuery] = useState('');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        inventoryApi.getTraceability()
            .then((response) => setRows(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load traceability.'));
    }, []);

    const filteredRows = query
        ? rows.filter((row) => JSON.stringify(row).toLowerCase().includes(query.toLowerCase()))
        : rows;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Trace stock by item, batch, serial, or source reference." title="Traceability" />
            <FormSection title="Trace Context">
                <div className="grid gap-4 md:grid-cols-4">
                    <Input onChange={(event) => setQuery(event.target.value)} value={query} />
                </div>
            </FormSection>
            {error ? <EmptyState description={error} title="Unable to load traceability" /> : <InventoryTraceabilityTimeline entries={filteredRows} />}
        </div>
    );
}

export function StockAvailabilityPreviewPage() {
    const [result, setResult] = useState<StockAvailabilityPreviewResult | null>(null);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory Preview" subtitle="Check stock availability for an item, warehouse, UOM, and tracking context." title="Stock Availability Preview" />
            <StockAvailabilityPreviewForm onResult={setResult} />
            <StockAvailabilityResultPanel result={result} />
        </div>
    );
}
