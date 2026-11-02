import { LinkButton } from '@/shared/components/Button';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { PurchasePaymentCreateForm } from '../components/PurchasePaymentCreateForm';

export default function PurchasePaymentPreparePage() {
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader
                title="Prepare Supplier Payment"
                description="Preview allocations and balances before creating a payment."
                actions={<LinkButton to="/purchase/payments" variant="secondary">Back to Supplier Payments</LinkButton>}
            />}
        >
            <PurchasePaymentCreateForm mode="prepare" />
        </PurchaseDocumentShell>
    );
}
