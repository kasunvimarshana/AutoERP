import { useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { DocumentRecordTable } from '../components/DocumentComponents';
import { documentApi } from '../services/documentApi';
import type { DocumentDefinition, DocumentRecord, DocumentTemplate } from '../types/document.types';

export function DocumentDashboardPage() {
    const [documents, setDocuments] = useState<DocumentRecord[]>([]);
    const [definitions, setDefinitions] = useState<DocumentDefinition[]>([]);
    const [templates, setTemplates] = useState<DocumentTemplate[]>([]);
    const [error, setError] = useState('');
    const [isLoaded, setIsLoaded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    async function loadDashboardData(): Promise<void> {
        if (isLoading) return;

        setIsLoading(true);
        setError('');

        try {
            const [documentResponse, definitionResponse, templateResponse] = await Promise.all([documentApi.listDocuments(), documentApi.listDefinitions(), documentApi.listTemplates()]);
            setDocuments(documentResponse.data);
            setDefinitions(definitionResponse.data);
            setTemplates(templateResponse.data);
            setIsLoaded(true);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to load Document dashboard.');
        } finally {
            setIsLoading(false);
        }
    }

    const metrics = [
        ['Recent documents', String(documents.length), 'Module-agnostic records'],
        ['Draft documents', String(documents.filter((row) => row.status === 'draft').length), 'Workflow pending'],
        ['Finalized / posted', String(documents.filter((row) => ['posted', 'finalized'].includes(row.status)).length), 'Backend-owned status'],
        ['Definitions', String(definitions.length), 'Reusable setup'],
        ['Active templates', String(templates.filter((row) => row.isActive).length), 'Backend renderer inputs'],
        ['Sequence status', 'Backend-owned', 'Numbering from Sequence module'],
    ];

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/documents/definitions/new"><Button>Create Definition</Button></Link><Link to="/documents/preview"><Button variant="secondary">Preview Document</Button></Link></>}
                eyebrow="Core Documents"
                subtitle="Reusable documents for source modules. Business modules calculate values; Document stores, renders, versions, tracks workflow, attachments, comments, and audit."
                title="Documents"
            />
            <div className="flex justify-end">
                <Button disabled={isLoading} onClick={() => void loadDashboardData()} type="button" variant="secondary">{isLoaded ? 'Refresh Dashboard Data' : 'Load Dashboard Data'}</Button>
            </div>
            {error ? <EmptyState description={error} title="Document dashboard unavailable" /> : null}
            {!isLoaded && !error ? <EmptyState description="Document metrics and recent records load only when requested." title="Dashboard data not loaded" /> : null}
            {isLoaded ? <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                {metrics.map(([label, value, helper]) => <Card className="p-5" key={label}><p className="text-sm text-slate-500">{label}</p><p className="mt-2 text-2xl font-bold text-slate-950">{value}</p><p className="mt-1 text-xs text-slate-400">{helper}</p></Card>)}
            </div> : null}
            <div className="grid gap-4 md:grid-cols-4">
                {[
                    ['Records', 'Generated document records from source modules.', '/documents/records'],
                    ['Definitions', 'Fields, templates, sequences, workflows.', '/documents/definitions'],
                    ['Templates', 'Renderer content and layouts.', '/documents/templates'],
                    ['Workflows', 'Statuses and transitions managed by backend.', '/documents/workflows'],
                ].map(([title, description, path]) => (
                    <Link key={title} to={path}><Card className="h-full p-5 transition hover:border-slate-300 hover:shadow-md"><p className="font-bold text-slate-950">{title}</p><p className="mt-2 text-sm text-slate-500">{description}</p></Card></Link>
                ))}
            </div>
            {isLoaded ? <DocumentRecordTable records={documents.slice(0, 5)} /> : null}
        </div>
    );
}
