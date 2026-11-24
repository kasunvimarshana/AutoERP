/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { getPayslip, listPayRuns, listPayslips } from './payrollApi';
import type { PayRun, Payslip } from './payrollTypes';

export default function EmployeePayslipPage() {
    const [payslips, setPayslips] = useState<Payslip[]>([]);
    const [payRuns, setPayRuns] = useState<PayRun[]>([]);
    const [selectedRunId, setSelectedRunId] = useState<number | ''>('');
    const [selectedSlip, setSelectedSlip] = useState<Payslip | null>(null);
    const [loading, setLoading] = useState(true);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const loadData = useCallback(() => {
        Promise.all([listPayRuns(), listPayslips()])
            .then(([rData, sData]) => {
                setPayRuns(rData);
                setPayslips(sData);
                if (sData.length > 0 && !selectedSlip) {
                    getPayslip(sData[0].id).then(setSelectedSlip);
                }
            })
            .catch((err) => setErrorMsg(err.message || 'Failed to load payslips.'))
            .finally(() => setLoading(false));
    }, [selectedSlip]);

    useEffect(() => {
        loadData();
    }, [loadData]);

    const handleSelectSlip = (id: number) => {
        getPayslip(id)
            .then(setSelectedSlip)
            .catch((err) => setErrorMsg(err.message || 'Failed to load payslip details.'));
    };

    const filteredSlips = selectedRunId
        ? payslips.filter((s) => s.pay_run_id === Number(selectedRunId))
        : payslips;

    return (
        <div className="space-y-6 p-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Employee Payslips & Statements
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        View itemized earnings, statutory EPF/ETF contributions, tax withholdings, and take-home net pay.
                    </p>
                </div>
                {selectedSlip && (
                    <button
                        type="button"
                        onClick={() => window.print()}
                        className="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        🖨️ Print Payslip
                    </button>
                )}
            </div>

            {errorMsg && (
                <div className="rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                    {errorMsg}
                </div>
            )}

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Left: Payslip List */}
                <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-b border-gray-200 p-3 dark:border-gray-700">
                        <label className="text-xs font-medium text-gray-600 dark:text-gray-400">Filter Pay Run:</label>
                        <select
                            value={selectedRunId}
                            onChange={(e) => setSelectedRunId(e.target.value === '' ? '' : Number(e.target.value))}
                            className="mt-1 w-full rounded border border-gray-300 px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">All Pay Runs</option>
                            {payRuns.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.pay_run_number} ({r.period?.name || 'Run'})
                                </option>
                            ))}
                        </select>
                    </div>

                    {loading ? (
                        <div className="p-8 text-center text-xs text-gray-500">Loading payslips...</div>
                    ) : filteredSlips.length === 0 ? (
                        <div className="p-8 text-center text-xs text-gray-500">No payslips found.</div>
                    ) : (
                        <ul className="divide-y divide-gray-100 max-h-[600px] overflow-y-auto dark:divide-gray-700">
                            {filteredSlips.map((slip) => (
                                <li
                                    key={slip.id}
                                    onClick={() => handleSelectSlip(slip.id)}
                                    className={`cursor-pointer p-3 text-xs transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50 ${
                                        selectedSlip?.id === slip.id ? 'bg-indigo-50 dark:bg-indigo-950/40' : ''
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">
                                            {slip.employee?.display_name || 'Staff Member'}
                                        </span>
                                        <span className="font-mono text-[10px] text-gray-400">
                                            {slip.payslip_number}
                                        </span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between text-gray-500 dark:text-gray-400">
                                        <span>{slip.employee?.employee_number}</span>
                                        <span className="font-bold text-gray-900 dark:text-gray-100">
                                            ${Number(slip.net_pay).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Right: Printable Payslip Document */}
                <div className="lg:col-span-2">
                    {selectedSlip ? (
                        <div id="printable-payslip" className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            {/* Document Header */}
                            <div className="border-b border-gray-200 pb-4 dark:border-gray-700">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">AutoERP Enterprises</h2>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Official Salary Statement & Payslip</p>
                                    </div>
                                    <div className="text-right">
                                        <span className="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                            {selectedSlip.payslip_number}
                                        </span>
                                        <p className="text-[11px] text-gray-500">Run: {selectedSlip.pay_run?.pay_run_number}</p>
                                    </div>
                                </div>

                                <div className="mt-4 grid grid-cols-2 gap-4 rounded-md bg-gray-50 p-3 text-xs dark:bg-gray-900">
                                    <div>
                                        <span className="text-gray-400">Employee:</span>{' '}
                                        <strong className="text-gray-800 dark:text-gray-200">{selectedSlip.employee?.display_name} ({selectedSlip.employee?.employee_number})</strong>
                                    </div>
                                    <div>
                                        <span className="text-gray-400">Pay Period:</span>{' '}
                                        <strong className="text-gray-800 dark:text-gray-200">{selectedSlip.period_start} to {selectedSlip.period_end}</strong>
                                    </div>
                                    <div>
                                        <span className="text-gray-400">Department:</span>{' '}
                                        <strong className="text-gray-800 dark:text-gray-200">{selectedSlip.employee?.department?.name || 'N/A'}</strong>
                                    </div>
                                    <div>
                                        <span className="text-gray-400">Payment Mode:</span>{' '}
                                        <strong className="text-gray-800 dark:text-gray-200 uppercase">{selectedSlip.payment_method}</strong>
                                    </div>
                                </div>

                                {/* Working Days & Attendance Summary */}
                                <div className="mt-3 flex flex-wrap gap-4 text-[11px] text-gray-600 dark:text-gray-300">
                                    <span>Standard Days: <strong>{selectedSlip.standard_days}</strong></span>
                                    <span>Worked Days: <strong>{selectedSlip.worked_days}</strong></span>
                                    <span>Unpaid Leaves: <strong>{selectedSlip.unpaid_leave_days}</strong></span>
                                    <span>Absent Days: <strong>{selectedSlip.absent_days}</strong></span>
                                    <span>Normal OT: <strong>{selectedSlip.overtime_hours}h</strong></span>
                                    <span>Double OT: <strong>{selectedSlip.double_time_hours}h</strong></span>
                                </div>
                            </div>

                            {/* Line Items Table */}
                            <div className="mt-4">
                                <h3 className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Itemized Breakdown</h3>
                                <table className="mt-2 w-full text-left text-xs">
                                    <thead className="border-b border-gray-200 text-gray-400 dark:border-gray-700">
                                        <tr>
                                            <th className="py-2">Item</th>
                                            <th className="py-2">Type</th>
                                            <th className="py-2">Basis</th>
                                            <th className="py-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                        {selectedSlip.items?.map((item) => (
                                            <tr key={item.id} className="py-1.5">
                                                <td className="py-2 font-medium text-gray-800 dark:text-gray-200">{item.item_name}</td>
                                                <td className="py-2">
                                                    <span className="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {item.item_type}
                                                    </span>
                                                </td>
                                                <td className="py-2 text-gray-400">{item.calculation_basis || '-'}</td>
                                                <td className="py-2 text-right font-mono font-semibold text-gray-900 dark:text-gray-100">
                                                    ${Number(item.amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Summary & Totals Box */}
                            <div className="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div className="space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <div className="flex justify-between">
                                        <span>Gross Earnings:</span>
                                        <strong className="text-gray-900 dark:text-gray-100">${Number(selectedSlip.gross_pay).toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Total Deductions (Unpaid/Absent/Other):</span>
                                        <strong className="text-red-600">-${Number(selectedSlip.total_deductions).toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Employee Statutory Deductions (EPF):</span>
                                        <strong className="text-red-600">-${Number(selectedSlip.employee_statutory_deductions).toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Withholding Tax (PAYE/APIT):</span>
                                        <strong className="text-red-600">-${Number(selectedSlip.tax_deduction).toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong>
                                    </div>
                                    <div className="flex justify-between border-t border-gray-100 pt-2 text-sm dark:border-gray-700">
                                        <span className="font-bold text-gray-900 dark:text-gray-100">Net Take-Home Pay:</span>
                                        <span className="font-mono text-base font-extrabold text-indigo-600 dark:text-indigo-400">
                                            ${Number(selectedSlip.net_pay).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                        </span>
                                    </div>
                                </div>

                                {/* Employer Contribution Note */}
                                <div className="mt-4 rounded bg-gray-50 p-2.5 text-[11px] text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                    <strong>Employer Liability:</strong> Employer statutory contributions for this period: ${Number(selectedSlip.employer_statutory_contributions).toLocaleString(undefined, { minimumFractionDigits: 2 })} (Paid directly by the company, not deducted from net pay).
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="flex h-64 items-center justify-center rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-400 dark:border-gray-700">
                            Select a payslip from the list to preview details.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
