/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState } from 'react';
import { listBiometricDevices } from '../hrApi';
import type { HrBiometricDevice } from '../hrTypes';

export function HrBiometricDeviceSelect({
    value,
    onChange,
    error,
    label = 'Biometric Device',
    disabled = false,
}: {
    value: HrBiometricDevice | null;
    onChange: (value: HrBiometricDevice | null) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}) {
    const [devices, setDevices] = useState<HrBiometricDevice[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let isMounted = true;
        listBiometricDevices({ per_page: 100 })
            .then((data) => {
                if (isMounted) {
                    setDevices(data.data || []);
                }
            })
            .catch(() => {
                if (isMounted) {
                    setDevices([]);
                }
            })
            .finally(() => {
                if (isMounted) {
                    setLoading(false);
                }
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
                    const selected = devices.find((d) => d.id === id) ?? null;
                    onChange(selected);
                }}
                disabled={disabled || loading}
            >
                <option value="">{loading ? 'Loading devices...' : '-- Select Biometric Device --'}</option>
                {devices.map((device) => (
                    <option key={device.id} value={device.id}>
                        [{device.device_code}] {device.name} ({device.protocol.replace('_', ' ').toUpperCase()})
                        {device.ip_address ? ` - ${device.ip_address}` : ''}
                    </option>
                ))}
            </select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
