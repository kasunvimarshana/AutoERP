import { useSearchParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { PurchaseReturnForm } from '../components/PurchaseReturnForm';

export default function PurchaseReturnCreatePage() {
    const [searchParams] = useSearchParams();
    const sourceId = Number(searchParams.get('goods_receipt_id') ?? searchParams.get('source_id'));

    return (
        <>
            <ContentHeader title="Create Purchase Return" description="Return received supplier goods against a posted goods receipt." />
            <PurchaseReturnForm sourceGoodsReceiptId={Number.isFinite(sourceId) && sourceId > 0 ? sourceId : undefined} />
        </>
    );
}
