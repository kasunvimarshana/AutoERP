import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchLicenses } from '../hrApi';
import type { HrLicense } from '../hrTypes';

export function HrLicenseSelect({ value, onChange, error }: {
    value: HrLicense | null;
    onChange: (value: HrLicense | null) => void;
    error?: string;
}) {
    return (
        <GenericLookupSelect
            label="License"
            value={value}
            onChange={onChange}
            search={searchLicenses}
            formatLabel={(license) => `${license.code} ${license.name}`}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
