import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';

const reports = [
    ['vehicle-rental.fleet-availability', 'Fleet Availability Report'],
    ['vehicle-rental.agreement-register', 'Rental Agreement Register'],
    ['vehicle-rental.active-rentals', 'Active Rentals Report'],
    ['vehicle-rental.overdue-rentals', 'Overdue Rentals Report'],
    ['vehicle-rental.running-chart', 'Running Chart Report'],
    ['vehicle-rental.running-chart-customer', 'Running Chart for Customer / Lessee'],
    ['vehicle-rental.running-chart-owner', 'Running Chart for Vehicle Owner'],
    ['vehicle-rental.running-chart-monthly', 'Running Chart Monthly Summary'],
    ['vehicle-rental.usage-summary', 'Usage Summary Report'],
    ['vehicle-rental.allocation-history', 'Vehicle Allocation History'],
    ['vehicle-rental.mileage-summary', 'Rental Mileage Summary'],
    ['vehicle-rental.overtime-summary', 'Rental Overtime Summary'],
    ['vehicle-rental.day-night-out-summary', 'Day Out / Night Out Summary'],
    ['vehicle-rental.charges', 'Rental Charge Report'],
    ['vehicle-rental.revenue', 'Rental Revenue Report'],
    ['vehicle-rental.inbound-cost', 'Inbound Rental Cost Report'],
    ['vehicle-rental.profitability', 'Rental Profitability Report'],
    ['vehicle-rental.deposit-liability', 'Deposit Liability Report'],
    ['vehicle-rental.deposit-refund', 'Deposits, Advances, and Refunds'],
    ['vehicle-rental.customer-outstanding', 'Customer Outstanding Rental Report'],
    ['vehicle-rental.owner-payable', 'Owner / Supplier Payable Rental Report'],
    ['vehicle-rental.customer-ageing', 'Customer Rental Outstanding Ageing'],
    ['vehicle-rental.owner-payable-ageing', 'Owner / Supplier Payable Ageing'],
    ['vehicle-rental.uninvoiced-revenue', 'Uninvoiced Approved Revenue'],
    ['vehicle-rental.unprocessed-payable-cost', 'Unprocessed Payable Cost'],
    ['vehicle-rental.partially-invoiced', 'Partially Invoiced Charges'],
    ['vehicle-rental.expense-recovery', 'Expense and Recovery Report'],
    ['vehicle-rental.payment-allocation', 'Payment and Receipt Allocation'],
    ['vehicle-rental.vehicle-utilization', 'Vehicle Utilization Report'],
    ['vehicle-rental.revenue-licence-expiry', 'Vehicle Revenue Licence Expiry Report'],
    ['vehicle-rental.document-expiry', 'Vehicle Document Expiry Report'],
    ['vehicle-rental.tax-withholding-traceability', 'Tax and Withholding Traceability'],
] as const;

export default function RentalReportsPage() {
    return (
        <>
            <ContentHeader title="Vehicle rental reports" description="Operational and financial reporting through the existing Reporting module." />
            <Panel title="Available reports">
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {reports.map(([key, title]) => (
                        <Link key={key} to={`/reports/${key}`} className="rounded-lg border border-slate-200 p-4 transition hover:border-sky-300 hover:bg-sky-50">
                            <strong className="block text-slate-900">{title}</strong>
                            <span className="mt-1 block text-xs text-slate-500">{key}</span>
                        </Link>
                    ))}
                </div>
            </Panel>
        </>
    );
}
