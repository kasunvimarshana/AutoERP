/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Textarea } from '@/shared/components/Textarea';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { requestLookup } from '@/shared/api/lookupRequest';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { DRIVER_EMPLOYEE_API, type RunningChart } from './runningCharts';
import { reassignRunningChartDriver, reassignVehicleUseDriver } from './agreementApi';

interface Props {
    open: boolean;
    onClose: () => void;
    chart?: RunningChart | null;
    vehicleUse?: { id: number; vehicle_label?: string; row_version: number; driver_name?: string } | null;
    onSaved?: () => void;
}

const employeeLoader = (params: LookupLoadParams) =>
    requestLookup<Record<string, unknown>>(DRIVER_EMPLOYEE_API, params).then(result => ({
        ...result,
        data: result.data.map(row => ({
            id: Number(row.id),
            name: String(row.display_name ?? row.name ?? row.employee_number ?? ''),
            code: String(row.employee_number ?? row.code ?? ''),
        })),
    }));

export function ReassignDriverModal({ open, onClose, chart, vehicleUse, onSaved }: Props) {
    const [selectedEmployee, setSelectedEmployee] = useState<NamedResource | null>(null);
    const [effectiveAt, setEffectiveAt] = useState(`${businessDateInputValue()}T08:00`);
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!selectedEmployee || !reason.trim()) return;

        setSubmitting(true);
        setError(null);

        try {
            if (chart) {
                await reassignRunningChartDriver(
                    chart.id,
                    selectedEmployee.id,
                    reason.trim(),
                    chart.row_version,
                );
            } else if (vehicleUse) {
                await reassignVehicleUseDriver(
                    vehicleUse.id,
                    {
                        driver_employee_id: selectedEmployee.id,
                        effective_at: effectiveAt,
                        reason: reason.trim(),
                    },
                    vehicleUse.row_version,
                );
            }
            if (onSaved) onSaved();
            onClose();
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    const currentDriverLabel = chart?.driver
        ? `${chart.driver.name} (${chart.driver.reference ?? 'Employee Driver'})`
        : vehicleUse?.driver_name
            ? vehicleUse.driver_name
            : 'Assigned Driver';

    const modalTitle = chart
        ? `Reassign Driver for Chart ${chart.reference}`
        : vehicleUse
            ? `Reassign Driver for Assignment (${vehicleUse.vehicle_label ?? 'Vehicle Assignment'})`
            : 'Reassign Driver';

    return (
        <Modal
            open={open}
            title={modalTitle}
            onClose={onClose}
            closeDisabled={submitting}
        >
            <form onSubmit={handleSubmit} className="space-y-4">
                <ErrorAlert error={error} inline />

                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs space-y-1">
                    <span className="text-slate-500 font-semibold uppercase tracking-wider block">Current Driver</span>
                    <span className="font-bold text-slate-800">{currentDriverLabel}</span>
                </div>

                {vehicleUse && !chart && (
                    <Input
                        label="Effective Cutover Date & Time"
                        type="datetime-local"
                        value={effectiveAt}
                        onChange={e => setEffectiveAt(e.target.value)}
                        required
                    />
                )}

                <div className="space-y-2">
                    <LookupSelect
                        label="New Driver Employee"
                        value={selectedEmployee}
                        onChange={setSelectedEmployee}
                        search={employeeLoader}
                        placeholder="Search employee directory by name or employee number..."
                        loadOnOpen
                        minSearchLength={0}
                        required
                    />
                </div>

                <Textarea
                    label="Reassignment Reason / Justification"
                    value={reason}
                    onChange={e => setReason(e.target.value)}
                    placeholder="State reason for driver replacement (e.g. driver illness, shift handover, client emergency)..."
                    required
                />

                <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        loading={submitting}
                        disabled={!selectedEmployee || !reason.trim()}
                    >
                        Confirm Driver Reassignment
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
