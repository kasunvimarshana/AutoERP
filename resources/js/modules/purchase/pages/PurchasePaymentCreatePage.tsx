import { LinkButton } from '@/shared/components/Button';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { PurchasePaymentCreateForm } from '../components/PurchasePaymentCreateForm';

export default function PurchasePaymentCreatePage() {
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader
                title="Create Supplier Payment"
                description="Create a canonical Payment and allocate it to eligible supplier invoices."
                actions={<LinkButton to="/purchase/payments" variant="secondary">Back to Supplier Payments</LinkButton>}
            />}
        >
            <PurchasePaymentCreateForm />
        </PurchaseDocumentShell>
    );
}
