import { useSearchParams } from 'react-router-dom';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { GoodsReceiptForm } from '../components/GoodsReceiptForm';

export default function GoodsReceiptCreatePage() {
    const [searchParams] = useSearchParams();
    const sourceId = Number(searchParams.get('purchase_order_id') ?? searchParams.get('source_id'));

    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader title="New Goods Receipt" description="Receive approved purchase order lines into inventory through the Purchase backend." />}
        >
            <GoodsReceiptForm sourcePurchaseOrderId={Number.isFinite(sourceId) && sourceId > 0 ? sourceId : undefined} />
        </PurchaseDocumentShell>
    );
}
