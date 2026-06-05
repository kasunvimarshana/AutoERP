import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { VoucherDashboardCards, VoucherPageHeader, VoucherTable } from '../components/VoucherComponents';
import { voucherApi } from '../services/voucherApi';
import type { Voucher, VoucherDashboardMetric } from '../types/voucher.types';

export function VoucherDashboardPage() {
    const [metrics, setMetrics] = useState<VoucherDashboardMetric[]>([]);
    const [rows, setRows] = useState<Voucher[]>([]);

    useEffect(() => {
        voucherApi.dashboard.summary().then((response) => setMetrics(response.data));
        voucherApi.vouchers.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to="/vouchers/new"><Button>New Voucher</Button></Link><Link to="/vouchers/posting-preview"><Button variant="blue">Posting Preview</Button></Link></>}
                subtitle="Generic voucher workflows for payment, receipt, journal, contra, expense, advance, refund, write-off, and adjustment vouchers."
                title="Voucher Dashboard"
            />
            <VoucherDashboardCards metrics={metrics} />
            <PreviewPanel status="Architecture" title="Voucher Module">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
                    {['Multiple voucher types', 'Backend balance validation', 'Approval workflow actions', 'Finance posting preview', 'Payment impact preview', 'Document generation', 'Generic allocations', 'Audit/history'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Recent Vouchers</h2>
                <VoucherTable rows={rows} />
            </div>
        </div>
    );
}

export { VoucherDashboardPage as VoucherPage };
