import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { DateDisplay, FormSection, MoneyDisplay, PageHeader, SecondaryLink, StatusBadge, SummaryCard, SummaryRow } from '../../../shared/components/erp/ErpUi';
import { invoiceApi } from '../services/invoiceApi';
import type { Invoice } from '../types/invoice.types';

export function InvoiceDetailPage() {
    const { id } = useParams();
    const [invoice, setInvoice] = useState<Invoice | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void invoiceApi.get(Number(id)).then((response) => { if (active) setInvoice(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load invoice.'); }); return () => { active = false; }; }, [id]);
    if (!invoice && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!invoice) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="space-y-5"><PageHeader actions={<SecondaryLink to="/invoices">Back</SecondaryLink>} eyebrow={invoice.documentType.replaceAll('_', ' ')} subtitle="Invoice source, totals, credit/debit adjustments, and current settlement position." title={invoice.invoiceNumber} />
        <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
            <div className="space-y-5">
                <FormSection title="Invoice summary"><dl className="grid gap-5 sm:grid-cols-3"><Info label="Party" value={invoice.customerId ? `Customer #${invoice.customerId}` : invoice.supplierId ? `Supplier #${invoice.supplierId}` : 'Not set'} /><Info label="Source document" value={invoice.businessContext.replaceAll('_', ' ')} /><Info label="Direction" value={invoice.ledgerDirection} /><Info label="Status" value={<StatusBadge value={invoice.status} />} /><Info label="Date" value={<DateDisplay value={invoice.invoiceDate} />} /><Info label="Due" value={<DateDisplay value={invoice.dueDate} />} /></dl></FormSection>
                <FormSection title="Lines">{invoice.lines?.length ? <div className="overflow-x-auto"><table className="w-full min-w-[680px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-3 py-2">Description</th><th className="px-3 py-2">Qty</th><th className="px-3 py-2">Unit price</th><th className="px-3 py-2">Discount</th><th className="px-3 py-2">Tax</th></tr></thead><tbody>{invoice.lines.map((line, index) => <tr className="border-t" key={line.id ?? index}><td className="px-3 py-3">{line.description}</td><td className="px-3 py-3">{line.quantity}</td><td className="px-3 py-3"><MoneyDisplay value={line.unit_price} /></td><td className="px-3 py-3"><MoneyDisplay value={line.discount_total} /></td><td className="px-3 py-3"><MoneyDisplay value={line.tax_total} /></td></tr>)}</tbody></table></div> : <p className="text-sm text-slate-500">No line details loaded.</p>}</FormSection>
                <FormSection title="Credit / Debit Notes & Adjustments">{invoice.adjustments?.length ? <div className="space-y-2">{invoice.adjustments.map((adjustment, index) => <div className="flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm" key={adjustment.id ?? index}><span>{adjustment.name || adjustment.adjustment_type}</span><span className="font-semibold">{adjustment.effect === 'deduct' ? '-' : '+'}<MoneyDisplay value={adjustment.amount} /></span></div>)}</div> : <p className="text-sm text-slate-500">No header adjustments, credit notes, or debit notes recorded on this invoice.</p>}</FormSection>
            </div>
            <div className="space-y-5 xl:sticky xl:top-24 xl:self-start">
                <SummaryCard title="Settlement">
                    <SummaryRow label="Grand total" value={<MoneyDisplay value={invoice.grandTotal} />} />
                    <SummaryRow label="Paid total" value={<MoneyDisplay value={invoice.paidTotal} />} />
                    <SummaryRow label="Credit notes" value={<MoneyDisplay value={invoice.creditAdjustmentTotal} />} />
                    <SummaryRow label="Debit notes" value={<MoneyDisplay value={invoice.debitAdjustmentTotal} />} />
                    <div className="border-t border-slate-100 pt-4"><SummaryRow label="Balance due" value={<MoneyDisplay className="text-lg font-black" value={invoice.balanceDue} />} /></div>
                </SummaryCard>
                <SummaryCard title="Totals Breakdown">
                    <SummaryRow label="Gross" value={<MoneyDisplay value={invoice.grossTotal} />} />
                    <SummaryRow label="Line discount" value={<MoneyDisplay value={invoice.lineDiscountTotal} />} />
                    <SummaryRow label="Header discount" value={<MoneyDisplay value={invoice.headerDiscountTotal} />} />
                    <SummaryRow label="Tax" value={<MoneyDisplay value={invoice.taxTotal} />} />
                    <SummaryRow label="Charges" value={<MoneyDisplay value={invoice.chargeTotal} />} />
                </SummaryCard>
            </div>
        </div>
    </div>;
}

function Info({ label, value }: { label: string; value: React.ReactNode }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
