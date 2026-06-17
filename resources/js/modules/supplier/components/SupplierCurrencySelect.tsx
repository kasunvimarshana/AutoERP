import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import { searchCurrencies } from '../supplierApi';

const formatCurrency = (currency: NamedResource) => `${currency.code ?? ''} - ${currency.name}${currency.symbol ? ` (${currency.symbol})` : ''}`;

export function SupplierCurrencySelect({ value, onChange, error }: {
    value: NamedResource | null;
    onChange: (currency: NamedResource | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Currency" value={value} onChange={onChange} search={searchCurrencies} formatLabel={formatCurrency} error={error} placeholder="Search currency code" loadOnOpen minSearchLength={0} />;
}
