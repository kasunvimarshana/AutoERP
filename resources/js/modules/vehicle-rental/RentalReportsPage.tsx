import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';

interface ReportCard {
    title: string;
    description: string;
    to: string;
    badge?: string;
    badgeColor?: string;
}

interface ReportSection {
    category: string;
    description: string;
    reports: ReportCard[];
}

export default function RentalReportsPage() {
    const sections: ReportSection[] = [
        {
            category: 'Fleet Operations & Compliance Reports',
            description: 'Physical fleet utilization, custody tracking, regulatory licence expirations, and trip sheets.',
            reports: [
                {
                    title: 'Fleet Utilization & Status Report',
                    description: 'Physical asset availability, custody status, planned assignments, ownership type, period distance, and utilization rates.',
                    to: '/vehicle-rental/reports/fleet-utilization',
                    badge: 'Fleet Operations',
                    badgeColor: 'bg-blue-100 text-blue-800 border-blue-200',
                },
                {
                    title: 'Vehicle Revenue Licence Expire Report',
                    description: 'Regulatory compliance countdown for fleet revenue licences, owner names, expiration alerts, and renewal status.',
                    to: '/vehicle-rental/reports/revenue-licenses',
                    badge: 'Compliance',
                    badgeColor: 'bg-rose-100 text-rose-800 border-rose-200',
                },
                {
                    title: 'Running Chart 31-Day Matrix Sheet',
                    description: 'Official monthly trip sheet with daily odometers, comparative/cumulative mileage, overtime hours, night outs, and pass outlays.',
                    to: '/vehicle-rental/running-chart/report',
                    badge: 'Lessee & Owner Sheet',
                    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                },
                {
                    title: 'Vehicle Availability Query',
                    description: 'Search fleet availability across planned dispatches and active customer custody to prevent booking collisions.',
                    to: '/vehicle-rental/vehicles/available',
                    badge: 'Dispatch',
                    badgeColor: 'bg-slate-100 text-slate-700 border-slate-200',
                },
            ],
        },
        {
            category: 'Commercial Revenue & Receivables',
            description: 'Customer rental billing, lease cycles, extra usage revenue, and customer aging analysis.',
            reports: [
                {
                    title: 'Rental Revenue & Commercial Billing Summary',
                    description: 'Customer agreement revenue totals, base rent billing, excess distance charges, overtime, night outs, and toll reimbursements.',
                    to: '/vehicle-rental/reports/rental-revenue',
                    badge: 'Revenue P&L',
                    badgeColor: 'bg-blue-100 text-blue-800 border-blue-200',
                },
                {
                    title: "Lessee's Outstanding Age Analysis",
                    description: 'Customer receivables aging report grouped by client, rental agreement reference, and individual vehicle with aging days.',
                    to: '/vehicle-rental/reports/lessee-outstanding',
                    badge: 'Receivables Aging',
                    badgeColor: 'bg-amber-100 text-amber-800 border-amber-200',
                },
                {
                    title: 'Customer Agreements & Base Rent Billing',
                    description: 'Active commercial customer contracts, base rent billing cycles, usage charge assessments, and security deposit receipts.',
                    to: '/vehicle-rental/customer/agreements',
                    badge: 'Contracts',
                    badgeColor: 'bg-slate-100 text-slate-800 border-slate-200',
                },
            ],
        },
        {
            category: 'Supplier Payables & Financial Performance',
            description: 'External owner supply settlements, vehicle profit & loss, direct operating costs, and risk management.',
            reports: [
                {
                    title: 'Vehicle Owner Disbursement & Payables Ledger',
                    description: 'Lessor settlements, monthly base lease entitlements, usage revenue sharing, toll/fuel deductions, and withholding tax (WHT) ledger statements.',
                    to: '/vehicle-rental/reports/owner-disbursements',
                    badge: 'Lessor Payables',
                    badgeColor: 'bg-blue-100 text-blue-800 border-blue-200',
                },
                {
                    title: 'Fleet Vehicle Profitability & Unit Economics',
                    description: 'Vehicle-level profit & loss, direct operating costs (owner lease, fuel, maintenance, tolls), gross margins, and per-kilometer economics.',
                    to: '/vehicle-rental/reports/vehicle-profitability',
                    badge: 'Unit Economics',
                    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                },
                {
                    title: 'Rental Incident & Damage Risk Report',
                    description: 'Fleet risk management register, damage event classifications, liability determinations, and photographic evidence audits.',
                    to: '/vehicle-rental/reports/incidents-summary',
                    badge: 'Risk & Claims',
                    badgeColor: 'bg-rose-100 text-rose-800 border-rose-200',
                },
                {
                    title: 'Vehicle Finance & Bank Leasing Payables',
                    description: 'Bank leasing contracts, installment amortization schedules, due status sync, and inbound supplier invoices.',
                    to: '/vehicle-rental/finance-agreements',
                    badge: 'Bank Leases',
                    badgeColor: 'bg-slate-100 text-slate-800 border-slate-200',
                },
            ],
        },
    ];

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Vehicle Rental Reports & Business Intelligence"
                description="Comprehensive reporting suite covering fleet operational custody, regulatory compliance, commercial revenue, lessor disbursements, and asset profitability."
            />

            {/* Featured Running Chart Banner */}
            <div className="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50/80 via-slate-50 to-white p-6 shadow-sm">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                            Sri Lankan Monthly Basis Standard (pmdeblogsheet2.frx / pmcre1logsheet2.frx)
                        </span>
                        <h2 className="mt-2 text-xl font-bold text-slate-900">
                            Running Chart Landscape A4 Matrix Sheet
                        </h2>
                        <p className="mt-1 max-w-2xl text-sm text-slate-600">
                            Generate the complete 31-day operational sheet with comparative & cumulative mileage, overtime hours, night outs, expressway toll passes, and driver/owner signatures in both Lessee and Owner modes.
                        </p>
                    </div>
                    <div className="shrink-0 flex items-center gap-3">
                        <Link
                            to="/vehicle-rental/running-chart/report"
                            className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
                        >
                            Open Running Chart Sheet
                        </Link>
                    </div>
                </div>
            </div>

            {/* Report Sections */}
            <div className="space-y-6">
                {sections.map(section => (
                    <Panel key={section.category} title={section.category}>
                        <p className="mb-4 text-xs text-slate-500">{section.description}</p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {section.reports.map(report => (
                                <Link
                                    key={report.title}
                                    to={report.to}
                                    className="group relative flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-blue-300 hover:bg-white hover:shadow-sm"
                                >
                                    <div>
                                        <div className="flex items-center justify-between gap-2">
                                            <h3 className="font-semibold text-slate-900 group-hover:text-blue-600 transition">
                                                {report.title}
                                            </h3>
                                            {report.badge && (
                                                <span className={`rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${report.badgeColor ?? 'bg-slate-100 text-slate-700'}`}>
                                                    {report.badge}
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-1.5 text-xs text-slate-600 line-clamp-2">
                                            {report.description}
                                        </p>
                                    </div>
                                    <span className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 group-hover:underline">
                                        View report &rarr;
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </Panel>
                ))}
            </div>
        </div>
    );
}
