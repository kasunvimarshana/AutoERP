import { ContentHeader } from '@/shared/components/ContentHeader';
import { GoodsReceiptForm } from '../components/GoodsReceiptForm';

export default function GoodsReceiptCreatePage() {
    return (
        <>
            <ContentHeader title="New goods receipt" description="Receive approved purchase order lines into inventory through the Purchase backend." />
            <GoodsReceiptForm />
        </>
    );
}
