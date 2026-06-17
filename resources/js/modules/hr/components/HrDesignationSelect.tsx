import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchDesignations } from '../hrApi';
import type { HrDesignation } from '../hrTypes';

export function HrDesignationSelect({ value, onChange, error }: {
    value: HrDesignation | null;
    onChange: (value: HrDesignation | null) => void;
    error?: string;
}) {
    return (
        <GenericLookupSelect
            label="Designation"
            value={value}
            onChange={onChange}
            search={searchDesignations}
            formatLabel={(designation) => `${designation.code} ${designation.name}`}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
