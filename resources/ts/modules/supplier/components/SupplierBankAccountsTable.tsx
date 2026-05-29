import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type { SupplierBankAccount } from '../types/supplier.types';

export function SupplierBankAccountsTable({ accounts }: { accounts: SupplierBankAccount[] }) {
    if (!accounts.length) {
        return <EmptyState description="Bank accounts are collected here and validated by backend finance/payment workflows." title="No bank accounts" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Account Name', key: 'accountName' },
                { header: 'Account Number', key: 'accountNumber' },
                { header: 'Bank', key: 'bankName' },
                { header: 'Currency', key: 'currency' },
                { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Primary' : 'Secondary'} /> },
                { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={accounts}
        />
    );
}
