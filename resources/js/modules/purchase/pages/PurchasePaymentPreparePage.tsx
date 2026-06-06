import { ContentHeader } from '@/shared/components/ContentHeader';
import { PurchasePaymentPreparationForm } from '../components/PurchasePaymentPreparationForm';

export default function PurchasePaymentPreparePage() {
    return (
        <>
            <ContentHeader title="Prepare supplier payment" />
            <PurchasePaymentPreparationForm />
        </>
    );
}
