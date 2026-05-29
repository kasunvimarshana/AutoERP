import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { PaymentForm } from '../components/PaymentComponents';
import { getPaymentById } from '../mock/paymentMock';

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
    const payment = getPaymentById(id ?? '');

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to={`/payments/payments/${payment.id}`}><Button variant="secondary">Back</Button></Link>} eyebrow="Payments" subtitle="Edit draft/payment setup fields only. Backend remains authoritative for settlement and posting." title={`Edit ${payment.paymentNumber}`} />
            <PaymentForm
                initial={{
                    amount: payment.amount.replaceAll(',', ''),
                    currency: payment.currency,
                    direction: payment.direction,
                    partyType: payment.partyType,
                    paymentDate: payment.paymentDate,
                    reference: payment.reference,
                    sourceModule: payment.sourceModule,
                    sourceReference: payment.sourceReference,
                }}
                mode="edit"
            />
        </div>
    );
}
