import { useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { issueVehicleServiceInventory, listInventoryIssueLines } from '../vehicleServiceApi';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';
import { formatLineItem } from './line-editor/lineForm';
import { VehicleServiceInventoryLocationFields } from './VehicleServiceInventoryLocationFields';

export function VehicleServiceInventoryIssueDrawer({
    open,
    jobId,
    line,
    expectedVersion,
    onClose,
    onIssued,
}: {
    open: boolean;
    jobId: number;
    line: VehicleServiceJobLine | null;
    expectedVersion: number;
    onClose: () => void;
    onIssued: (nextVersion: number) => void;
}) {
    const identity = `${open ? 'open' : 'closed'}:${line?.id ?? 'none'}:${expectedVersion}`;

    return (
        <VehicleServiceInventoryIssueDrawerForm
            key={identity}
            open={open}
            jobId={jobId}
            line={line}
            expectedVersion={expectedVersion}
            onClose={onClose}
            onIssued={onIssued}
        />
    );
}

function VehicleServiceInventoryIssueDrawerForm({
    open,
    jobId,
    line,
    expectedVersion,
    onClose,
    onIssued,
}: {
    open: boolean;
    jobId: number;
    line: VehicleServiceJobLine | null;
    expectedVersion: number;
    onClose: () => void;
    onIssued: (nextVersion: number) => void;
}) {
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [issuing, setIssuing] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const eligibility = useApi(
        (signal) => listInventoryIssueLines(jobId, {
            warehouse_id: warehouse?.id,
            warehouse_location_id: location?.id,
        }, signal),
        [jobId, warehouse?.id, location?.id],
        open,
    );
    const eligibleLine = line
        ? (eligibility.data ?? []).find((candidate) => candidate.id === line.id) ?? null
        : null;
    const exactLocationSelected = warehouse !== null && location !== null;
    const canIssue = !eligibility.loading
        && exactLocationSelected
        && eligibleLine?.issue_eligible !== false
        && eligibleLine !== null;

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!line || !warehouse || !location || !canIssue || issuing) return;

        setIssuing(true);
        setError(null);
        try {
            await issueVehicleServiceInventory(jobId, {
                expected_version: expectedVersion,
                warehouse_id: warehouse.id,
                warehouse_location_id: location.id,
                line_ids: [line.id],
            });
            onIssued(expectedVersion + 1);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setIssuing(false);
        }
    };

    return (
        <FormDrawer
            open={open}
            title={`Issue stock — ${line ? formatLineItem(line) : ''}`}
            onClose={onClose}
            closeDisabled={issuing}
        >
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error ?? eligibility.error} />
                {line && (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <div className="font-semibold text-slate-900">{formatLineItem(line)}</div>
                        <div className="mt-1">Required quantity: {line.quantity} {line.uom?.code ?? line.uom?.name ?? ''}</div>
                    </div>
                )}
                <VehicleServiceInventoryLocationFields
                    value={{ warehouse, location }}
                    onChange={(next) => {
                        setWarehouse(next.warehouse);
                        setLocation(next.location);
                        setError(null);
                    }}
                    disabled={issuing}
                />
                {eligibility.loading ? (
                    <LoadingState />
                ) : exactLocationSelected ? (
                    eligibleLine ? (
                        <div className={`rounded-lg border p-4 text-sm ${eligibleLine.issue_eligible === false ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`}>
                            <div className="font-semibold">
                                {eligibleLine.issue_eligible === false ? 'Stock issue blocked' : 'Ready to issue'}
                            </div>
                            <div className="mt-1">Available: {eligibleLine.stock_available ?? '-'}</div>
                            {eligibleLine.inventory_warning && <div className="mt-1">{eligibleLine.inventory_warning}</div>}
                        </div>
                    ) : (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            This line is no longer pending for stock issue. Reload the job lines and try again.
                        </div>
                    )
                ) : (
                    <p className="text-sm text-slate-600">Select an exact warehouse and location to verify stock availability.</p>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={issuing} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={issuing} disabled={!canIssue}>Issue stock</Button>
                </div>
            </form>
        </FormDrawer>
    );
}
