import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { VoucherDashboardCards, VoucherPageHeader, VoucherTable } from '../components/VoucherComponents';
import { voucherApi } from '../services/voucherApi';
import type { Voucher, VoucherDashboardMetric } from '../types/voucher.types';

export function VoucherDashboardPage() {
    const [metrics, setMetrics] = useState<VoucherDashboardMetric[]>([]);
    const [rows, setRows] = useState<Voucher[]>([]);
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        let active = true;
        setIsLoading(true);
        Promise.all([
            voucherApi.dashboard.summary(),
            voucherApi.vouchers.list({ perPage: 5 }),
        ])
            .then(([metricResponse, voucherResponse]) => {
                if (!active) {
                    return;
                }

                setMetrics(metricResponse.data);
                setRows(voucherResponse.data);
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
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to="/vouchers/new"><Button>New Voucher</Button></Link><Link to="/vouchers/posting-preview"><Button variant="blue">Posting Preview</Button></Link></>}
                subtitle="Generic voucher workflows for payment, receipt, journal, contra, expense, advance, refund, write-off, and adjustment vouchers."
                title="Voucher Dashboard"
            />
            {isLoading ? <EmptyState description="Loading voucher dashboard summary..." title="Loading vouchers" /> : null}
            {error ? <EmptyState description={error.message} title="Voucher dashboard failed" /> : null}
            <VoucherDashboardCards metrics={metrics} />
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Recent Vouchers</h2>
                <VoucherTable rows={rows} />
            </div>
        </div>
    );
}

export { VoucherDashboardPage as VoucherPage };
