import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { InventoryTraceabilityTimeline, StockAvailabilityPreviewForm, StockAvailabilityResultPanel } from '../components/InventoryComponents';
import { availabilityPreview } from '../mock/inventoryMock';
import { inventoryApi } from '../services/inventoryApi';
import type { InventoryAuditEntry, StockAvailabilityPreviewResult } from '../types/inventory.types';

export function TraceabilityPage() {
    const [rows, setRows] = useState<InventoryAuditEntry[]>([]);
    useEffect(() => { inventoryApi.getTraceability().then((response) => setRows(response.data)); }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Trace stock by item, batch, serial, or source reference. Backend/mock returns the timeline." title="Traceability" />
            <FormSection title="Trace Context">
                <div className="grid gap-4 md:grid-cols-4">
                    <Input placeholder="Item selector" />
                    <Input placeholder="Batch / serial optional" />
                    <Input placeholder="Source reference optional" />
                    <Button variant="blue">Trace</Button>
                </div>
            </FormSection>
            <InventoryTraceabilityTimeline entries={rows} />
        </div>
    );
}

export function StockAvailabilityPreviewPage() {
    const [result, setResult] = useState<StockAvailabilityPreviewResult>(availabilityPreview);
    useEffect(() => {
        inventoryApi.previewStockAvailability({ itemId: 'mock-item', quantity: '1', uom: 'PCS', warehouse: 'Main Warehouse' })
            .then((response) => setResult({ breakdown: response.breakdown.map((row) => ({ label: String(row.label), value: String(row.value) })), calculated: response.calculated, errors: response.errors, input: response.input, warnings: response.warnings }));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory Preview" subtitle="Ask backend/mock if stock is available. The frontend does not calculate on hand, reserved, available, UOM conversion, or batch/serial availability." title="Stock Availability Preview" />
            <StockAvailabilityPreviewForm />
            <StockAvailabilityResultPanel result={result} />
        </div>
    );
}
