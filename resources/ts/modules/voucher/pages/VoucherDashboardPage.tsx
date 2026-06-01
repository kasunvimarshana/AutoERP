import { useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { VoucherDashboardCards, VoucherPageHeader, VoucherTable } from '../components/VoucherComponents';
import { voucherApi } from '../services/voucherApi';
import type { Voucher, VoucherDashboardMetric } from '../types/voucher.types';

export function VoucherDashboardPage() {
    const [metrics, setMetrics] = useState<VoucherDashboardMetric[]>([]);
    const [rows, setRows] = useState<Voucher[]>([]);
    const [error, setError] = useState('');
    const [isLoaded, setIsLoaded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    async function loadDashboardData(): Promise<void> {
        if (isLoading) return;

        setIsLoading(true);
        setError('');

        try {
            const [summaryResponse, voucherResponse] = await Promise.all([voucherApi.dashboard.summary(), voucherApi.vouchers.list()]);
            setMetrics(summaryResponse.data);
            setRows(voucherResponse.data.slice(0, 5));
            setIsLoaded(true);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to load Voucher dashboard.');
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to="/vouchers/new"><Button>New Voucher</Button></Link><Link to="/vouchers/posting-preview"><Button variant="blue">Posting Preview</Button></Link></>}
                subtitle="Generic voucher workflows for payment, receipt, journal, contra, expense, advance, refund, write-off, and adjustment vouchers."
                title="Voucher Dashboard"
            />
            <div className="flex justify-end">
                <Button disabled={isLoading} onClick={() => void loadDashboardData()} type="button" variant="secondary">{isLoaded ? 'Refresh Dashboard Data' : 'Load Dashboard Data'}</Button>
            </div>
            {error ? <EmptyState description={error} title="Voucher dashboard unavailable" /> : null}
            {!isLoaded && !error ? <EmptyState description="Voucher metrics and recent vouchers load only when requested." title="Dashboard data not loaded" /> : null}
            {isLoaded ? <VoucherDashboardCards metrics={metrics} /> : null}
            <PreviewPanel status="Architecture" title="Voucher Module">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
                    {['Multiple voucher types', 'Backend balance validation', 'Approval workflow actions', 'Finance posting preview', 'Payment impact preview', 'Document generation', 'Generic allocations', 'Audit/history'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
            {isLoaded ? <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Recent Vouchers</h2>
                <VoucherTable rows={rows} />
            </div> : null}
        </div>
    );
}

export { VoucherDashboardPage as VoucherPage };
