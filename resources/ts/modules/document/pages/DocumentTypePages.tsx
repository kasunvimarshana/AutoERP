import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { DocumentTypeForm } from '../components/DocumentComponents';
import { documentApi } from '../services/documentApi';
import type { DocumentType } from '../types/document.types';

export function DocumentTypeListPage() {
    const [rows, setRows] = useState<DocumentType[]>([]);
    const [query, setQuery] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.listDocumentTypes().then((response) => { if (mounted) setRows(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleRows = useMemo(() => {
        const q = query.trim().toLowerCase();
        return q ? rows.filter((row) => [row.code, row.name, row.moduleScope].some((value) => value.toLowerCase().includes(q))) : rows;
    }, [query, rows]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/documents/types/new"><Button>New Type</Button></Link>} eyebrow="Documents" subtitle="Reusable module-agnostic document types." title="Document Types" />
            <SearchFilterBar onSearch={setQuery} placeholder="Search type code, name, scope..." />
            {isLoading ? <EmptyState description="Loading document types..." title="Loading types" /> : null}
            {!isLoading ? (
                <DataTable
                    columns={[
                        { header: 'Code', key: 'code' },
                        { header: 'Name', key: 'name' },
                        { header: 'Scope', key: 'moduleScope' },
                        { header: 'Versioning', key: 'versioningEnabled', render: (row) => <StatusBadge status={row.versioningEnabled ? 'Enabled' : 'Off'} /> },
                        { header: 'Workflow', key: 'workflowEnabled', render: (row) => <StatusBadge status={row.workflowEnabled ? 'Enabled' : 'Off'} /> },
                        { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
                        { header: 'Actions', key: 'actions', render: (row) => <Link className="font-semibold text-slate-950" to={`/documents/types/${row.id}/edit`}>Edit</Link> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={visibleRows}
                />
            ) : null}
        </div>
    );
}

export function DocumentTypeCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Type" subtitle="Create a reusable document type. Numbering, workflow, and rendering remain backend-owned." title="Create Document Type" />
            <DocumentTypeForm mode="create" />
        </div>
    );
}

export function DocumentTypeEditPage() {
    const { id } = useParams();
    const [type, setType] = useState<DocumentType | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.getDocumentType(id ?? '').then((response) => { if (mounted) setType(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading document type..." title="Loading type" />;
    if (!type) return <EmptyState description="Document type not found." title="Unable to load type" />;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Type" subtitle="Edit reusable document behavior flags." title={`Edit ${type.name}`} />
            <DocumentTypeForm mode="edit" type={type} />
        </div>
    );
}
