import { useCallback, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import type { NamedResource } from '@/shared/types/common';
import { searchWarehouses } from '@/shared/api/referenceApi';
import { useApi } from '@/shared/hooks/useApi';
import { issueVehicleServiceInventory, listInventoryIssueLines } from '../vehicleServiceApi';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';

export default function VehicleServiceInventoryIssueTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listInventoryIssueLines(jobId, signal), [jobId]);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [selected, setSelected] = useState<number[]>([]);
    const [issuing, setIssuing] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const search = useCallback((query: string, signal: AbortSignal) => searchWarehouses(query, signal), []);
    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'select', header: '', render: (line) => <input type="checkbox" checked={selected.includes(line.id)} onChange={(event) => setSelected((current) => event.target.checked ? [...current, line.id] : current.filter((id) => id !== line.id))} /> },
        { key: 'line', header: 'Line', render: (line) => line.line_number },
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? line.description },
        { key: 'quantity', header: 'Quantity', render: (line) => line.quantity },
        { key: 'status', header: 'Status', render: (line) => line.status },
    ];

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-[minmax(0,1fr)_auto]">
                <GenericLookupSelect label="Issue warehouse" value={warehouse} onChange={setWarehouse} search={search} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} />
                <div className="flex items-end">
                    <Button type="button" loading={issuing} disabled={!warehouse || selected.length === 0} onClick={async () => {
                        if (!warehouse) return;
                        setIssuing(true);
                        setError(null);
                        try {
                            await issueVehicleServiceInventory(jobId, { warehouse_id: warehouse.id, line_ids: selected });
                            setSelected([]);
                            result.reload();
                        } catch (requestError) {
                            setError(toApiError(requestError));
                        } finally {
                            setIssuing(false);
                        }
                    }}>Issue selected stock</Button>
                </div>
            </div>
            {result.loading ? <LoadingState /> : <DataTable rows={result.data ?? []} columns={columns} rowKey={(line) => line.id} emptyMessage="No inventory lines remain to issue." />}
        </div>
    );
}
