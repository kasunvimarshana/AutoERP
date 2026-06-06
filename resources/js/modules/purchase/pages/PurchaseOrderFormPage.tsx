import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { createPurchaseOrder } from '../purchaseApi';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Textarea } from '@/shared/components/Textarea';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';

export default function PurchaseOrderFormPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [form, setForm] = useState({ purchase_order_date: new Date().toISOString().slice(0, 10), expected_delivery_date: '', notes: '', quantity: '1', unit_price: '0' });
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [item, setItem] = useState<NamedResource | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (id) {
        return (
            <>
                <ContentHeader title="Edit purchase order" />
                <CapabilityNotice>The Purchase API does not currently expose an update endpoint. Editing is intentionally unavailable until the backend defines draft update rules.</CapabilityNotice>
            </>
        );
    }
    return (
        <>
            <ContentHeader title="New purchase order" description="A readable first-line form using the transactional purchase order create endpoint." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                setSubmitting(true);
                setError(null);
                try {
                    const saved = await createPurchaseOrder({
                        purchase_order_date: form.purchase_order_date,
                        supplier_type: supplier ? 'supplier' : undefined,
                        supplier_id: supplier?.id,
                        warehouse_id: warehouse?.id,
                        expected_delivery_date: form.expected_delivery_date || undefined,
                        notes: form.notes || undefined,
                        lines: [{ item_id: item?.id ?? 0, ordered_quantity: form.quantity, unit_price: form.unit_price }],
                    });
                    navigate(`/purchase/orders/${saved.id}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Order header">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Input label="Order date" type="date" required value={form.purchase_order_date} onChange={(event) => setForm({ ...form, purchase_order_date: event.target.value })} />
                        <LookupSelect label="Supplier" value={supplier} onChange={setSupplier} search={lookupApi.suppliers} placeholder="Search suppliers..." />
                        <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} placeholder="Search warehouses..." />
                        <Input label="Expected delivery" type="date" value={form.expected_delivery_date} onChange={(event) => setForm({ ...form, expected_delivery_date: event.target.value })} />
                    </div>
                    <div className="mt-4"><Textarea label="Notes" value={form.notes} onChange={(event) => setForm({ ...form, notes: event.target.value })} /></div>
                </Panel>
                <Panel title="First order line">
                    <div className="grid gap-4 sm:grid-cols-3">
                        <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.items} placeholder="Search items..." />
                        <Input label="Quantity" type="number" min="0.000001" step="0.000001" required value={form.quantity} onChange={(event) => setForm({ ...form, quantity: event.target.value })} />
                        <Input label="Unit price" type="number" min="0" step="0.000001" required value={form.unit_price} onChange={(event) => setForm({ ...form, unit_price: event.target.value })} />
                    </div>
                </Panel>
                <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>Create order</Button></div>
            </form>
        </>
    );
}
