import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCustomers } from '../customerApi';
import type { CustomerSummary } from '../customerTypes';

const DEFAULT_LABEL = 'Customer';

const formatCustomer = (customer: CustomerSummary) =>
    [customer.code, customer.name].filter(Boolean).join(' - ');

export function CustomerLookupSelect({
    value,
    onChange,
    error,
    disabled,
    required,
    label = DEFAULT_LABEL,
}: {
    value: CustomerSummary | null;
    onChange: (customer: CustomerSummary | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
    label?: string;
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={searchCustomers}
            formatLabel={formatCustomer}
            error={error}
            disabled={disabled}
            required={required}
        />
    );
}
