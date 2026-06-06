import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { DateDisplay, FormSection, MoneyDisplay, PageHeader, SecondaryLink, StatusBadge, SummaryCard, SummaryRow } from '../../../shared/components/erp/ErpUi';
import { paymentApi } from '../services/paymentApi';
import type { Payment } from '../types/payment.types';

export function PaymentDetailPage() {
    const { id } = useParams();
    const [payment, setPayment] = useState<Payment | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void paymentApi.get(Number(id)).then((response) => { if (active) setPayment(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load payment.'); }); return () => { active = false; }; }, [id]);
    if (!payment && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!payment) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="space-y-5"><PageHeader actions={<SecondaryLink to="/payments">Back</SecondaryLink>} eyebrow={payment.direction === 'outbound' ? 'Supplier settlement' : 'Customer receipt'} subtitle="Settlement amount, allocation state, and remaining advance balance." title={payment.paymentNumber} />
        <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
            <FormSection title="Payment summary"><dl className="grid gap-5 sm:grid-cols-3"><Info label="Party" value={`${payment.partyType} #${payment.partyId}`} /><Info label="Date" value={<DateDisplay value={payment.paymentDate} />} /><Info label="Direction" value={payment.direction} /><Info label="Method" value={`#${payment.paymentMethodId}`} /><Info label="Status" value={<StatusBadge value={payment.status} />} /><Info label="Payment ID" value={`#${payment.id}`} /></dl><p className="mt-5 rounded-lg bg-blue-50 p-3 text-sm text-blue-700">Allocations are validated against live invoice balances by the backend. Any unallocated amount remains available as advance credit when supported.</p></FormSection>
            <SummaryCard title="Settlement">
                <SummaryRow label="Payment amount" value={<MoneyDisplay value={payment.amount} />} />
                <SummaryRow label="Allocated" value={<MoneyDisplay value={payment.allocatedAmount} />} />
                <div className="border-t border-slate-100 pt-4"><SummaryRow label="Unallocated / advance" value={<MoneyDisplay className="text-lg font-black" value={payment.unallocatedAmount} />} /></div>
            </SummaryCard>
        </div>
    </div>;
}

function Info({ label, value }: { label: string; value: React.ReactNode }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
