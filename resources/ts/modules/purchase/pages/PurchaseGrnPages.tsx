import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GrnForm,
    GrnInventoryEffectPanel,
    GrnLineTable,
    GrnTable,
    PurchaseActivityTimeline,
    PurchaseInvoiceTable,
    PurchaseSourceReferencePanel,
    PurchaseWorkflowActions,
} from '../components/PurchaseComponents';
import { inventoryEffects, purchaseActivity, purchaseInvoices } from '../mock/purchaseMock';
import { purchaseApi } from '../services/purchaseApi';
import type { GoodsReceivedNote } from '../types/purchase.types';

export function GrnListPage() {
    const [rows, setRows] = useState<GoodsReceivedNote[]>([]);

    useEffect(() => {
        purchaseApi.grns.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/grns/new"><Button>Create GRN</Button></Link>} eyebrow="Purchase" subtitle="Goods receipt notes manage received quantities and backend-owned inventory effects." title="Goods Received Notes" />
            <SearchFilterBar placeholder="Search GRN number, supplier, PO, item..." />
            {rows.length ? <GrnTable rows={rows} /> : <EmptyState description="No GRNs returned yet." title="No GRNs" />}
        </div>
    );
}

export function GrnCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="GRN" subtitle="Create from PO or direct GRN when settings allow. Backend validates stock receiving rules." title="Create Goods Receipt Note" />
            <GrnForm />
        </div>
    );
}

export function GrnEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="GRN" subtitle="Update draft GRN inputs. Stock movements are not calculated in the frontend." title="Edit Goods Receipt Note" />
            <GrnForm mode="edit" />
        </div>
    );
}

export function GrnDetailPage() {
    const { id = 'grn-001' } = useParams();
    const [grn, setGrn] = useState<GoodsReceivedNote>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.grns.get(id).then((response) => setGrn(response.data));
    }, [id]);

    if (!grn) {
        return <EmptyState description="Loading GRN details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to={`/purchase/grns/${grn.id}/edit`}><Button variant="secondary">Edit</Button></Link>} eyebrow="Goods Receipt Note" subtitle="GRN detail with received lines, source PO, inventory effect, invoices, and audit." title={grn.grnNumber} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Received Lines', value: 'lines' },
                    { label: 'Source PO', value: 'source' },
                    { label: 'Inventory Effect', value: 'inventory' },
                    { label: 'Invoices', value: 'invoices' },
                    { label: 'Documents', value: 'documents' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchaseWorkflowActions entityId={grn.id} entityType="grn_header" status={grn.status} /> : null}
            {activeTab === 'lines' ? <GrnLineTable rows={grn.lines} /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={grn.sourcePo} /> : null}
            {activeTab === 'inventory' ? <GrnInventoryEffectPanel effects={inventoryEffects.filter((effect) => effect.sourceReference === grn.grnNumber)} /> : null}
            {activeTab === 'invoices' ? <PurchaseInvoiceTable rows={purchaseInvoices.filter((invoice) => invoice.sourceReference === grn.grnNumber)} /> : null}
            {activeTab === 'documents' ? <PurchaseSourceReferencePanel reference={grn.grnNumber} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={purchaseActivity} /> : null}
        </div>
    );
}
