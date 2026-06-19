import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { GoodsReceiptForm } from '../components/GoodsReceiptForm';

export default function GoodsReceiptCreatePage() {
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader title="New Goods Receipt" description="Receive approved purchase order lines into inventory through the Purchase backend." />}
        >
            <GoodsReceiptForm />
        </PurchaseDocumentShell>
    );
}
