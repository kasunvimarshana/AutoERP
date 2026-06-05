import { FormEvent, useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { DocumentPreviewPanel, DocumentSequencePanel, DocumentWorkflowPanel } from '../components/DocumentComponents';
import { documentApi } from '../services/documentApi';
import type { DocumentDefinition, DocumentPreviewInput, DocumentPreviewRendered, DocumentSequence, DocumentWorkflow } from '../types/document.types';

export function DocumentWorkflowListPage() {
    const [rows, setRows] = useState<DocumentWorkflow[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.listWorkflows().then((response) => { if (mounted) setRows(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Documents" subtitle="Workflow steps and transitions are displayed here. Backend owns allowed transitions and status effects." title="Document Workflows" />
            {isLoading ? <EmptyState description="Loading workflows..." title="Loading workflows" /> : null}
            {!isLoading ? <div className="grid gap-5 lg:grid-cols-2">{rows.map((workflow) => <DocumentWorkflowPanel key={workflow.id} workflow={workflow} />)}</div> : null}
        </div>
    );
}

export function DocumentSequenceListPage() {
    const [rows, setRows] = useState<DocumentSequence[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        documentApi.listSequences().then((response) => { if (mounted) setRows(response.data); }).finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Documents" subtitle="Document numbering is served by the Sequence module. Frontend only asks for previews." title="Document Sequences" />
            {isLoading ? <EmptyState description="Loading sequences..." title="Loading sequences" /> : null}
            {!isLoading ? (
                <div className="space-y-5">
                    <DataTable columns={[
                        { header: 'Code', key: 'code' },
                        { header: 'Document Type', key: 'documentType' },
                        { header: 'Prefix', key: 'prefix' },
                        { header: 'Period', key: 'periodType' },
                        { header: 'Next Preview', key: 'nextNumberPreview' },
                        { header: 'Status', key: 'status' },
                    ]} getRowKey={(row) => row.id} rows={rows} />
                    <div className="grid gap-5 md:grid-cols-2">{rows.slice(0, 2).map((row) => <DocumentSequencePanel key={row.id} sequence={row} />)}</div>
                </div>
            ) : null}
        </div>
    );
}

export function DocumentPreviewPage() {
    const [definitions, setDefinitions] = useState<DocumentDefinition[]>([]);
    const [preview, setPreview] = useState<DocumentPreviewRendered | undefined>();
    const [breakdown, setBreakdown] = useState<Array<{ label: string; value: string }>>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        documentApi.listDefinitions().then((response) => { if (mounted) setDefinitions(response.data); });
        return () => { mounted = false; };
    }, []);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsLoading(true);
        setError('');
        const formData = new FormData(event.currentTarget);
        const input: DocumentPreviewInput = {
            definitionId: String(formData.get('definition_id') ?? ''),
            metadata: {
                body: String(formData.get('notes') ?? ''),
                sample_reference: String(formData.get('sample_reference') ?? ''),
                title: String(formData.get('sample_reference') ?? ''),
            },
            sourceId: String(formData.get('source_id') ?? ''),
            sourceModule: String(formData.get('source_module') ?? 'shared') || 'shared',
            sourceReference: String(formData.get('sample_reference') ?? ''),
        };

        try {
            const response = await documentApi.previewDocument(input);
            setPreview(response.calculated);
            setBreakdown(response.breakdown.map((row) => ({ label: String(row.label ?? 'Step'), value: String(row.value ?? '') })));
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to render document preview.');
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Document Preview" subtitle="Choose a definition and source context. The Document API returns rendered preview output." title="Preview Document" />
            <form className="grid gap-5 xl:grid-cols-[1fr_420px]" onSubmit={handleSubmit}>
                <div className="space-y-5">
                    {error ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div> : null}
                    <FormSection description="No official rendering happens in the frontend. This form only sends preview input." title="Preview Inputs">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2"><label className="text-xs font-bold uppercase tracking-wide text-slate-500">Definition</label><Select name="definition_id" options={definitions.map((definition) => ({ label: definition.name, value: definition.id }))} /></div>
                            <div className="space-y-2"><label className="text-xs font-bold uppercase tracking-wide text-slate-500">Source module</label><Input defaultValue="shared" name="source_module" /></div>
                            <div className="space-y-2"><label className="text-xs font-bold uppercase tracking-wide text-slate-500">Source id</label><Input name="source_id" /></div>
                            <div className="space-y-2"><label className="text-xs font-bold uppercase tracking-wide text-slate-500">Sample reference</label><Input name="sample_reference" /></div>
                            <div className="space-y-2 md:col-span-2"><label className="text-xs font-bold uppercase tracking-wide text-slate-500">Sample metadata</label><Textarea name="notes" /></div>
                        </div>
                    </FormSection>
                    <div className="flex justify-end"><Button disabled={isLoading} type="submit" variant="blue">{isLoading ? 'Rendering...' : 'Preview Document'}</Button></div>
                </div>
                <DocumentPreviewPanel rendered={preview} />
            </form>
            {preview ? <PreviewPanel rows={breakdown} title="Preview Breakdown" /> : <Card className="p-5"><p className="text-sm text-slate-500">Preview output will appear after backend rendering.</p></Card>}
        </div>
    );
}
