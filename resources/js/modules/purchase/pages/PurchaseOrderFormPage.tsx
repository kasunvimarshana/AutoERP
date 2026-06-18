import { useParams } from 'react-router-dom';
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
        return <PurchaseOrderForm />;
    }

    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    if (result.data.status !== 'draft') {
        return (
            <>
                <CapabilityNotice>Only draft purchase orders can be edited.</CapabilityNotice>
            </>
        );
    }

    return <PurchaseOrderForm order={result.data} />;
}
