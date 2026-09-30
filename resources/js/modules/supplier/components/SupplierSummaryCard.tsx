import { DetailGrid } from '@/shared/components/DetailGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { readableRelation } from '@/shared/utils/object';
import type { Supplier } from '../supplierTypes';

export function SupplierSummaryCard({ supplier }: { supplier: Supplier }) {
    return <DetailGrid items={[
        { label: 'Supplier number', value: supplier.supplier_number },
        { label: 'Status', value: <StatusBadge status={supplier.status} /> },
        { label: 'Type', value: supplier.supplier_type.replaceAll('_', ' ') },
        { label: 'Legal name', value: supplier.name },
        { label: 'Email', value: supplier.email },
        { label: 'Phone', value: supplier.phone ?? supplier.mobile },
        { label: 'Currency', value: readableRelation(supplier.default_currency) },
        { label: 'Reference credit limit', value: <MoneyDisplay value={supplier.credit_limit} /> },
        { label: 'Credit allowed', value: supplier.is_credit_allowed ? 'Yes' : 'No' },
        { label: 'Advance allowed', value: supplier.is_advance_allowed ? 'Yes' : 'No' },
        { label: 'Categories', value: supplier.categories?.map((category) => category.name).join(', ') || '-' },
        { label: 'Notes', value: supplier.notes },
    ]} />;
}
