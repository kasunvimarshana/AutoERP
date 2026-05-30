import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    PurchaseActivityTimeline,
    PurchaseAdvancePanel,
    PurchaseFinancePostingPanel,
    PurchasePaymentAllocationPanel,
    PurchasePaymentForm,
    PurchasePaymentTable,
    PurchaseSourceReferencePanel,
    SupplierRefundPanel,
} from '../components/PurchaseComponents';
import { financePostingPreview, purchaseActivity, purchaseAdvances, supplierRefunds } from '../mock/purchaseMock';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchasePayment } from '../types/purchase.types';

export function PurchasePaymentListPage() {
    const [rows, setRows] = useState<PurchasePayment[]>([]);

    useEffect(() => {
        purchaseApi.payments.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/payments/new"><Button>Create Supplier Payment</Button></Link>} eyebrow="Purchase" subtitle="Purchase-scoped supplier payment workspace backed by the generic Payment module." title="Supplier Payments" />
            <SearchFilterBar placeholder="Search payment number, supplier, method, invoice..." />
            {rows.length ? <PurchasePaymentTable rows={rows} /> : <EmptyState description="No payments returned yet." title="No payments" />}
        </div>
    );
}

export function PurchasePaymentCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Supplier Payment" subtitle="Collect supplier payment input and request backend allocation preview before posting." title="Create Supplier Payment" />
            <PurchasePaymentForm />
        </div>
    );
}

export function PurchasePaymentDetailPage() {
    const { id = 'pay-001' } = useParams();
    const [payment, setPayment] = useState<PurchasePayment>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.payments.get(id).then((response) => setPayment(response.data));
    }, [id]);

    if (!payment) {
        return <EmptyState description="Loading supplier payment details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button variant="blue">Backend Actions</Button>} eyebrow="Supplier Payment" subtitle="Payment detail with allocation, source invoice, finance posting, and history." title={payment.paymentNumber} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Allocations', value: 'allocations' },
                    { label: 'Source Invoice', value: 'source' },
                    { label: 'Finance Posting', value: 'finance' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchasePaymentTable rows={[payment]} /> : null}
            {activeTab === 'allocations' ? <PurchasePaymentAllocationPanel allocations={payment.allocations} /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={payment.reference} /> : null}
            {activeTab === 'finance' ? <PurchaseFinancePostingPanel preview={financePostingPreview} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={purchaseActivity} /> : null}
        </div>
    );
}

export function PurchaseAdvanceListPage() {
    return (
        <div className="space-y-6">
            <PageHeader actions={<Button>Create Advance</Button>} eyebrow="Purchase" subtitle="Supplier advances and later allocations. Remaining balance is backend-owned." title="Supplier Advances" />
            <SearchFilterBar placeholder="Search supplier advances..." />
            <PurchaseAdvancePanel advances={purchaseAdvances} />
        </div>
    );
}

export function SupplierRefundListPage() {
    return (
        <div className="space-y-6">
            <PageHeader actions={<Button>Create Refund</Button>} eyebrow="Purchase" subtitle="Supplier refunds linked to purchase returns or credits. Refund amount and posting are backend-owned." title="Supplier Refunds" />
            <SearchFilterBar placeholder="Search supplier refunds..." />
            <SupplierRefundPanel refunds={supplierRefunds} />
        </div>
    );
}
