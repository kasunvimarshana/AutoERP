import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
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
import type { Voucher, VoucherAllocation, VoucherAuditEntry, VoucherDocument, VoucherPaymentImpactPreview, VoucherPostingPreview } from '../types/voucher.types';

export function VoucherListPage() {
    const [rows, setRows] = useState<Voucher[]>([]);
    const [filters, setFilters] = useState<Record<string, DataToolbarFilterValue>>({});
    const [search, setSearch] = useState('');
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        let active = true;
        setIsLoading(true);
        voucherApi.vouchers.list({
            partyType: String(filters.party_type ?? ''),
            perPage: 25,
            search,
            status: String(filters.status ?? ''),
            type: String(filters.type ?? ''),
        })
            .then((response) => {
                if (!active) {
                    return;
                }

                setRows(response.data);
                setError(null);
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            })
            .finally(() => {
                if (active) {
                    setIsLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [filters.party_type, filters.status, filters.type, search]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        setFilters((current) => ({ ...current, [filterId]: value }));
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Link to="/vouchers/new"><Button>New Voucher</Button></Link>}
                subtitle="Voucher list covers payment, receipt, journal, contra, expense, advance, refund, write-off, and adjustment vouchers."
                title="Vouchers"
            />
            {error ? <EmptyState description={error.message} title="Voucher list failed" /> : null}
            <DataToolbar
                disabled={isLoading}
                filterValues={filters}
                filters={[
                    { id: 'type', label: 'Type', options: ['payment', 'receipt', 'journal', 'adjustment'].map((value) => ({ label: value.replaceAll('_', ' '), value })), type: 'select' },
                    { id: 'status', label: 'Status', options: ['draft', 'submitted', 'approved', 'posted'].map((value) => ({ label: value, value })), type: 'status' },
                    { id: 'party_type', label: 'Party', options: ['customer', 'supplier', 'employee'].map((value) => ({ label: value, value })), type: 'select' },
                ]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved voucher views need a user-preferences backend endpoint."
                searchPlaceholder="Search voucher number, party, reference..."
                searchValue={search}
            />
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
            <VoucherForm />
        </div>
    );
}

export function VoucherEditPage() {
    const { id = '' } = useParams();
    const [voucher, setVoucher] = useState<Voucher | null>(null);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        let active = true;
        setVoucher(null);
        voucherApi.vouchers.get(id)
            .then((response) => {
                if (active) {
                    setVoucher(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, [id]);

    if (error) {
        return <EmptyState description={error.message} title="Voucher failed to load" />;
    }

    if (!voucher) {
        return <EmptyState description="Loading voucher from backend..." title="Loading voucher" />;
    }

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
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [voucher, setVoucher] = useState<Voucher | null>(null);
    const [allocations, setAllocations] = useState<VoucherAllocation[]>();
    const [document, setDocument] = useState<VoucherDocument>();
    const [history, setHistory] = useState<VoucherAuditEntry[]>();
    const [paymentImpact, setPaymentImpact] = useState<VoucherPaymentImpactPreview>();
    const [postingPreview, setPostingPreview] = useState<VoucherPostingPreview>();
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        let active = true;
        setVoucher(null);
        setAllocations(undefined);
        setDocument(undefined);
        setHistory(undefined);
        setPaymentImpact(undefined);
        setPostingPreview(undefined);
        voucherApi.vouchers.get(id)
            .then((response) => {
                if (active) {
                    setVoucher(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, [id]);

    useEffect(() => {
        if (!voucher) {
            return undefined;
        }

        let active = true;
        if (activeTab === 'allocations' && allocations === undefined) {
            voucherApi.allocations.list(voucher.id, { perPage: 25 }).then((response) => {
                if (active) {
                    setAllocations(response.data);
                }
            }).catch((caught: Error) => active && setError(caught));
        }

        if (activeTab === 'history' && history === undefined) {
            voucherApi.vouchers.history(voucher.id).then((response) => {
                if (active) {
                    setHistory(response.data);
                }
            }).catch((caught: Error) => active && setError(caught));
        }

        if (activeTab === 'posting' && postingPreview === undefined) {
            voucherApi.utilities.previewPosting(voucher.id).then((response) => {
                if (active) {
                    setPostingPreview(response as VoucherPostingPreview);
                }
            }).catch((caught: Error) => active && setError(caught));
        }

        if (activeTab === 'payment' && paymentImpact === undefined) {
            voucherApi.utilities.previewPaymentImpact({ voucherId: voucher.id }).then((response) => {
                if (active) {
                    setPaymentImpact(response as VoucherPaymentImpactPreview);
                }
            }).catch((caught: Error) => active && setError(caught));
        }

        if (activeTab === 'document' && document === undefined) {
            voucherApi.documents.preview(voucher.id).then((response) => {
                if (!active) {
                    return;
                }

                const data = response.data as Record<string, unknown>;
                setDocument({
                    documentNumber: String(data.documentNumber ?? data.document_number ?? ''),
                    status: String(data.status ?? ''),
                    template: String(data.template ?? data.template_name ?? ''),
                });
            }).catch((caught: Error) => active && setError(caught));
        }

        return () => {
            active = false;
        };
    }, [activeTab, allocations, document, history, paymentImpact, postingPreview, voucher]);

    if (error && !voucher) {
        return <EmptyState description={error.message} title="Voucher failed to load" />;
    }

    if (!voucher) {
        return <EmptyState description="Loading voucher from backend..." title="Loading voucher" />;
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/${voucher.id}/edit`}><Button>Edit</Button></Link><Button variant="blue">Preview Posting</Button></>}
                subtitle="Voucher detail keeps approval, allocations, posting, payment impact, document output, attachments, comments, and history visible without frontend calculations."
                title={voucher.voucherNumber}
            />
            {error ? <EmptyState description={error.message} title="Voucher section failed" /> : null}
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
            {activeTab === 'allocations' ? <VoucherAllocationPanel allocations={allocations} voucher={voucher} /> : null}
            {activeTab === 'approval' ? <VoucherApprovalPanel voucher={voucher} /> : null}
            {activeTab === 'posting' ? <VoucherPostingPreviewPanel preview={postingPreview} voucher={voucher} /> : null}
            {activeTab === 'payment' ? <VoucherPaymentImpactPanel preview={paymentImpact} voucher={voucher} /> : null}
            {activeTab === 'document' ? <VoucherDocumentPanel document={document} voucher={voucher} /> : null}
            {activeTab === 'attachments' ? <EmptyState description="No voucher attachment endpoint is exposed in this frontend contract yet." title="Attachments unavailable" /> : null}
            {activeTab === 'comments' ? <EmptyState description="No voucher comments endpoint is exposed in this frontend contract yet." title="Comments unavailable" /> : null}
            {activeTab === 'history' ? <VoucherActivityTimeline rows={history ?? []} /> : null}
        </div>
    );
}
