import { PurchaseReturnForm } from '../components/PurchaseReturnForm';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';

export default function PurchaseReturnCreatePage() {
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader title="Create Purchase Return" description="Return received supplier goods against a posted goods receipt." />}
        >
            <PurchaseReturnForm />
        </PurchaseDocumentShell>
    );
}
