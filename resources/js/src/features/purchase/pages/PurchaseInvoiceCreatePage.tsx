import { useEffect, useState } from 'react';
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
import { useSuppliers } from '../../suppliers/hooks';
import { parsePositiveInteger } from '../../shared/utils';
import { useCreatePurchaseInvoice, useGrn, usePurchaseOrder, usePurchaseOrders } from '../hooks';

export function PurchaseInvoiceCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams] = useSearchParams();
    const initialPoId = parsePositiveInteger(searchParams.get('purchaseOrderId'), 0);
    const initialGrnId = parsePositiveInteger(searchParams.get('grnId'), 0);
    const [sourceType, setSourceType] = useState<'po' | 'grn'>(initialGrnId > 0 ? 'grn' : 'po');
    const [sourceId, setSourceId] = useState(String(initialGrnId || initialPoId || ''));
    const [supplierId, setSupplierId] = useState('');
    const [invoiceNumber, setInvoiceNumber] = useState('');
    const [invoiceType, setInvoiceType] = useState('standard');
    const [invoiceDate, setInvoiceDate] = useState(new Date().toISOString().slice(0, 10));
    const [dueDate, setDueDate] = useState(new Date().toISOString().slice(0, 10));
    const [currencyId, setCurrencyId] = useState('');
    const [grandTotal, setGrandTotal] = useState('0');
    const [formError, setFormError] = useState<string | null>(null);
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const purchaseOrdersQuery = usePurchaseOrders({ tenant_id: tenantId, page: 1, per_page: 100, sort: '-updated_at' });
    const sourcePoQuery = usePurchaseOrder(sourceType === 'po' ? Number(sourceId) : 0, sourceType === 'po' && Number(sourceId) > 0);
    const sourceGrnQuery = useGrn(sourceType === 'grn' ? Number(sourceId) : 0, sourceType === 'grn' && Number(sourceId) > 0);
    const createMutation = useCreatePurchaseInvoice();
    const source = sourceType === 'po' ? sourcePoQuery.data : sourceGrnQuery.data;
    const loading = suppliersQuery.isPending || purchaseOrdersQuery.isPending || sourcePoQuery.isPending || sourceGrnQuery.isPending;
    const lookupError = suppliersQuery.error ?? purchaseOrdersQuery.error ?? sourcePoQuery.error ?? sourceGrnQuery.error;

    useEffect(() => {
        if (!source) {
            return;
        }
        setSupplierId(String(source.supplier_id));
        setCurrencyId(source.currency_id ? String(source.currency_id) : '');
        setGrandTotal(String(source.grand_total ?? 0));
    }, [source]);

    async function submit() {
        setFormError(null);
        try {
            const invoice = await createMutation.mutateAsync({
                tenant_id: tenantId,
                direction: 'inbound',
                invoice_type: invoiceType,
                invoice_number: invoiceNumber,
                status: 'draft',
                party_type: 'supplier',
                party_id: Number(supplierId),
                invoice_date: invoiceDate,
                due_date: dueDate,
                currency_id: currencyId ? Number(currencyId) : null,
                exchange_rate: 1,
                subtotal: Number(grandTotal),
                grand_total: Number(grandTotal),
                balance: Number(grandTotal),
                references: sourceId ? [{ document_type: sourceType === 'po' ? 'purchase_order' : 'grn', document_id: Number(sourceId) }] : [],
            });
            showToast({ title: 'Purchase invoice created', description: invoice.invoice_number, tone: 'success' });
            navigate(`/purchase/invoices/${invoice.id}`);
        } catch (error) {
            setFormError(error instanceof Error ? error.message : 'Unable to create purchase invoice.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/invoices' }, { label: 'Create Invoice' }]} description="Create a simplified inbound supplier bill from a PO or GRN." title="Create Purchase Invoice" />
            <ContentCard>
                {loading ? <LoadingState lines={8} /> : lookupError ? <ErrorState description={lookupError.message} title="Unable to load invoice setup" /> : (
                    <div className="space-y-6">
                        <FormGrid>
                            <FormField label="Source Type"><Select value={sourceType} onChange={(e) => { setSourceType(e.target.value as 'po' | 'grn'); setSourceId(''); }}><option value="po">Purchase Order</option><option value="grn">GRN</option></Select></FormField>
                            <FormField label="Source ID"><Input value={sourceId} onChange={(e) => setSourceId(e.target.value)} placeholder={sourceType === 'po' ? 'PO ID' : 'GRN ID'} /></FormField>
                            <FormField label="Supplier"><Select value={supplierId} onChange={(e) => setSupplierId(e.target.value)}><option value="">Select supplier</option>{suppliersQuery.data?.items.map((supplier) => <option key={supplier.id} value={supplier.id}>{supplier.name}</option>)}</Select></FormField>
                            <FormField label="Invoice Number"><Input value={invoiceNumber} onChange={(e) => setInvoiceNumber(e.target.value)} /></FormField>
                            <FormField label="Invoice Type"><Select value={invoiceType} onChange={(e) => setInvoiceType(e.target.value)}><option value="standard">Standard</option><option value="credit_note">Credit Note</option><option value="debit_note">Debit Note</option></Select></FormField>
                            <FormField label="Invoice Date"><Input type="date" value={invoiceDate} onChange={(e) => setInvoiceDate(e.target.value)} /></FormField>
                            <FormField label="Due Date"><Input type="date" value={dueDate} onChange={(e) => setDueDate(e.target.value)} /></FormField>
                            <FormField label="Currency ID"><Input value={currencyId} onChange={(e) => setCurrencyId(e.target.value)} /></FormField>
                            <FormField label="Grand Total"><Input type="number" value={grandTotal} onChange={(e) => setGrandTotal(e.target.value)} /></FormField>
                        </FormGrid>
                        {formError ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
                        <div className="flex justify-end gap-3"><Link to="/purchase/invoices"><Button type="button" variant="secondary">Cancel</Button></Link><Button onClick={() => void submit()} disabled={createMutation.isPending} type="button">Create Invoice</Button></div>
                    </div>
                )}
            </ContentCard>
        </div>
    );
}
