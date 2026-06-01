import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { RentalAgreementTable, VehicleRentalDashboardCards, VehicleRentalPageHeader } from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalAgreement, VehicleRentalDashboardMetric } from '../types/vehicleRental.types';

export function VehicleRentalDashboardPage() {
    const [metrics, setMetrics] = useState<VehicleRentalDashboardMetric[]>([]);
    const [agreements, setAgreements] = useState<VehicleRentalAgreement[]>([]);
    const [error, setError] = useState<string>();

    useEffect(() => {
        Promise.all([
            vehicleRentalApi.dashboard.summary(),
            vehicleRentalApi.agreements.list(),
        ]).then(([metricResponse, agreementResponse]) => {
            setMetrics(metricResponse.data);
            setAgreements(agreementResponse.data);
        }).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load Vehicle Rental dashboard.'));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<><Link to="/vehicle-rental/agreements/new"><Button>New Agreement</Button></Link><Link to="/vehicle-rental/availability"><Button variant="blue">Check Availability</Button></Link></>}
                subtitle="Rental workflows for availability, agreements, rate terms, running charts, invoices, payments, provider payables, replacements, breakdowns, documents, and finance posting."
                title="Vehicle Rental Dashboard"
            />
            <VehicleRentalDashboardCards metrics={metrics} />
            <PreviewPanel status="Domain" title="VehicleRental Workflow">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    {['Check vehicle availability', 'Create rental agreement and rate terms', 'Assign driver or external provider', 'Capture usage through running charts', 'Preview customer billing and provider payable', 'Generate invoice, collect payment, post AR/AP finance'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Recent Agreements</h2>
                {error ? <EmptyState description={error} title="Vehicle Rental backend unavailable" /> : <RentalAgreementTable rows={agreements} />}
            </div>
        </div>
    );
}

export { VehicleRentalDashboardPage as VehicleRentalPage };
