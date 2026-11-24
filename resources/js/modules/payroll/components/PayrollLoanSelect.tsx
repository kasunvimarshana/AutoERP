/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { listLoans } from '../payrollApi';
import type { PayrollLoan } from '../payrollTypes';

export function PayrollLoanSelect({
    value,
    onChange,
    employeeId,
    status,
    error,
    label = 'Employee Loan / Advance',
    disabled = false,
}: {
    value: PayrollLoan | null;
    onChange: (value: PayrollLoan | null) => void;
    employeeId?: number;
    status?: string;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [loans, setLoans] = useState<PayrollLoan[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let isMounted = true;
        listLoans({ employee_id: employeeId, status })
            .then((data) => {
                if (isMounted) setLoans(data);
            })
            .catch(() => {
                if (isMounted) setLoans([]);
            })
            .finally(() => {
                if (isMounted) setLoading(false);
            });

        return () => {
            isMounted = false;
        };
    }, [employeeId, status]);

    return (
        <div className="flex flex-col gap-1">
            {label && <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{label}</label>}
            <select
                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                value={value?.id ?? ''}
                onChange={(e) => {
                    const id = Number(e.target.value);
                    const selected = loans.find((l) => l.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading loans...' : '-- Select Loan or Advance --'}</option>
                {loans.map((l) => (
                    <option key={l.id} value={l.id}>
                        [{l.loan_reference}] {l.employee?.display_name ?? 'Employee'} ({l.loan_type === 'advance' ? 'Advance' : 'Loan'}) - Balance: {Number(l.balance_remaining).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
