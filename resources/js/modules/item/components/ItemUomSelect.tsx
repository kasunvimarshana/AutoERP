import { listUoms } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';
import { SearchableResourceSelect } from './SearchableResourceSelect';

const formatUom = (uom: NamedResource) => `${uom.code ?? ''} - ${uom.name}${uom.symbol ? ` (${uom.symbol})` : ''}`;

export function ItemUomSelect({ label = 'UOM', value, onChange, error }: {
    label?: string;
    value: NamedResource | null;
    onChange: (uom: NamedResource | null) => void;
    error?: string;
}) {
    return <SearchableResourceSelect label={label} value={value} onChange={onChange} search={listUoms} formatLabel={formatUom} error={error} />;
}
