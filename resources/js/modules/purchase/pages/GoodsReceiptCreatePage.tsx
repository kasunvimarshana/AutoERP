import { useSearchParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { GoodsReceiptForm } from '../components/GoodsReceiptForm';

export default function GoodsReceiptCreatePage() {
    const [searchParams] = useSearchParams();
    const sourceId = Number(searchParams.get('purchase_order_id') ?? searchParams.get('source_id'));

    return (
        <>
            <ContentHeader title="New goods receipt" description="Receive approved purchase order lines into inventory through the Purchase backend." />
            <GoodsReceiptForm sourcePurchaseOrderId={Number.isFinite(sourceId) && sourceId > 0 ? sourceId : undefined} />
        </>
    );
}
