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
            category: 'Operational & Dispatch Reports',
            description: 'Fleet custody, driver trip sheets, physical vehicle tracking, and availability verification.',
            reports: [
                {
                    title: 'Running Chart 31-Day Matrix Sheet',
                    description: 'Official monthly basis trip sheet report with comparative & cumulative mileage, overtime hours, night outs, and excess kilometer analysis.',
                    to: '/vehicle-rental/running-chart/report',
                    badge: 'Official Lessee Sheet',
                    badgeColor: 'bg-blue-100 text-blue-800 border-blue-200',
                },
                {
                    title: 'Vehicle Availability Query',
                    description: 'Search fleet availability across planned dispatches and active customer custody to prevent booking collisions.',
                    to: '/vehicle-rental/vehicles/available',
                    badge: 'Fleet Dispatch',
                    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                },
                {
                    title: 'Vehicle Use & Custody Register',
                    description: 'Audit planned assignments, vehicle handovers, odometer readings, fuel fractions, and atomic vehicle replacements.',
                    to: '/vehicle-rental/vehicle-uses',
                },
                {
                    title: 'Running Chart Trip Log Register',
                    description: 'Review finalized and draft daily log sheets, driver identities, AC operating modes, and trip timestamps.',
                    to: '/vehicle-rental/running-charts',
                },
            ],
        },
        {
            category: 'Financial & Contract Reports',
            description: 'Customer lease billing, owner supply payouts, bank financing contracts, and cost recovery allocations.',
            reports: [
                {
                    title: 'Customer Agreements & Base Rent Billing',
                    description: 'Active commercial customer contracts, base rent billing cycles, usage charge assessments, and security deposit receipts.',
                    to: '/vehicle-rental/customer/agreements',
                    badge: 'Receivables',
                    badgeColor: 'bg-indigo-100 text-indigo-800 border-indigo-200',
                },
                {
                    title: 'Owner Agreements & Settlements',
                    description: 'External vehicle supply contracts, owner payment terms, and vehicle custody source coverage.',
                    to: '/vehicle-rental/owner/agreements',
                    badge: 'Payables',
                    badgeColor: 'bg-purple-100 text-purple-800 border-purple-200',
                },
                {
                    title: 'Vehicle Finance & Leasing Payables',
                    description: 'Bank leasing contracts, installment amortization schedules, due status sync, and inbound supplier invoices.',
                    to: '/vehicle-rental/finance-agreements',
                    badge: 'Bank Leasing',
                    badgeColor: 'bg-amber-100 text-amber-800 border-amber-200',
                },
                {
                    title: 'Fleet Operational Expenses & Allocations',
                    description: 'Fuel, maintenance, toll, and repair costs with multi-party recoveries to customer invoices or owner payout deductions.',
                    to: '/vehicle-rental/expenses',
                },
                {
                    title: 'Incident Evidence & Damage Records',
                    description: 'Recorded fleet incidents with vehicle assignment linkage, review workflows, and private file proof attachments.',
                    to: '/vehicle-rental/incidents',
                },
            ],
        },
    ];

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Vehicle Rental reports & registers"
                description="Access operational fleet registers, official client running chart sheets, and canonical financial audit reports."
            />

            {/* Featured Running Chart Banner */}
            <div className="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 via-indigo-50 to-white p-6 shadow-sm">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                            Sri Lankan Monthly Basis Standard
                        </span>
                        <h2 className="mt-2 text-xl font-bold text-slate-900">
                            Running Chart Landscape A4 Matrix Sheet
                        </h2>
                        <p className="mt-1 max-w-2xl text-sm text-slate-600">
                            Generate the complete 31-day operational sheet with odometer continuity, daily comparative mileage, cumulative mileage, OT breakdown, night outs, driver duty, and excess kilometer calculations. Supports on-screen preview, direct printing, and PDF export.
                        </p>
                    </div>
                    <div className="shrink-0">
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
