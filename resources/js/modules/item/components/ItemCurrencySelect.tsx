import { searchCurrencies } from '@/shared/api/referenceApi';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';

const formatCurrency = (currency: NamedResource) =>
    `${currency.code ?? ''} - ${currency.name}${currency.symbol ? ` (${currency.symbol})` : ''}`;

export function ItemCurrencySelect({ value, onChange, error }: {
    value: NamedResource | null;
    onChange: (currency: NamedResource | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect
        label="Currency"
        value={value}
        onChange={onChange}
        search={searchCurrencies}
        formatLabel={formatCurrency}
        error={error}
        placeholder="Search currency code"
        loadOnOpen
        minSearchLength={0}
    />;
}
