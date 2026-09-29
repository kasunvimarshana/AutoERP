import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { ReactNode } from 'react';
import { searchCustomers } from '../customerApi';
import type { CustomerSummary } from '../customerTypes';

const DEFAULT_LABEL = 'Customer';

export type CustomerLookupOption = Pick<NamedResource, 'id' | 'name'> & {
    mobile?: string | null;
};

export const formatCustomerLookupLabel = (customer: CustomerLookupOption) => customer.name;

export function renderCustomerLookupOption(customer: CustomerLookupOption): ReactNode {
    return (
        <div className="flex min-w-0 items-center gap-2">
            <span className="truncate">{customer.name}</span>
            <span className="shrink-0 whitespace-nowrap text-xs text-slate-500">{customer.mobile?.trim() || '—'}</span>
        </div>
    );
}

export function CustomerLookupSelect({
    value,
    onChange,
    error,
    disabled,
    required,
    label = DEFAULT_LABEL,
    placeholder,
    loadOnOpen,
    minSearchLength,
    dropdownPlacement,
}: {
    value: CustomerSummary | null;
    onChange: (customer: CustomerSummary | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
    label?: string;
    placeholder?: string;
    loadOnOpen?: boolean;
    minSearchLength?: number;
    dropdownPlacement?: 'top' | 'bottom';
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={searchCustomers}
            formatLabel={formatCustomerLookupLabel}
            renderOption={(customer) => renderCustomerLookupOption(customer)}
            error={error}
            disabled={disabled}
            required={required}
            placeholder={placeholder}
            loadOnOpen={loadOnOpen}
            minSearchLength={minSearchLength}
            dropdownPlacement={dropdownPlacement}
        />
    );
}
