/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchEmployees } from '../hrApi';
import type { EmployeeSummary } from '../hrTypes';
import type { LookupLoadParams } from '@/shared/types/lookup';

export function EmployeeLookupSelect({ value, onChange, excludeId, error, label = 'Employee' }: {
    value: EmployeeSummary | null;
    onChange: (value: EmployeeSummary | null) => void;
    excludeId?: number | null;
    error?: string;
    label?: string;
}) {
    const search = useCallback((params: LookupLoadParams) => searchEmployees(params), []);

    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={search}
            excludeId={excludeId}
            formatLabel={(employee) => `${employee.employee_number} ${employee.display_name}`}
            error={error}
        />
    );
}
