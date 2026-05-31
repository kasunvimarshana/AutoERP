import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { DataToolbar } from '../../../shared/components/data/DataToolbar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PaymentTable } from '../components/PaymentComponents';
import { paymentApi } from '../services/paymentApi';
import type { Payment } from '../types/payment.types';

export function PaymentListPage() {
    const [rows, setRows] = useState<Payment[]>([]);
    const [query, setQuery] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        paymentApi.listPayments()
            .then((response) => setRows(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load payments.'))
            .finally(() => setLoading(false));
    }, []);

    const visibleRows = useMemo(() => {
        const q = query.trim().toLowerCase();
        return q
            ? rows.filter((row) => [row.paymentNumber, row.party, row.sourceReference ?? '', row.reference ?? '', row.methodName].some((value) => value.toLowerCase().includes(q)))
            : rows;
    }, [query, rows]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/payments/payments/new"><Button>New Payment</Button></Link>} eyebrow="Payments" subtitle="Generic payments across customers, suppliers, other parties, advances, refunds, and module sources." title="Payment Records" />
            <DataToolbar
                isLoading={loading}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for payment lists."
                searchPlaceholder="Search payment number, party, source, reference, or method..."
                searchValue={query}
            />
            {loading ? <EmptyState description="Loading payment records from service layer." title="Loading payments" /> : null}
            {error ? <EmptyState description={error} title="Payment API unavailable" /> : null}
            {!loading && !error && visibleRows.length ? <PaymentTable payments={visibleRows} /> : null}
            {!loading && !error && !visibleRows.length ? <EmptyState description="No payments returned yet." title="No payments" /> : null}
        </div>
    );
}
