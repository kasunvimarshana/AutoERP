import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { DocumentDefinitionFieldsTable, DocumentDefinitionForm, DocumentSequencePanel, DocumentTemplatePreviewPanel, DocumentWorkflowPanel } from '../components/DocumentComponents';
import { documentSequences, documentTemplates, documentWorkflows } from '../mock/documentMock';
import { documentApi } from '../services/documentApi';
import type { DocumentDefinition } from '../types/document.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Fields / Metadata', value: 'fields' },
    { label: 'Template', value: 'template' },
    { label: 'Sequence', value: 'sequence' },
    { label: 'Workflow', value: 'workflow' },
    { label: 'Permissions', value: 'permissions' },
    { label: 'Usage', value: 'usage' },
    { label: 'Audit / History', value: 'audit' },
];

export function DocumentDefinitionListPage() {
    const [rows, setRows] = useState<DocumentDefinition[]>([]);
    const [query, setQuery] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.listDefinitions().then((response) => { if (mounted) setRows(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleRows = useMemo(() => {
        const q = query.trim().toLowerCase();
        return q ? rows.filter((row) => [row.code, row.name, row.documentTypeName, row.sourceModule].some((value) => value.toLowerCase().includes(q))) : rows;
    }, [query, rows]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/documents/definitions/new"><Button>New Definition</Button></Link>} eyebrow="Documents" subtitle="Definitions connect types, metadata fields, templates, sequences, and workflows." title="Document Definitions" />
            <SearchFilterBar onSearch={setQuery} placeholder="Search definition code, name, type, source..." />
            {isLoading ? <EmptyState description="Loading document definitions..." title="Loading definitions" /> : null}
            {!isLoading ? <DataTable columns={[
                { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/documents/definitions/${row.id}`}>{row.code}</Link> },
                { header: 'Name', key: 'name' },
                { header: 'Type', key: 'documentTypeName' },
                { header: 'Source', key: 'sourceModule' },
                { header: 'Version', key: 'version' },
                { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
                { header: 'Updated', key: 'updatedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-3 text-sm font-semibold"><Link className="text-slate-950" to={`/documents/definitions/${row.id}`}>View</Link><Link className="text-slate-500" to={`/documents/definitions/${row.id}/edit`}>Edit</Link></div> },
            ]} getRowKey={(row) => row.id} rows={visibleRows} /> : null}
        </div>
    );
}

export function DocumentDefinitionCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Definition" subtitle="Create metadata, template, sequence, and workflow setup. Document stays business-logic-free." title="Create Definition" />
            <DocumentDefinitionForm mode="create" />
        </div>
    );
}

export function DocumentDefinitionEditPage() {
    const { id } = useParams();
    const [definition, setDefinition] = useState<DocumentDefinition | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.getDefinition(id ?? '').then((response) => { if (mounted) setDefinition(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading document definition..." title="Loading definition" />;
    if (!definition) return <EmptyState description="Document definition not found." title="Unable to load definition" />;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Definition" subtitle="Edit definition setup. Backend owns rendering, numbering, and status transitions." title={`Edit ${definition.name}`} />
            <DocumentDefinitionForm definition={definition} mode="edit" />
        </div>
    );
}

export function DocumentDefinitionDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [definition, setDefinition] = useState<DocumentDefinition | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.getDefinition(id ?? '').then((response) => { if (mounted) setDefinition(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading document definition..." title="Loading definition" />;
    if (!definition) return <EmptyState description="Document definition not found." title="Unable to load definition" />;

    const template = documentTemplates.find((row) => row.id === definition.templateId);
    const sequence = documentSequences.find((row) => row.id === definition.sequenceId);
    const workflow = documentWorkflows.find((row) => row.id === definition.workflowId);

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/documents/definitions"><Button variant="secondary">Back</Button></Link><Link to={`/documents/definitions/${definition.id}/edit`}><Button>Edit Definition</Button></Link></>} eyebrow="Document Definition" subtitle="Definition detail keeps document setup reusable and source-module agnostic." title={definition.name} />
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <PreviewPanel rows={[{ label: 'Code', value: definition.code }, { label: 'Type', value: definition.documentTypeName }, { label: 'Source module', value: definition.sourceModule }, { label: 'Allowed statuses', value: definition.allowedStatuses.join(', ') }]} title="Overview" /> : null}
            {activeTab === 'fields' ? <DocumentDefinitionFieldsTable fields={definition.fields} /> : null}
            {activeTab === 'template' ? <DocumentTemplatePreviewPanel template={template} /> : null}
            {activeTab === 'sequence' ? <DocumentSequencePanel sequence={sequence} /> : null}
            {activeTab === 'workflow' ? <DocumentWorkflowPanel workflow={workflow} /> : null}
            {activeTab === 'permissions' ? <PreviewPanel rows={[{ label: 'Definition permissions', value: 'Backend-managed permission rules' }]} title="Permissions" /> : null}
            {activeTab === 'usage' ? <PreviewPanel rows={[{ label: 'Usage', value: 'Used by source modules through Document API' }]} title="Usage" /> : null}
            {activeTab === 'audit' ? <PreviewPanel rows={[{ label: 'Audit', value: 'Backend activity/history endpoint pending for definitions' }]} title="Audit / History" /> : null}
        </div>
    );
}
