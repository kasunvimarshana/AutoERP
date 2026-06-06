import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCustomers } from '../customerApi';
import type { CustomerSummary } from '../customerTypes';

const formatCustomer = (customer: CustomerSummary) => `${customer.code} - ${customer.name}`;
const searchActive = (query: string, signal: AbortSignal) => searchCustomers(query, signal);

export function CustomerLookupSelect({ value, onChange, error }: {
    value: CustomerSummary | null;
    onChange: (customer: CustomerSummary | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Customer" value={value} onChange={onChange} search={searchActive} formatLabel={formatCustomer} error={error} />;
}
