/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { listStructures } from '../payrollApi';
import type { SalaryStructure } from '../payrollTypes';

export function PayrollSalaryStructureSelect({
    value,
    onChange,
    error,
    label = 'Salary Structure',
    disabled = false,
}: {
    value: SalaryStructure | null;
    onChange: (value: SalaryStructure | null) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [structures, setStructures] = useState<SalaryStructure[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        listStructures()
            .then((data) => setStructures(data))
            .catch(() => setStructures([]))
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
                    const selected = structures.find((s) => s.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading structures...' : '-- Select Salary Structure --'}</option>
                {structures.map((s) => (
                    <option key={s.id} value={s.id}>
                        {s.code} - {s.name} ({s.cycle_type.toUpperCase()})
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
