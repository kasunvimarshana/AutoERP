import { listUoms } from '@/shared/api/referenceApi';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';

const formatUom = (uom: NamedResource) => `${uom.code ?? ''} - ${uom.name}${uom.symbol ? ` (${uom.symbol})` : ''}`;

export function SupplierUomSelect({ value, onChange, error }: {
    value: NamedResource | null;
    onChange: (uom: NamedResource | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Purchase UOM" value={value} onChange={onChange} search={listUoms} formatLabel={formatUom} error={error} />;
}
