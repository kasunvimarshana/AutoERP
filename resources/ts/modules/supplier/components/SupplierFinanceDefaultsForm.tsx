import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import type { SupplierFinanceDefaults } from '../types/supplier.types';

export function SupplierFinanceDefaultsForm({ defaults }: { defaults: SupplierFinanceDefaults }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Default currency', value: defaults.defaultCurrency },
                { label: 'Payment term', value: defaults.paymentTerm },
                { label: 'Payable account', value: defaults.payableAccount },
                { label: 'Expense account', value: defaults.expenseAccount },
                { label: 'Credit limit', value: defaults.creditLimit },
            ]}
            status="Backend-owned"
            subtitle="Finance defaults are backend validated. The frontend does not calculate payable balances or accounting values."
            title="Supplier Finance Defaults"
        />
    );
}
