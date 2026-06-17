import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchEmploymentTypes } from '../hrApi';
import type { HrEmploymentType } from '../hrTypes';

export function HrEmploymentTypeSelect({ value, onChange, error }: {
    value: HrEmploymentType | null;
    onChange: (value: HrEmploymentType | null) => void;
    error?: string;
}) {
    return (
        <GenericLookupSelect
            label="Employment Type"
            value={value}
            onChange={onChange}
            search={searchEmploymentTypes}
            formatLabel={(type) => `${type.code} ${type.name}`}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
