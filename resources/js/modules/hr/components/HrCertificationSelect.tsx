import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCertifications } from '../hrApi';
import type { HrCertification } from '../hrTypes';

export function HrCertificationSelect({ value, onChange, error }: {
    value: HrCertification | null;
    onChange: (value: HrCertification | null) => void;
    error?: string;
}) {
    return (
        <GenericLookupSelect
            label="Certification"
            value={value}
            onChange={onChange}
            search={searchCertifications}
            formatLabel={(certification) => `${certification.code} ${certification.name}`}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
