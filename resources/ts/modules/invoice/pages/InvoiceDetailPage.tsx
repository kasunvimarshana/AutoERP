import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { invoiceApi } from '../services/invoiceApi';
import type { Invoice } from '../types/invoice.types';

export function InvoiceDetailPage() {
    const { id } = useParams();
    const [invoice, setInvoice] = useState<Invoice | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void invoiceApi.get(Number(id)).then((response) => { if (active) setInvoice(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load invoice.'); }); return () => { active = false; }; }, [id]);
    if (!invoice && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!invoice) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="mx-auto max-w-4xl space-y-5"><header className="flex justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{invoice.documentType}</p><h1 className="text-3xl font-bold">{invoice.invoiceNumber}</h1></div><Link className="font-semibold text-blue-700" to="/invoices">Back</Link></header><section className="grid gap-4 rounded-xl border bg-white p-5 shadow-sm sm:grid-cols-3"><Info label="Status" value={invoice.status} /><Info label="Grand total" value={Number(invoice.grandTotal).toLocaleString()} /><Info label="Balance due" value={Number(invoice.balanceDue).toLocaleString()} /><Info label="Paid" value={Number(invoice.paidTotal).toLocaleString()} /><Info label="Date" value={invoice.invoiceDate} /><Info label="Due" value={invoice.dueDate || 'Not set'} /></section></div>;
}

function Info({ label, value }: { label: string; value: string }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
