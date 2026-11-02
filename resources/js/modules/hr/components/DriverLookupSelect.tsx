import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchDrivers } from '../driverApi';
import type { DriverSummary } from '../driverTypes';

interface DriverLookupSelectProps {
    label?: string;
    value: DriverSummary | null;
    onChange: (value: DriverSummary | null) => void;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    excludeId?: number | null;
}

export function DriverLookupSelect({
    label = 'Driver',
    value,
    onChange,
    error,
    required = false,
    disabled = false,
    excludeId,
}: DriverLookupSelectProps) {
    const search = useCallback(
        (params: LookupLoadParams) => searchDrivers(params),
        [],
    );

    return (
        <GenericLookupSelect<DriverSummary>
            label={label}
            value={value}
            onChange={onChange}
            search={search}
            excludeId={excludeId}
            formatLabel={(driver) => {
                const lic = driver.driving_license_number ? ` [Lic: ${driver.driving_license_number}]` : '';
                return `${driver.employee_number || driver.code || ''} ${driver.display_name || driver.name}${lic}`.trim();
            }}
            error={error}
            required={required}
            disabled={disabled}
            placeholder="Search driver by code, name, license, NIC..."
        />
    );
}
