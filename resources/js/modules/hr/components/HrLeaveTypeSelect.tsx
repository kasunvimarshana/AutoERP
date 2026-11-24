/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { listLeaveTypes } from '../hrApi';
import type { HrLeaveType } from '../hrTypes';

export function HrLeaveTypeSelect({
    value,
    onChange,
    error,
    label = 'Leave Type',
    disabled = false,
}: {
    value: HrLeaveType | null;
    onChange: (value: HrLeaveType | null) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [types, setTypes] = useState<HrLeaveType[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        listLeaveTypes()
            .then((data) => setTypes(data))
            .catch(() => setTypes([]))
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
                    const selected = types.find((t) => t.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading leave types...' : '-- Select Leave Type --'}</option>
                {types.map((type) => (
                    <option key={type.id} value={type.id}>
                        {type.code} - {type.name} {type.is_paid ? '(Paid)' : '(Unpaid)'}
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
