import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PaymentTable } from '../components/PaymentComponents';
import { paymentApi } from '../services/paymentApi';
import type { Payment } from '../types/payment.types';

export function PaymentListPage() {
    const [rows, setRows] = useState<Payment[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        paymentApi.listPayments()
            .then((response) => setRows(response.data))
            .finally(() => setLoading(false));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/payments/payments/new"><Button>New Payment</Button></Link>} eyebrow="Payments" subtitle="Generic payments across customers, suppliers, other parties, advances, refunds, and module sources." title="Payment Records" />
            <SearchFilterBar placeholder="Search payment number, party, source, reference, method..." />
            {loading ? <EmptyState description="Loading payment records from service layer." title="Loading payments" /> : rows.length ? <PaymentTable payments={rows} /> : <EmptyState description="No payments returned yet." title="No payments" />}
        </div>
    );
}
