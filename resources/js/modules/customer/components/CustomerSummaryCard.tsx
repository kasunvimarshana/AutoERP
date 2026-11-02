import { DetailGrid } from '@/shared/components/DetailGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { readableRelation } from '@/shared/utils/object';
import type { Customer } from '../customerTypes';

export function CustomerSummaryCard({ customer }: { customer: Customer }) {
    return <DetailGrid items={[
        { label: 'Customer number', value: customer.customer_number },
        { label: 'Status', value: <StatusBadge status={customer.status} /> },
        { label: 'Type', value: customer.customer_type.replaceAll('_', ' ') },
        { label: 'Legal name', value: customer.legal_name },
        { label: 'Email', value: customer.email },
        { label: 'Phone', value: customer.phone ?? customer.mobile },
        { label: 'Currency', value: readableRelation(customer.default_currency) },
        { label: 'Reference credit limit', value: <MoneyDisplay value={customer.credit_limit} /> },
        { label: 'Opening balance', value: <MoneyDisplay value={customer.opening_balance} /> },
        { label: 'Credit allowed', value: customer.is_credit_allowed ? 'Yes' : 'No' },
        { label: 'Advance allowed', value: customer.is_advance_allowed ? 'Yes' : 'No' },
        { label: 'Tax exempt', value: customer.is_tax_exempt ? 'Yes' : 'No' },
        { label: 'Marketing consent', value: customer.marketing_consent ? 'Yes' : 'No' },
        { label: 'Preferred channel', value: customer.preferred_communication_channel?.replaceAll('_', ' ') ?? '-' },
        { label: 'Categories', value: customer.categories?.map((category) => category.name).join(', ') || '-' },
        { label: 'Notes', value: customer.notes },
    ]} />;
}
