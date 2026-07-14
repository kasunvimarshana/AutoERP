import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchDepartments } from '../hrApi';
import type { HrDepartment } from '../hrTypes';

export function HrDepartmentSelect({ value, onChange, error, label = 'Department' }: {
    value: HrDepartment | null;
    onChange: (value: HrDepartment | null) => void;
    error?: string;
    label?: string;
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={searchDepartments}
            formatLabel={(department) => `${department.code} ${department.name}`}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
