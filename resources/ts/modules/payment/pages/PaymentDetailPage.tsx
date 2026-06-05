import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { paymentApi } from '../services/paymentApi';
import type { Payment } from '../types/payment.types';

export function PaymentDetailPage() {
    const { id } = useParams();
    const [payment, setPayment] = useState<Payment | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void paymentApi.get(Number(id)).then((response) => { if (active) setPayment(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load payment.'); }); return () => { active = false; }; }, [id]);
    if (!payment && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!payment) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="mx-auto max-w-4xl space-y-5"><header className="flex justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{payment.direction}</p><h1 className="text-3xl font-bold">{payment.paymentNumber}</h1></div><Link className="font-semibold text-blue-700" to="/payments">Back</Link></header><section className="grid gap-4 rounded-xl border bg-white p-5 sm:grid-cols-3"><Info label="Amount" value={Number(payment.amount).toLocaleString()} /><Info label="Allocated" value={Number(payment.allocatedAmount).toLocaleString()} /><Info label="Unallocated" value={Number(payment.unallocatedAmount).toLocaleString()} /><Info label="Party" value={`${payment.partyType} #${payment.partyId}`} /><Info label="Date" value={payment.paymentDate} /><Info label="Status" value={payment.status} /></section></div>;
}

function Info({ label, value }: { label: string; value: string }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
