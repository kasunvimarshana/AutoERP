import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import { searchActiveItems } from '../supplierApi';

const formatItem = (item: NamedResource) => `${item.code ?? ''} - ${item.name}`;

export function SupplierItemSelect({ value, onChange, error }: {
    value: NamedResource | null;
    onChange: (item: NamedResource | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Item" value={value} onChange={onChange} search={searchActiveItems} formatLabel={formatItem} error={error} />;
}
