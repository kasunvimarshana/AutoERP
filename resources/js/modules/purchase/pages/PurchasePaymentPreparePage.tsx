import { LinkButton } from '@/shared/components/Button';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { PurchasePaymentPreparationForm } from '../components/PurchasePaymentPreparationForm';

export default function PurchasePaymentPreparePage() {
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader
                title="Create Supplier Payment"
                description="Create a canonical Payment draft and allocate it to eligible supplier invoices."
                actions={<LinkButton to="/purchase/payments" variant="secondary">Back to Supplier Payments</LinkButton>}
            />}
        >
            <PurchasePaymentPreparationForm />
        </PurchaseDocumentShell>
    );
}
