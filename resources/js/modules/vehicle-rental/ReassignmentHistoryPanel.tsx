import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { listReassignments, type ReassignmentRecord } from './agreementApi';

interface Props {
    customerAgreementId?: number;
    currencyCode?: string;
}

export function ReassignmentHistoryPanel({ customerAgreementId, currencyCode = 'USD' }: Props) {
    const [rows, setRows] = useState<ReassignmentRecord[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        listReassignments(customerAgreementId)
            .then(data => {
                if (!controller.signal.aborted) {
                    setRows(data);
                    setError(null);
                }
            })
            .catch(err => {
                if (!controller.signal.aborted) {
                    setError(toApiError(err));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => controller.abort();
    }, [customerAgreementId]);

    if (loading) {
        return <LoadingState label="Loading vehicle and driver reassignment history..." />;
    }

    return (
        <div className="space-y-4">
            <ErrorAlert error={error} inline />

            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                <table className="w-full text-left text-xs">
                    <thead>
                        <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                            <th className="px-4 py-3">Type</th>
                            <th className="px-4 py-3">Timestamp</th>
                            <th className="px-4 py-3">Entity Handover</th>
                            <th className="px-4 py-3">Financial Settlement</th>
                            <th className="px-4 py-3">Reason</th>
                            <th className="px-4 py-3">Logged By</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map(record => {
                            const isVehicle = record.reassignment_type === 'vehicle_replacement';
                            const hasDelta = Number(record.financial_delta_amount) !== 0;

                            return (
                                <tr key={record.id} className="transition-colors hover:bg-blue-50/30">
                                    <td className="px-4 py-3 font-medium">
                                        <span className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${
                                            isVehicle ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800'
                                        }`}>
                                            {isVehicle ? 'Vehicle Replacement' : 'Driver Reassignment'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">
                                        {formatBusinessDateTime(record.occurred_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {isVehicle ? (
                                            <div>
                                                <span className="font-semibold text-slate-900">
                                                    New: {record.new_vehicle?.registration_number ?? record.new_vehicle?.vehicle_number ?? `Vehicle #${record.new_vehicle_id}`}
                                                </span>
                                                {record.previous_vehicle && (
                                                    <div className="text-[11px] text-slate-500">
                                                        Prev: {record.previous_vehicle.registration_number ?? record.previous_vehicle.vehicle_number}
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div>
                                                <span className="font-semibold text-slate-900">
                                                    New: {record.new_driver?.display_name ?? `Driver #${record.new_driver_employee_id}`}
                                                </span>
                                                {record.previous_driver && (
                                                    <div className="text-[11px] text-slate-500">
                                                        Prev: {record.previous_driver.display_name}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {hasDelta ? (
                                            <div>
                                                <span className="font-bold text-slate-900">
                                                    <MoneyDisplay value={record.financial_delta_amount} currency={currencyCode} />
                                                </span>
                                                <span className="ml-1.5 inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium bg-amber-100 text-amber-800 capitalize">
                                                    {record.financial_status}
                                                </span>
                                            </div>
                                        ) : (
                                            <span className="text-slate-400 italic">No financial delta</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-slate-700 max-w-xs truncate" title={record.reason}>
                                        {record.reason}
                                    </td>
                                    <td className="px-4 py-3 text-slate-500">
                                        {record.user?.name ?? 'System'}
                                    </td>
                                </tr>
                            );
                        })}
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-6 text-center text-slate-400">
                                    No vehicle replacements or driver reassignments recorded.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
