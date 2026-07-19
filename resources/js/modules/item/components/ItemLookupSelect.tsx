import { searchItems } from '../itemApi';
import type { ItemSummary } from '../itemTypes';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';

const formatItem = (item: ItemSummary) => `${item.code} - ${item.name}`;

export function ItemLookupSelect({ value, onChange, excludeId, error, label = 'Item', lookupKind = '' }: {
    value: ItemSummary | null;
    onChange: (item: ItemSummary | null) => void;
    excludeId?: number | null;
    error?: string;
    label?: string;
    lookupKind?: string;
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={(params) => searchItems(params, lookupKind)}
            formatLabel={formatItem}
            excludeId={excludeId}
            error={error}
        />
    );
}
