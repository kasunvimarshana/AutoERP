import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { parsePositiveInteger } from '../../shared/utils';
import { useCreatePurchasePayment, usePurchaseInvoices } from '../hooks';

export function PurchasePaymentCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams] = useSearchParams();
    const initialInvoiceId = parsePositiveInteger(searchParams.get('invoiceId'), 0);
    const [invoiceId, setInvoiceId] = useState(String(initialInvoiceId || ''));
    const [paymentNumber, setPaymentNumber] = useState('');
    const [paymentDate, setPaymentDate] = useState(new Date().toISOString().slice(0, 10));
    const [amount, setAmount] = useState('');
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [accountId, setAccountId] = useState('');
    const [currencyId, setCurrencyId] = useState('');
    const [formError, setFormError] = useState<string | null>(null);
    const invoicesQuery = usePurchaseInvoices({ tenant_id: tenantId, page: 1, per_page: 100, direction: 'inbound', sort: '-updated_at' });
    const createMutation = useCreatePurchasePayment();

    async function submit() {
        setFormError(null);
        try {
            await createMutation.mutateAsync({
                tenant_id: tenantId,
                payment_number: paymentNumber,
                payment_date: paymentDate,
                amount: Number(amount),
                base_amount: Number(amount),
                direction: 'outbound',
                payment_method_id: Number(paymentMethodId),
                account_id: Number(accountId),
                currency_id: currencyId ? Number(currencyId) : null,
                exchange_rate: 1,
                status: 'draft',
                allocations: invoiceId ? [{ document_type: 'invoice', document_id: Number(invoiceId), allocated_amount: Number(amount) }] : [],
            });
            showToast({ title: 'Outbound payment created', description: paymentNumber, tone: 'success' });
            navigate('/purchase/invoices');
        } catch (error) {
            setFormError(error instanceof Error ? error.message : 'Unable to create purchase payment.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/invoices' }, { label: 'Create Payment' }]} description="Record an outbound supplier payment and allocate it to an invoice." title="Create Purchase Payment" />
            <ContentCard>
                {invoicesQuery.isPending ? <LoadingState lines={8} /> : invoicesQuery.isError ? <ErrorState description={invoicesQuery.error.message} title="Unable to load invoices" /> : (
                    <div className="space-y-6">
                        <FormGrid>
                            <FormField label="Invoice"><Select value={invoiceId} onChange={(e) => setInvoiceId(e.target.value)}><option value="">Select invoice</option>{invoicesQuery.data?.items.map((invoice) => <option key={invoice.id} value={invoice.id}>{invoice.invoice_number} - {invoice.balance}</option>)}</Select></FormField>
                            <FormField label="Payment Number"><Input value={paymentNumber} onChange={(e) => setPaymentNumber(e.target.value)} /></FormField>
                            <FormField label="Payment Date"><Input type="date" value={paymentDate} onChange={(e) => setPaymentDate(e.target.value)} /></FormField>
                            <FormField label="Amount"><Input type="number" value={amount} onChange={(e) => setAmount(e.target.value)} /></FormField>
                            <FormField label="Payment Method ID"><Input value={paymentMethodId} onChange={(e) => setPaymentMethodId(e.target.value)} /></FormField>
                            <FormField label="Cash/Bank Account ID"><Input value={accountId} onChange={(e) => setAccountId(e.target.value)} /></FormField>
                            <FormField label="Currency ID"><Input value={currencyId} onChange={(e) => setCurrencyId(e.target.value)} /></FormField>
                        </FormGrid>
                        {formError ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
                        <div className="flex justify-end gap-3"><Link to="/purchase/invoices"><Button type="button" variant="secondary">Cancel</Button></Link><Button onClick={() => void submit()} disabled={createMutation.isPending} type="button">Create Payment</Button></div>
                    </div>
                )}
            </ContentCard>
        </div>
    );
}
