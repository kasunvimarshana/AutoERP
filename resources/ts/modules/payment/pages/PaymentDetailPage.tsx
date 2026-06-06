import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { DateDisplay, FormSection, MoneyDisplay, PageHeader, SecondaryLink, StatusBadge } from '../../../shared/components/erp/ErpUi';
import { paymentApi } from '../services/paymentApi';
import type { Payment } from '../types/payment.types';

export function PaymentDetailPage() {
    const { id } = useParams();
    const [payment, setPayment] = useState<Payment | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void paymentApi.get(Number(id)).then((response) => { if (active) setPayment(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load payment.'); }); return () => { active = false; }; }, [id]);
    if (!payment && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!payment) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="mx-auto max-w-4xl space-y-5"><PageHeader actions={<SecondaryLink to="/payments">Back</SecondaryLink>} eyebrow={payment.direction} subtitle="Payment allocation and remaining advance balance." title={payment.paymentNumber} /><FormSection title="Payment summary"><dl className="grid gap-5 sm:grid-cols-3"><Info label="Amount" value={<MoneyDisplay value={payment.amount} />} /><Info label="Allocated" value={<MoneyDisplay value={payment.allocatedAmount} />} /><Info label="Unallocated" value={<MoneyDisplay value={payment.unallocatedAmount} />} /><Info label="Party" value={`${payment.partyType} #${payment.partyId}`} /><Info label="Date" value={<DateDisplay value={payment.paymentDate} />} /><Info label="Status" value={<StatusBadge value={payment.status} />} /></dl></FormSection></div>;
}

function Info({ label, value }: { label: string; value: React.ReactNode }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
