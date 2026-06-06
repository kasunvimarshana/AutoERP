import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { DateDisplay, FormSection, MoneyDisplay, PageHeader, SecondaryLink, StatusBadge } from '../../../shared/components/erp/ErpUi';
import { invoiceApi } from '../services/invoiceApi';
import type { Invoice } from '../types/invoice.types';

export function InvoiceDetailPage() {
    const { id } = useParams();
    const [invoice, setInvoice] = useState<Invoice | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void invoiceApi.get(Number(id)).then((response) => { if (active) setInvoice(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load invoice.'); }); return () => { active = false; }; }, [id]);
    if (!invoice && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!invoice) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="mx-auto max-w-4xl space-y-5"><PageHeader actions={<SecondaryLink to="/invoices">Back</SecondaryLink>} eyebrow={invoice.documentType.replaceAll('_', ' ')} subtitle="Invoice totals and current settlement position." title={invoice.invoiceNumber} /><FormSection title="Invoice summary"><dl className="grid gap-5 sm:grid-cols-3"><Info label="Status" value={<StatusBadge value={invoice.status} />} /><Info label="Grand total" value={<MoneyDisplay value={invoice.grandTotal} />} /><Info label="Balance due" value={<MoneyDisplay value={invoice.balanceDue} />} /><Info label="Paid" value={<MoneyDisplay value={invoice.paidTotal} />} /><Info label="Date" value={<DateDisplay value={invoice.invoiceDate} />} /><Info label="Due" value={<DateDisplay value={invoice.dueDate} />} /></dl></FormSection></div>;
}

function Info({ label, value }: { label: string; value: React.ReactNode }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
