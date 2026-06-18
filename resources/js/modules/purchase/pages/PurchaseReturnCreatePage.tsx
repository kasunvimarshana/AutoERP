import { useSearchParams } from 'react-router-dom';
import { PurchaseReturnForm } from '../components/PurchaseReturnForm';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';

export default function PurchaseReturnCreatePage() {
    const [searchParams] = useSearchParams();
    const sourceId = Number(searchParams.get('goods_receipt_id') ?? searchParams.get('source_id'));

    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader title="Create Purchase Return" description="Return received supplier goods against a posted goods receipt." />}
        >
            <PurchaseReturnForm sourceGoodsReceiptId={Number.isFinite(sourceId) && sourceId > 0 ? sourceId : undefined} />
        </PurchaseDocumentShell>
    );
}
