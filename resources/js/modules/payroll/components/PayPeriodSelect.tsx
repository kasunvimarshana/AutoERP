/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { listPayPeriods } from '../payrollApi';
import type { PayPeriod } from '../payrollTypes';

export function PayPeriodSelect({
    value,
    onChange,
    error,
    label = 'Pay Period',
    disabled = false,
}: {
    value: PayPeriod | null;
    onChange: (value: PayPeriod | null) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [periods, setPeriods] = useState<PayPeriod[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        listPayPeriods()
            .then((data) => setPeriods(data))
            .catch(() => setPeriods([]))
            .finally(() => setLoading(false));
    }, []);

    return (
        <div className="flex flex-col gap-1">
            {label && <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{label}</label>}
            <select
                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                value={value?.id ?? ''}
                onChange={(e) => {
                    const id = Number(e.target.value);
                    const selected = periods.find((p) => p.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading periods...' : '-- Select Pay Period --'}</option>
                {periods.map((p) => (
                    <option key={p.id} value={p.id}>
                        {p.name} ({p.period_start} to {p.period_end}) - {p.total_working_days} Days
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
