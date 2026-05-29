import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ModuleHeader } from '../../../layouts/components/ModuleHeader';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { CommentPanel } from '../../../shared/components/business/CommentPanel';
import { DocumentPreview } from '../../../shared/components/business/DocumentPreview';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { jobLines, labourAssignments } from '../mock/vehicleServiceMock';

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Job Lines', value: 'lines' },
    { label: 'Labour & Assignment', value: 'labour' },
    { label: 'Invoice Preview', value: 'invoice' },
    { label: 'Service Payments', value: 'payments' },
    { label: 'Attachments', value: 'attachments' },
    { label: 'Comments', value: 'comments' },
    { label: 'Audit / History', value: 'audit' },
];

export function JobCardDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');

    return (
        <div className="space-y-6">
            <ModuleHeader
                actions={
                    <>
                        <Link to={`/vehicle-service/job-cards/${id ?? 'mock'}/edit`}>
                            <Button variant="secondary">Edit</Button>
                        </Link>
                        <Button variant="blue">Request Backend Preview</Button>
                    </>
                }
                subtitle="Operational detail view. Invoice, payment, stock, workflow, and audit values are backend-owned."
                title={`Job Card ${id ?? 'JC-MOCK-001'}`}
            />

            <Card className="p-5">
                <div className="mb-5 grid gap-4 md:grid-cols-4">
                    {[
                        ['Customer', 'Northline Logistics'],
                        ['Vehicle', 'WP CAD-4521 | Toyota HiAce'],
                        ['Workflow status', 'In Progress'],
                        ['Payment status', 'Backend controlled'],
                    ].map(([label, value]) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                            <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                            <div className="mt-2 text-sm font-semibold text-slate-900">{label.includes('status') ? <StatusBadge status={value} /> : value}</div>
                        </div>
                    ))}
                </div>
                <Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} />
            </Card>

            {activeTab === 'overview' ? (
                <Card className="p-5">
                    <h2 className="text-base font-bold text-slate-950">Service overview</h2>
                    <p className="mt-2 text-sm text-slate-500">
                        Complaint, diagnosis, intake notes, expected completion, and workflow actions render here once backend detail APIs are connected.
                    </p>
                </Card>
            ) : null}

            {activeTab === 'lines' ? (
                <DataTable
                    columns={[
                        { header: 'Category', key: 'category' },
                        { header: 'Description', key: 'description' },
                        { header: 'Qty', key: 'quantity' },
                        { header: 'Unit', key: 'unit' },
                        { header: 'Stock effect', key: 'stockImpact' },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={jobLines}
                />
            ) : null}

            {activeTab === 'labour' ? (
                <DataTable
                    columns={[
                        { header: 'Labour item', key: 'labourItem' },
                        { header: 'Employee', key: 'employee' },
                        { header: 'Role', key: 'role' },
                        { header: 'Share / incentive', key: 'shareRule' },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={labourAssignments}
                />
            ) : null}

            {activeTab === 'invoice' ? <DocumentPreview title="Mock Service Invoice Preview" /> : null}
            {activeTab === 'payments' ? <DocumentPreview title="Mock Service Payment Allocation Preview" /> : null}
            {activeTab === 'attachments' ? <DocumentPreview title="Attachments placeholder" /> : null}
            {activeTab === 'comments' ? <CommentPanel /> : null}
            {activeTab === 'audit' ? <AuditTimeline /> : null}
        </div>
    );
}
