import { useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { useApi } from '@/shared/hooks/useApi';
import { getPurchaseOrder } from '../purchaseApi';
import { PurchaseOrderForm } from '../components/PurchaseOrderForm';

export default function PurchaseOrderFormPage() {
    const id = Number(useParams().id);
    const editing = Number.isFinite(id) && id > 0;
    const result = useApi((signal) => getPurchaseOrder(id, signal), [id], editing);

    if (!editing) {
        return (
            <>
                <ContentHeader title="New purchase order" description="Create a draft purchase order with lines and header adjustments." />
                <PurchaseOrderForm />
            </>
        );
    }

    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    if (result.data.status !== 'draft') {
        return (
            <>
                <ContentHeader title={result.data.purchase_order_number ?? `Purchase order #${result.data.id}`} />
                <CapabilityNotice>Only draft purchase orders can be edited.</CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title={`Edit ${result.data.purchase_order_number ?? `purchase order #${result.data.id}`}`} />
            <PurchaseOrderForm order={result.data} />
        </>
    );
}
