import { useCallback, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { notifySuccess } from '@/shared/notifications/appToast';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import {
    createOpeningStockImport,
    downloadOpeningStockTemplate,
    previewOpeningStockImport,
} from '../../inventoryApi';
import type { OpeningStockImportPreview, OpeningStockImportPreviewRow } from '../../inventoryTypes';
import { localToday } from '../inventoryUi';

const TEMPLATE_FILE_NAME = 'opening-stock-template.csv';

export function OpeningStockImportPanel({ reload }: { reload: () => void }) {
    const [adjustmentDate, setAdjustmentDate] = useState(localToday);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [file, setFile] = useState<File | null>(null);
    const [fileInputKey, setFileInputKey] = useState(0);
    const [preview, setPreview] = useState<OpeningStockImportPreview | null>(null);
    const [busyAction, setBusyAction] = useState<'template' | 'preview' | 'create' | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const locationSearch = useCallback(
        (params: LookupLoadParams) => searchWarehouseLocations(params, warehouse?.id),
        [warehouse?.id],
    );

    const formData = () => {
        const payload = new FormData();
        payload.append('adjustment_date', adjustmentDate);
        payload.append('warehouse_id', String(warehouse?.id ?? ''));
        if (location) payload.append('warehouse_location_id', String(location.id));
        if (file) payload.append('csv_file', file);
        return payload;
    };

    const clearPreview = () => {
        setPreview(null);
        setError(null);
    };

    return (
        <details className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <summary className="cursor-pointer text-sm font-semibold text-slate-800">Import opening stock CSV</summary>
            <div className="mt-4 space-y-4">
                <p className="text-sm text-slate-600">
                    Import one warehouse and location at a time. Previewing resolves item, UOM and tracking codes without changing stock. A successful import creates one draft opening-balance adjustment for review and posting.
                </p>
                <ErrorAlert error={error} />
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Input
                        label="Opening date"
                        type="date"
                        value={adjustmentDate}
                        onChange={(event) => { setAdjustmentDate(event.target.value); clearPreview(); }}
                    />
                    <LookupSelect
                        label="Warehouse"
                        value={warehouse}
                        onChange={(value) => { setWarehouse(value); setLocation(null); clearPreview(); }}
                        search={searchWarehouses}
                        loadOnOpen
                        minSearchLength={0}
                    />
                    <LookupSelect
                        label="Location"
                        value={location}
                        onChange={(value) => { setLocation(value); clearPreview(); }}
                        search={locationSearch}
                        disabled={!warehouse}
                        loadOnOpen
                        minSearchLength={0}
                    />
                    <Input
                        key={fileInputKey}
                        label="CSV file"
                        type="file"
                        accept=".csv,text/csv"
                        hint="Use the downloaded template."
                        onChange={(event) => {
                            setFile(event.target.files?.[0] ?? null);
                            clearPreview();
                        }}
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="secondary"
                        loading={busyAction === 'template'}
                        disabled={busyAction !== null}
                        onClick={async () => {
                            setBusyAction('template');
                            setError(null);
                            try {
                                const blob = await downloadOpeningStockTemplate();
                                const url = URL.createObjectURL(blob);
                                const anchor = document.createElement('a');
                                anchor.href = url;
                                anchor.download = TEMPLATE_FILE_NAME;
                                document.body.appendChild(anchor);
                                anchor.click();
                                anchor.remove();
                                URL.revokeObjectURL(url);
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusyAction(null);
                            }
                        }}
                    >Download template</Button>
                    <Button
                        variant="secondary"
                        loading={busyAction === 'preview'}
                        disabled={busyAction !== null || !warehouse || !file || adjustmentDate === ''}
                        onClick={async () => {
                            setBusyAction('preview');
                            setError(null);
                            try {
                                setPreview(await previewOpeningStockImport(formData()));
                            } catch (requestError) {
                                setPreview(null);
                                setError(toApiError(requestError));
                            } finally {
                                setBusyAction(null);
                            }
                        }}
                    >Preview CSV</Button>
                    <Button
                        loading={busyAction === 'create'}
                        disabled={busyAction !== null || !preview?.can_create || !file || !warehouse}
                        onClick={async () => {
                            setBusyAction('create');
                            setError(null);
                            try {
                                await createOpeningStockImport(formData());
                                notifySuccess('Opening stock draft adjustment created. Review and post it from the adjustment list.');
                                setPreview(null);
                                setFile(null);
                                setFileInputKey((current) => current + 1);
                                reload();
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusyAction(null);
                            }
                        }}
                    >Create draft adjustment</Button>
                </div>
                {preview && <OpeningStockPreviewTable preview={preview} />}
            </div>
        </details>
    );
}

function OpeningStockPreviewTable({ preview }: { preview: OpeningStockImportPreview }) {
    return (
        <div className="space-y-3">
            <div className="flex flex-wrap gap-3 text-sm">
                <span className="font-semibold text-slate-800">{preview.total_rows} rows</span>
                <span className="text-emerald-700">{preview.valid_rows} valid</span>
                <span className={preview.invalid_rows > 0 ? 'font-semibold text-rose-700' : 'text-slate-500'}>{preview.invalid_rows} invalid</span>
            </div>
            <DataTable
                rows={preview.rows}
                rowKey={(row) => row.row_number}
                rowClassName={(row) => row.errors.length > 0 ? 'bg-rose-50/60' : undefined}
                emptyMessage="No CSV rows were found."
                columns={previewColumns}
            />
        </div>
    );
}

const previewColumns = [
    { key: 'row', header: 'Row', render: (row: OpeningStockImportPreviewRow) => row.row_number },
    { key: 'item', header: 'Item', render: (row: OpeningStockImportPreviewRow) => row.item_name ? `${row.item_code} · ${row.item_name}` : row.item_code || '-' },
    { key: 'variant', header: 'Variant', render: (row: OpeningStockImportPreviewRow) => row.variant_name ? `${row.variant_code} · ${row.variant_name}` : row.variant_code || '-' },
    { key: 'uom', header: 'UOM', render: (row: OpeningStockImportPreviewRow) => row.uom_code || '-' },
    { key: 'quantity', header: 'Opening qty', render: (row: OpeningStockImportPreviewRow) => row.opening_quantity || '-' },
    { key: 'cost', header: 'Unit cost', render: (row: OpeningStockImportPreviewRow) => row.unit_cost || '-' },
    { key: 'base', header: 'Base qty / cost', render: (row: OpeningStockImportPreviewRow) => row.base_quantity ? `${row.base_quantity} / ${row.base_unit_cost}` : '-' },
    { key: 'tracking', header: 'Batch / serial', render: (row: OpeningStockImportPreviewRow) => [row.batch_number, row.serial_number].filter(Boolean).join(' / ') || '-' },
    { key: 'result', header: 'Validation', render: (row: OpeningStockImportPreviewRow) => row.errors.length === 0
        ? <span className="font-semibold text-emerald-700">Valid</span>
        : <ul className="space-y-1 text-rose-700">{row.errors.map((message) => <li key={message}>{message}</li>)}</ul> },
];
