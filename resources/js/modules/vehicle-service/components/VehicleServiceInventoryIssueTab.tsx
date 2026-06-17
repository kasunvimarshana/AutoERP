import { useState } from 'react';
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
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const result = useApi(
        (signal) => listInventoryIssueLines(jobId, { warehouse_id: warehouse?.id }, signal),
        [jobId, warehouse?.id],
    );
    const [selected, setSelected] = useState<number[]>([]);
    const [issuing, setIssuing] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'select', header: '', render: (line) => <input type="checkbox" disabled={!warehouse || line.issue_eligible === false} checked={selected.includes(line.id)} onChange={(event) => setSelected((current) => event.target.checked ? [...current, line.id] : current.filter((id) => id !== line.id))} /> },
        { key: 'line', header: 'Line', render: (line) => line.line_number },
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? line.description },
        { key: 'quantity', header: 'Quantity', render: (line) => line.quantity },
        { key: 'available', header: 'Available', render: (line) => line.stock_available ?? 'Select warehouse' },
        { key: 'eligibility', header: 'Issue readiness', render: (line) => (
            <div>
                <span className={`font-semibold ${line.issue_eligible === false ? 'text-rose-700' : 'text-emerald-700'}`}>
                    {line.issue_eligible === false ? 'Blocked' : warehouse ? 'Ready' : 'Pending check'}
                </span>
                {line.inventory_warning && <p className="text-xs text-amber-700">{line.inventory_warning}</p>}
            </div>
        ) },
    ];

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-[minmax(0,1fr)_auto]">
                <GenericLookupSelect label="Issue warehouse" value={warehouse} onChange={(value) => { setWarehouse(value); setSelected([]); }} search={searchWarehouses} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} loadOnOpen minSearchLength={0} />
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
