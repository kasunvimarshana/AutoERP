import { LinkButton } from '@/shared/components/Button';
import { PurchaseDebitNoteForm } from '../components/PurchaseDebitNoteForm';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';

export default function PurchaseDebitNoteCreatePage() {
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader
                title="Create Debit Note"
                description="Create a supplier debit note without entering the Purchase Return workflow."
                actions={<LinkButton to="/purchase/debit-notes" variant="secondary">Back to Debit Notes</LinkButton>}
            />}
        >
            <PurchaseDebitNoteForm />
        </PurchaseDocumentShell>
    );
}
