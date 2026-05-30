import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GrnInventoryEffectPanel,
    PurchaseActivityTimeline,
    PurchaseFinancePostingPanel,
    PurchaseReturnForm,
    PurchaseReturnLineTable,
    PurchaseReturnTable,
    PurchaseSourceReferencePanel,
    SupplierRefundPanel,
} from '../components/PurchaseComponents';
import { financePostingPreview, inventoryEffects, purchaseActivity, supplierRefunds } from '../mock/purchaseMock';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseReturn } from '../types/purchase.types';

export function PurchaseReturnListPage() {
    const [rows, setRows] = useState<PurchaseReturn[]>([]);

    useEffect(() => {
        purchaseApi.returns.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/returns/new"><Button>Create Return</Button></Link>} eyebrow="Purchase" subtitle="Purchase returns with backend-owned returnable quantity, stock/AP effects, and refund eligibility." title="Purchase Returns" />
            <SearchFilterBar placeholder="Search return number, supplier, source document..." />
            {rows.length ? <PurchaseReturnTable rows={rows} /> : <EmptyState description="No purchase returns returned yet." title="No returns" />}
        </div>
    );
}

export function PurchaseReturnCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Return" subtitle="Create from invoice, GRN, PO, or direct source. Backend validates returnable quantities and effects." title="Create Purchase Return" />
            <PurchaseReturnForm />
        </div>
    );
}

export function PurchaseReturnDetailPage() {
    const { id = 'pret-001' } = useParams();
    const [record, setRecord] = useState<PurchaseReturn>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.returns.get(id).then((response) => setRecord(response.data));
    }, [id]);

    if (!record) {
        return <EmptyState description="Loading purchase return details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button variant="blue">Post via Backend</Button>} eyebrow="Purchase Return" subtitle="Return detail with source, inventory effect, AP effect, refunds, and audit." title={record.returnNumber} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Return Lines', value: 'lines' },
                    { label: 'Source Document', value: 'source' },
                    { label: 'Inventory Effect', value: 'inventory' },
                    { label: 'AP/Finance Effect', value: 'finance' },
                    { label: 'Refunds', value: 'refunds' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchaseReturnTable rows={[record]} /> : null}
            {activeTab === 'lines' ? <PurchaseReturnLineTable rows={record.lines} /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={record.sourceReference} /> : null}
            {activeTab === 'inventory' ? <GrnInventoryEffectPanel effects={inventoryEffects} /> : null}
            {activeTab === 'finance' ? <PurchaseFinancePostingPanel preview={financePostingPreview} /> : null}
            {activeTab === 'refunds' ? <SupplierRefundPanel refunds={supplierRefunds} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={purchaseActivity} /> : null}
        </div>
    );
}
