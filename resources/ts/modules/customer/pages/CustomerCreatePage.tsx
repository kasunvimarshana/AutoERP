import { PageHeader } from '../../../shared/components/business/PageHeader';
import { CustomerForm } from '../components/CustomerForm';

export function CustomerCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Master Data"
                subtitle="Create the customer profile first. Contacts, addresses, vehicles, finance defaults, and optional user access are managed after save."
                title="Create Customer"
            />
            <CustomerForm mode="create" />
        </div>
    );
}
