/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type { HrShiftRotation } from '../hrTypes';

export function HrShiftRotationSelect({
    value,
    onChange,
    error,
    label = 'Shift Rotation Cycle',
    disabled = false,
}: {
    value: HrShiftRotation | null;
    onChange: (value: HrShiftRotation | null) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [rotations, setRotations] = useState<HrShiftRotation[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let isMounted = true;
        apiClient
            .get<ApiResource<HrShiftRotation[]>>(endpoints.hrShiftRotations)
            .then((r) => {
                if (isMounted) setRotations(r.data.data || []);
            })
            .catch(() => {
                if (isMounted) setRotations([]);
            })
            .finally(() => {
                if (isMounted) setLoading(false);
            });

        return () => {
            isMounted = false;
        };
    }, []);

    return (
        <div className="flex flex-col gap-1">
            {label && <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{label}</label>}
            <select
                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                value={value?.id ?? ''}
                onChange={(e) => {
                    const id = Number(e.target.value);
                    const selected = rotations.find((r) => r.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading rotations...' : '-- Select Rotation Pattern --'}</option>
                {rotations.map((r) => (
                    <option key={r.id} value={r.id}>
                        {r.name} ({r.code}) - {r.cycle_days} Day Cycle
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
