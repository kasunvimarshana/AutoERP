import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { getVoucherById } from '../mock/voucherMock';
import { voucherApi } from '../services/voucherApi';
import {
    VoucherActivityTimeline,
    VoucherAllocationPanel,
    VoucherApprovalPanel,
    VoucherDocumentPanel,
    VoucherForm,
    VoucherLineTable,
    VoucherPageHeader,
    VoucherPaymentImpactPanel,
    VoucherPostingPreviewPanel,
    VoucherTable,
    VoucherWorkflowActions,
} from '../components/VoucherComponents';
import type { Voucher } from '../types/voucher.types';

export function VoucherListPage() {
    const [rows, setRows] = useState<Voucher[]>([]);

    useEffect(() => {
        voucherApi.vouchers.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Link to="/vouchers/new"><Button>New Voucher</Button></Link>}
                subtitle="Voucher list covers payment, receipt, journal, contra, expense, advance, refund, write-off, and adjustment vouchers."
                title="Vouchers"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_180px_160px]">
                    <Input placeholder="Search voucher number, party, reference..." />
                    <Select options={[{ label: 'Any type', value: '' }, { label: 'Payment', value: 'payment' }, { label: 'Receipt', value: 'receipt' }, { label: 'Journal', value: 'journal' }, { label: 'Adjustment', value: 'adjustment' }]} />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Draft', value: 'draft' }, { label: 'Submitted', value: 'submitted' }, { label: 'Approved', value: 'approved' }, { label: 'Posted', value: 'posted' }]} />
                    <Select options={[{ label: 'Any party', value: '' }, { label: 'Customer', value: 'customer' }, { label: 'Supplier', value: 'supplier' }, { label: 'Employee', value: 'employee' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <VoucherTable rows={rows} />
        </div>
    );
}

export function VoucherCreatePage() {
    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to="/vouchers"><Button variant="secondary">Cancel</Button></Link><Button>Save Draft</Button><Button variant="blue">Preview Backend Validation</Button></>}
                subtitle="Create voucher header, lines, allocations, and previews. Backend owns balance validation, payment impact, posting, workflow, and document generation."
                title="New Voucher"
            />
            <VoucherForm voucher={getVoucherById('vou-001')} />
        </div>
    );
}

export function VoucherEditPage() {
    const { id = 'vou-001' } = useParams();
    const [voucher, setVoucher] = useState<Voucher>(getVoucherById(id));

    useEffect(() => {
        voucherApi.vouchers.get(id).then((response) => setVoucher(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/${voucher.id}`}><Button variant="secondary">View</Button></Link><Button>Save Draft</Button><Button variant="blue">Preview Posting</Button></>}
                subtitle="Edit draft voucher inputs. Backend remains authoritative for totals, allocation balance, approval, posting, payment, and document output."
                title={`Edit ${voucher.voucherNumber}`}
            />
            <VoucherForm voucher={voucher} />
        </div>
    );
}

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Lines', value: 'lines' },
    { label: 'Allocations', value: 'allocations' },
    { label: 'Approval Workflow', value: 'approval' },
    { label: 'Posting Preview / Finance', value: 'posting' },
    { label: 'Payment Impact', value: 'payment' },
    { label: 'Document', value: 'document' },
    { label: 'Attachments', value: 'attachments' },
    { label: 'Comments', value: 'comments' },
    { label: 'History / Audit', value: 'history' },
];

export function VoucherDetailPage() {
    const { id = 'vou-001' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [voucher, setVoucher] = useState<Voucher>(getVoucherById(id));

    useEffect(() => {
        voucherApi.vouchers.get(id).then((response) => setVoucher(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/${voucher.id}/edit`}><Button>Edit</Button></Link><Button variant="blue">Preview Posting</Button></>}
                subtitle="Voucher detail keeps approval, allocations, posting, payment impact, document output, attachments, comments, and history visible without frontend calculations."
                title={voucher.voucherNumber}
            />
            <Card className="p-5"><Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <Card className="p-5">
                        <div className="grid gap-4 md:grid-cols-2">
                            {[
                                ['Type', voucher.voucherType],
                                ['Party', voucher.party],
                                ['Reference', voucher.referenceNumber],
                                ['Source', `${voucher.sourceReference.sourceModule} / ${voucher.sourceReference.sourceNumber}`],
                                ['Backend debit', voucher.totalDebit],
                                ['Backend credit', voucher.totalCredit],
                                ['Backend amount', voucher.totalAmount],
                                ['Payment method', voucher.paymentMethod],
                            ].map(([label, value]) => (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                                    <p className="mt-1 font-semibold text-slate-900">{value}</p>
                                </div>
                            ))}
                        </div>
                    </Card>
                    <VoucherWorkflowActions voucher={voucher} />
                </div>
            ) : null}
            {activeTab === 'lines' ? <VoucherLineTable voucher={voucher} /> : null}
            {activeTab === 'allocations' ? <VoucherAllocationPanel voucher={voucher} /> : null}
            {activeTab === 'approval' ? <VoucherApprovalPanel voucher={voucher} /> : null}
            {activeTab === 'posting' ? <VoucherPostingPreviewPanel voucher={voucher} /> : null}
            {activeTab === 'payment' ? <VoucherPaymentImpactPanel voucher={voucher} /> : null}
            {activeTab === 'document' ? <VoucherDocumentPanel voucher={voucher} /> : null}
            {activeTab === 'attachments' ? <PreviewPanel status="Attachment" title="Attachments">Attachment panel integration placeholder for backend document/file endpoints.</PreviewPanel> : null}
            {activeTab === 'comments' ? <PreviewPanel status="Comment" title="Comments">Comment panel integration placeholder for backend activity/comment endpoints.</PreviewPanel> : null}
            {activeTab === 'history' ? <VoucherActivityTimeline rows={voucher.activity} /> : null}
        </div>
    );
}
