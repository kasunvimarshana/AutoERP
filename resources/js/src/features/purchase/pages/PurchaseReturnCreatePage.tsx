import { useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { useToast } from '../../../app/providers/ToastProvider';
import { useAuth } from '../../auth/context/AuthContext';
import { useTenant } from '../../auth/context/TenantContext';
import { useProducts, useUnitsOfMeasure } from '../../products/hooks';
import { useSuppliers } from '../../suppliers/hooks';
import { useWarehouses } from '../../warehouse/hooks';
import { parsePositiveInteger } from '../../shared/utils';
import { useCreatePurchaseReturn, useGrn, usePurchaseOrder, usePurchaseOrders } from '../hooks';

type ReturnLine = {
    original_grn_line_id?: number | null;
    original_purchase_order_line_id?: number | null;
    item_id: string;
    uom_id: string;
    return_qty: string;
    unit_price: string;
    restocking_fee: string;
    condition: string;
    disposition: string;
};

export function PurchaseReturnCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { user } = useAuth();
    const [searchParams] = useSearchParams();
    const sourcePoId = parsePositiveInteger(searchParams.get('purchaseOrderId'), 0);
    const sourceGrnId = parsePositiveInteger(searchParams.get('grnId'), 0);
    const [supplierId, setSupplierId] = useState('');
    const [warehouseId, setWarehouseId] = useState('');
    const [currencyId, setCurrencyId] = useState('');
    const [returnNumber, setReturnNumber] = useState('');
    const [returnReason, setReturnReason] = useState('');
    const [formError, setFormError] = useState<string | null>(null);
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 250, sort: 'name' });
    const uomsQuery = useUnitsOfMeasure({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const purchaseOrdersQuery = usePurchaseOrders({ tenant_id: tenantId, page: 1, per_page: 100, sort: '-updated_at' });
    const sourcePoQuery = usePurchaseOrder(sourcePoId, sourcePoId > 0);
    const sourceGrnQuery = useGrn(sourceGrnId, sourceGrnId > 0);
    const createMutation = useCreatePurchaseReturn();
    const [lines, setLines] = useState<ReturnLine[]>([
        { item_id: '', uom_id: '', return_qty: '1', unit_price: '0', restocking_fee: '0', condition: 'good', disposition: 'return_to_vendor' },
    ]);
    const sourceLines = useMemo(() => sourceGrnQuery.data?.grn_lines ?? sourceGrnQuery.data?.lines ?? sourcePoQuery.data?.purchase_order_lines ?? sourcePoQuery.data?.lines ?? [], [sourceGrnQuery.data, sourcePoQuery.data]);
    const lookupError = suppliersQuery.error ?? warehousesQuery.error ?? productsQuery.error ?? uomsQuery.error ?? purchaseOrdersQuery.error ?? sourcePoQuery.error ?? sourceGrnQuery.error;
    const loading = suppliersQuery.isPending || warehousesQuery.isPending || productsQuery.isPending || uomsQuery.isPending || purchaseOrdersQuery.isPending || (sourcePoId > 0 && sourcePoQuery.isPending) || (sourceGrnId > 0 && sourceGrnQuery.isPending);

    function importSourceLines() {
        const source = sourceLines.map((line) => ({
            original_grn_line_id: 'grn_header_id' in line ? line.id : null,
            original_purchase_order_line_id: 'purchase_order_id' in line ? line.id : line.purchase_order_line_id ?? null,
            item_id: String(line.item_id),
            uom_id: String(line.uom_id),
            return_qty: String(Number('received_qty' in line ? line.received_qty : line.ordered_qty) || 1),
            unit_price: String(line.unit_price),
            restocking_fee: '0',
            condition: 'good',
            disposition: 'return_to_vendor',
        }));

        if (source.length > 0) {
            setLines(source);
        }
    }

    async function submit() {
        setFormError(null);
        try {
            const created = await createMutation.mutateAsync({
                tenant_id: tenantId,
                supplier_id: Number(supplierId),
                original_purchase_order_id: sourcePoId || null,
                original_grn_id: sourceGrnId || null,
                return_number: returnNumber || undefined,
                currency_id: currencyId ? Number(currencyId) : null,
                exchange_rate: 1,
                return_date: new Date().toISOString().slice(0, 10),
                return_reason: returnReason || null,
                created_by: user?.id ?? null,
                lines: lines.map((line) => ({
                    original_grn_line_id: line.original_grn_line_id ?? null,
                    original_purchase_order_line_id: line.original_purchase_order_line_id ?? null,
                    item_id: Number(line.item_id),
                    warehouse_id: warehouseId ? Number(warehouseId) : null,
                    uom_id: Number(line.uom_id),
                    return_qty: Number(line.return_qty),
                    unit_price: Number(line.unit_price),
                    restocking_fee: Number(line.restocking_fee || 0),
                    condition: line.condition || null,
                    disposition: line.disposition || null,
                })),
            });
            showToast({ title: 'Purchase return created', description: created.return_number, tone: 'success' });
            navigate(`/purchase/returns/${created.id}`);
        } catch (error) {
            setFormError(error instanceof Error ? error.message : 'Unable to create purchase return.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/returns' }, { label: 'Create Return' }]} description="Create a supplier return from a PO, GRN, or manual item selection." title="Create Purchase Return" />
            <ContentCard>
                {loading ? <LoadingState lines={8} /> : lookupError ? <ErrorState description={lookupError.message} title="Unable to load return setup" /> : (
                    <div className="space-y-6">
                        <SectionCard action={sourceLines.length > 0 ? <Button onClick={importSourceLines} type="button" variant="secondary">Pull source lines</Button> : null} description="Reference is optional; source lines can be pulled when opened from a PO or GRN." title="Return header">
                            <FormGrid>
                                <FormField label="Supplier" required><Select value={supplierId} onChange={(e) => setSupplierId(e.target.value)}><option value="">Select supplier</option>{suppliersQuery.data?.items.map((supplier) => <option key={supplier.id} value={supplier.id}>{supplier.name}</option>)}</Select></FormField>
                                <FormField label="Warehouse"><Select value={warehouseId} onChange={(e) => setWarehouseId(e.target.value)}><option value="">Select warehouse</option>{warehousesQuery.data?.items.map((warehouse) => <option key={warehouse.id} value={warehouse.id}>{warehouse.name}</option>)}</Select></FormField>
                                <FormField label="Return Number"><Input value={returnNumber} onChange={(e) => setReturnNumber(e.target.value)} placeholder="Auto if backend sequence is enabled" /></FormField>
                                <FormField label="Currency ID"><Input value={currencyId} onChange={(e) => setCurrencyId(e.target.value)} /></FormField>
                                <FormField label="Reason"><Input value={returnReason} onChange={(e) => setReturnReason(e.target.value)} /></FormField>
                            </FormGrid>
                        </SectionCard>
                        <SectionCard action={<Button onClick={() => setLines([...lines, { item_id: '', uom_id: '', return_qty: '1', unit_price: '0', restocking_fee: '0', condition: 'good', disposition: 'return_to_vendor' }])} type="button" variant="secondary">Add Row</Button>} description="Returned quantities and supplier disposition." title="Return lines">
                            <div className="space-y-3">
                                {lines.map((line, index) => (
                                    <div key={index} className="grid gap-3 rounded-xl border border-stone-200 p-3 md:grid-cols-7">
                                        <Select value={line.item_id} onChange={(e) => setLines(lines.map((item, i) => i === index ? { ...item, item_id: e.target.value } : item))}><option value="">Item</option>{productsQuery.data?.items.map((product) => <option key={product.id} value={product.id}>{product.name}</option>)}</Select>
                                        <Select value={line.uom_id} onChange={(e) => setLines(lines.map((item, i) => i === index ? { ...item, uom_id: e.target.value } : item))}><option value="">UOM</option>{uomsQuery.data?.items.map((uom) => <option key={uom.id} value={uom.id}>{uom.symbol || uom.name}</option>)}</Select>
                                        <Input type="number" value={line.return_qty} onChange={(e) => setLines(lines.map((item, i) => i === index ? { ...item, return_qty: e.target.value } : item))} />
                                        <Input type="number" value={line.unit_price} onChange={(e) => setLines(lines.map((item, i) => i === index ? { ...item, unit_price: e.target.value } : item))} />
                                        <Input type="number" value={line.restocking_fee} onChange={(e) => setLines(lines.map((item, i) => i === index ? { ...item, restocking_fee: e.target.value } : item))} />
                                        <Select value={line.condition} onChange={(e) => setLines(lines.map((item, i) => i === index ? { ...item, condition: e.target.value } : item))}><option value="good">Good</option><option value="damaged">Damaged</option><option value="expired">Expired</option><option value="defective">Defective</option></Select>
                                        <Button onClick={() => setLines(lines.filter((_, i) => i !== index))} type="button" variant="secondary">Remove</Button>
                                    </div>
                                ))}
                            </div>
                        </SectionCard>
                        {formError ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
                        <div className="flex justify-end gap-3"><Link to="/purchase/returns"><Button type="button" variant="secondary">Cancel</Button></Link><Button onClick={() => void submit()} disabled={createMutation.isPending} type="button">Create Return</Button></div>
                    </div>
                )}
            </ContentCard>
        </div>
    );
}
