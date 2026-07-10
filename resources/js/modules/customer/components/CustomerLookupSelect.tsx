import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCustomers } from '../customerApi';
import type { CustomerSummary } from '../customerTypes';

const formatCustomer = (customer: CustomerSummary) =>
    [customer.code, customer.name].filter(Boolean).join(' - ');

export function CustomerLookupSelect({ value, onChange, error, disabled, required }: {
    value: CustomerSummary | null;
    onChange: (customer: CustomerSummary | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}) {
    return <GenericLookupSelect label="Customer" value={value} onChange={onChange} search={searchCustomers} formatLabel={formatCustomer} error={error} disabled={disabled} required={required} />;
}
