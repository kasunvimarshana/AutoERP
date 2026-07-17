import { useEffect, useRef, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import type { NamedResource } from '@/shared/types/common';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { useApi } from '@/shared/hooks/useApi';
import { getDefaultWarehouse, getDefaultWarehouseLocation } from '@/modules/warehouse/warehouseApi';
import { issueVehicleServiceInventory, listInventoryIssueLines } from '../vehicleServiceApi';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';

export default function VehicleServiceInventoryIssueTab({
    jobId,
    expectedVersion,
    onChanged,
}: {
    jobId: number;
    expectedVersion: number;
    onChanged: (nextVersion: number) => void;
}) {
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [defaultsError, setDefaultsError] = useState<ApiError | null>(null);
    const warehouseTouched = useRef(false);
    const locationTouched = useRef(false);
    const searchLocations = (params: Parameters<typeof searchWarehouseLocations>[0]) =>
        searchWarehouseLocations(params, warehouse?.id);
    const result = useApi(
        (signal) => listInventoryIssueLines(jobId, {
            warehouse_id: warehouse?.id,
            warehouse_location_id: warehouseLocation?.id,
        }, signal),
        [jobId, warehouse?.id, warehouseLocation?.id],
    );
    const [selected, setSelected] = useState<number[]>([]);
    const [issuing, setIssuing] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const exactLocationSelected = Boolean(warehouse && warehouseLocation);

    useEffect(() => {
        if (warehouseTouched.current || warehouse !== null) {
            return;
        }

        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) {
                setDefaultsError(null);
            }
        });

        void getDefaultWarehouse(controller.signal)
            .then((defaultWarehouse) => {
                if (controller.signal.aborted || defaultWarehouse === null || warehouseTouched.current) {
                    return;
                }

                setWarehouse(defaultWarehouse);
                setSelected([]);
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) {
                    setDefaultsError(toApiError(requestError));
                }
            });

        return () => controller.abort();
    }, [warehouse]);

    useEffect(() => {
        if (!warehouse?.id || locationTouched.current) {
            return;
        }

        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) {
                setDefaultsError(null);
            }
        });

        void getDefaultWarehouseLocation(warehouse.id, controller.signal)
            .then((defaultLocation) => {
                if (controller.signal.aborted || locationTouched.current) {
                    return;
                }

                setWarehouseLocation(defaultLocation);
                setSelected([]);
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) {
                    setDefaultsError(toApiError(requestError));
                }
            });

        return () => controller.abort();
    }, [warehouse?.id]);

    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'select', header: '', render: (line) => <input type="checkbox" disabled={!warehouse || !warehouseLocation || line.issue_eligible === false} checked={selected.includes(line.id)} onChange={(event) => setSelected((current) => event.target.checked ? [...current, line.id] : current.filter((id) => id !== line.id))} /> },
        { key: 'line', header: 'Line', render: (line) => line.line_number },
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? line.description },
        { key: 'quantity', header: 'Quantity', render: (line) => line.quantity },
        { key: 'available', header: 'Available', render: (line) => line.stock_available ?? (warehouse ? 'Select location' : 'Select warehouse') },
        { key: 'eligibility', header: 'Issue readiness', render: (line) => (
            <div>
                <span className={`font-semibold ${!exactLocationSelected ? 'text-amber-700' : line.issue_eligible === false ? 'text-rose-700' : 'text-emerald-700'}`}>
                    {!exactLocationSelected ? 'Pending check' : line.issue_eligible === false ? 'Blocked' : 'Ready'}
                </span>
                {line.inventory_warning && <p className="text-xs text-amber-700">{line.inventory_warning}</p>}
            </div>
        ) },
    ];

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? defaultsError ?? result.error} />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                <GenericLookupSelect label="Issue warehouse" value={warehouse} onChange={(value) => { warehouseTouched.current = true; locationTouched.current = false; setWarehouse(value); setWarehouseLocation(null); setSelected([]); }} search={searchWarehouses} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} loadOnOpen minSearchLength={0} />
                <GenericLookupSelect label="Issue location" value={warehouseLocation} onChange={(value) => { locationTouched.current = true; setWarehouseLocation(value); setSelected([]); }} search={searchLocations} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} placeholder={warehouse ? 'Select warehouse location' : 'Select a warehouse first'} disabled={!warehouse} loadOnOpen={Boolean(warehouse)} minSearchLength={0} />
                <div className="flex items-end">
                    <Button type="button" loading={issuing} disabled={!warehouse || !warehouseLocation || selected.length === 0} onClick={async () => {
                        if (!warehouse || !warehouseLocation) return;
                        setIssuing(true);
                        setError(null);
                        try {
                            await issueVehicleServiceInventory(jobId, {
                                expected_version: expectedVersion,
                                warehouse_id: warehouse.id,
                                warehouse_location_id: warehouseLocation.id,
                                line_ids: selected,
                            });
                            result.setData((current) => (current ?? []).filter((line) => !selected.includes(line.id)));
                            setSelected([]);
                            onChanged(expectedVersion + 1);
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
