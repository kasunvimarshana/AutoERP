import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SupplierForm } from '../components/SupplierForm';

export function SupplierCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Master Data"
                subtitle="Create the supplier business profile first. Contacts, addresses, bank accounts, finance defaults, tax profile, and optional user access are managed after save."
                title="Create Supplier"
            />
            <SupplierForm mode="create" />
        </div>
    );
}
