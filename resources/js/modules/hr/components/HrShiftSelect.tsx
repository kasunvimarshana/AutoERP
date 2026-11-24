/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { listShifts } from '../hrApi';
import type { HrShift } from '../hrTypes';

export function HrShiftSelect({
    value,
    onChange,
    error,
    label = 'Shift',
    disabled = false,
}: {
    value: HrShift | null;
    onChange: (value: HrShift | null) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [shifts, setShifts] = useState<HrShift[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        listShifts()
            .then((data) => setShifts(data))
            .catch(() => setShifts([]))
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
                    const selected = shifts.find((s) => s.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading shifts...' : '-- Select Shift --'}</option>
                {shifts.map((shift) => (
                    <option key={shift.id} value={shift.id}>
                        {shift.code} - {shift.name} ({shift.start_time.slice(0, 5)} - {shift.end_time.slice(0, 5)})
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
