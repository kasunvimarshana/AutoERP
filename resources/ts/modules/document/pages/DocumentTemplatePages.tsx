import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { DocumentTemplateForm } from '../components/DocumentComponents';
import { documentApi } from '../services/documentApi';
import type { DocumentTemplate } from '../types/document.types';

export function DocumentTemplateListPage() {
    const [rows, setRows] = useState<DocumentTemplate[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.listTemplates().then((response) => { if (mounted) setRows(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/documents/templates/new"><Button>New Template</Button></Link>} eyebrow="Documents" subtitle="Templates are renderer inputs. Official PDF/HTML generation happens in backend." title="Document Templates" />
            {isLoading ? <EmptyState description="Loading templates..." title="Loading templates" /> : null}
            {!isLoading ? <DataTable columns={[
                { header: 'Code', key: 'code' },
                { header: 'Name', key: 'name' },
                { header: 'Layout', key: 'layoutType' },
                { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link className="font-semibold text-slate-950" to={`/documents/templates/${row.id}/edit`}>Edit</Link> },
            ]} getRowKey={(row) => row.id} rows={rows} /> : null}
        </div>
    );
}

export function DocumentTemplateCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Template" subtitle="Create backend-rendered template content." title="Create Template" />
            <DocumentTemplateForm mode="create" />
        </div>
    );
}

export function DocumentTemplateEditPage() {
    const { id } = useParams();
    const [template, setTemplate] = useState<DocumentTemplate | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.getTemplate(id ?? '').then((response) => { if (mounted) setTemplate(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading template..." title="Loading template" />;
    if (!template) return <EmptyState description="Document template not found." title="Unable to load template" />;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Template" subtitle="Edit renderer content stored by the Document API." title={`Edit ${template.name}`} />
            <DocumentTemplateForm mode="edit" template={template} />
        </div>
    );
}
