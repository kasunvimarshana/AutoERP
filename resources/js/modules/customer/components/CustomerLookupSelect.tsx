import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCustomers } from '../customerApi';
import type { CustomerSummary } from '../customerTypes';

const formatCustomer = (customer: CustomerSummary) => `${customer.code} - ${customer.name}`;

export function CustomerLookupSelect({ value, onChange, error, disabled }: {
    value: CustomerSummary | null;
    onChange: (customer: CustomerSummary | null) => void;
    error?: string;
    disabled?: boolean;
}) {
    return <GenericLookupSelect label="Customer" value={value} onChange={onChange} search={searchCustomers} formatLabel={formatCustomer} error={error} disabled={disabled} />;
}
