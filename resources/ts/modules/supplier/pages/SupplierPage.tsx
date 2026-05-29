import { Link } from 'react-router-dom';
import { MasterDataWorkspace } from '../../../shared/components/business/MasterDataWorkspace';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { suppliers } from '../mock/supplierMock';
import type { Supplier } from '../types/supplier.types';

export function SupplierPage() {
    return (
        <MasterDataWorkspace<Supplier>
            backendNotes={['AP finance defaults', 'Bank account validation', 'Tax profile validation', 'Optional supplier user access']}
            columns={[
                { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/suppliers/${row.id}`}>{row.code}</Link> },
                { header: 'Name', key: 'name' },
                { header: 'Contact', key: 'contact' },
                { header: 'Payment Profile', key: 'paymentProfile' },
                { header: 'User Access', key: 'userAccess' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            createPath="/suppliers/new"
            description="Suppliers, contacts, addresses, bank accounts, tax profile, finance defaults, and optional user access."
            fields={[
                { label: 'Supplier name', name: 'name', placeholder: 'Legal or trading name' },
                { label: 'Primary contact', name: 'contact', placeholder: 'Contact person' },
                { label: 'Email', name: 'email', placeholder: 'accounts@example.com', type: 'email' },
                { label: 'Phone', name: 'phone', placeholder: '+94...' },
                { help: 'Backend validates AP accounts and payment defaults.', label: 'Finance defaults', name: 'financeDefaults', placeholder: 'Select after backend integration', type: 'select' },
                { help: 'Optional. Supplier can exist without a login account.', label: 'User access', name: 'userAccess', placeholder: 'No linked user', type: 'select' },
            ]}
            listPath="/suppliers"
            previewTitle="Supplier backend preview"
            rows={suppliers}
            sections={[
                { title: 'Profile', description: 'Supplier identity, status, procurement preferences, and tenant references.' },
                { title: 'Contacts', description: 'Procurement, accounts, and operations contacts.' },
                { title: 'Addresses', description: 'Registered, billing, and delivery addresses.' },
                { title: 'Bank Accounts', description: 'Supplier banking details collected for backend validation.' },
                { title: 'Tax Profile', description: 'Tax registration input; backend validates tax treatment.' },
                { title: 'Finance Defaults', description: 'Backend owns AP defaults, accounts, terms, and payable behavior.' },
                { title: 'Optional User Access', description: 'Supplier portal access is optional and linked explicitly.' },
            ]}
            title="Suppliers"
        />
    );
}
