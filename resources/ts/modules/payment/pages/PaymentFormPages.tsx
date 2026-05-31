import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PaymentForm } from '../components/PaymentComponents';
import { paymentApi } from '../services/paymentApi';
import type { Payment } from '../types/payment.types';

export function PaymentCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Payments" subtitle="Create a generic payment input. Backend will number, validate, post, allocate, and settle." title="Create Payment" />
            <PaymentForm />
        </div>
    );
}

export function PaymentEditPage() {
    const { id } = useParams();
    const [payment, setPayment] = useState<Payment | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        paymentApi.getPayment(id ?? '').then((response) => setPayment(response.data)).finally(() => setIsLoading(false));
    }, [id]);

    if (isLoading) return <EmptyState description="Loading payment..." title="Loading" />;
    if (!payment) return <EmptyState description="Payment record was not found." title="Unable to load payment" />;

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to={`/payments/payments/${payment.id}`}><Button variant="secondary">Back</Button></Link>} eyebrow="Payments" subtitle="Edit draft/payment setup fields only. Backend remains authoritative for settlement and posting." title={`Edit ${payment.paymentNumber}`} />
            <PaymentForm
                initial={{
                    amount: payment.amount.replaceAll(',', ''),
                    currency: payment.currency,
                    direction: payment.direction,
                    partyName: payment.party,
                    partyType: payment.partyType,
                    paymentDate: payment.paymentDate,
                    reference: payment.reference,
                    sourceModule: payment.sourceModule,
                    sourceReference: payment.sourceReference,
                    sourceType: payment.sourceType,
                }}
                mode="edit"
                paymentId={payment.id}
            />
        </div>
    );
}
