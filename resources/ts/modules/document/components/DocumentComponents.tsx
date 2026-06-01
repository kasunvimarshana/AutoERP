import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { WorkflowTimeline } from '../../../shared/components/business/WorkflowTimeline';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { documentApi } from '../services/documentApi';
import type {
    DocumentAttachment,
    DocumentComment,
    DocumentDefinition,
    DocumentDefinitionFormInput,
    DocumentEvent,
    DocumentFieldDefinition,
    DocumentLine,
    DocumentPermission,
    DocumentPreviewRendered,
    DocumentRecord,
    DocumentRelation,
    DocumentSequence,
    DocumentStatus,
    DocumentTemplate,
    DocumentTemplateFormInput,
    DocumentType,
    DocumentTypeFormInput,
    DocumentVersion,
    DocumentWorkflow,
    LookupOption,
} from '../types/document.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checked(formData: FormData, name: string) {
    return formData.get(name) === 'on';
}

function value(formData: FormData, name: string) {
    return String(formData.get(name) ?? '');
}

function apiError(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
        return { errors: error.errors, message: error.message };
    }

    return { errors: {}, message: error instanceof Error ? error.message : fallback };
}

const statusOptions: LookupOption[] = ['draft', 'submitted', 'approved', 'posted', 'finalized', 'cancelled', 'archived'].map((status) => ({ label: status, value: status }));

export function DocumentRecordSummaryCard({ document, onStatusChanged }: { document: DocumentRecord; onStatusChanged?: (document: DocumentRecord) => void }) {
    const [isSaving, setIsSaving] = useState(false);

    async function changeStatus(status: DocumentStatus) {
        setIsSaving(true);
        try {
            const response = await documentApi.changeDocumentStatus(document.id, status, `set_${status}`);
            onStatusChanged?.(response.data);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{document.documentNumber}</p>
                        <StatusBadge status={document.status} />
                        <StatusBadge status={document.sourceModule} />
                    </div>
                    <h2 className="mt-2 text-xl font-bold text-slate-950">{document.title}</h2>
                    <p className="mt-1 text-sm text-slate-500">{document.typeName} {document.sourceReference ? `from ${document.sourceReference}` : ''}</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Version</p><p className="mt-1 font-semibold text-slate-800">{document.version}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Date</p><p className="mt-1 font-semibold text-slate-800">{document.documentDate}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Amount</p><p className="mt-1 font-semibold text-slate-800">{document.grandTotal}</p></div>
                </div>
            </div>
            <div className="mt-5 flex flex-wrap gap-2">
                {statusOptions.map((option) => (
                    <Button disabled={isSaving || document.status === option.value} key={`${option.value}:${option.label}`} onClick={() => void changeStatus(option.value as DocumentStatus)} type="button" variant={document.status === option.value ? 'secondary' : 'ghost'}>
                        {option.label}
                    </Button>
                ))}
            </div>
        </Card>
    );
}

export function DocumentRecordTable({ records }: { records: DocumentRecord[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Document #', key: 'documentNumber', render: (row) => <Link className="font-semibold text-slate-950" to={`/documents/records/${row.id}`}>{row.documentNumber}</Link> },
                { header: 'Title / Reference', key: 'title', render: (row) => <div><p className="font-semibold text-slate-900">{row.title}</p><p className="text-xs text-slate-400">{row.sourceReference || row.sourceType}</p></div> },
                { header: 'Type', key: 'typeName' },
                { header: 'Source', key: 'sourceModule' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Version', key: 'version' },
                { header: 'Date', key: 'documentDate' },
                { header: 'Actions', key: 'actions', render: (row) => <Link className="font-semibold text-slate-950" to={`/documents/records/${row.id}`}>View</Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={records}
        />
    );
}

export function DocumentPreviewPanel({ rendered }: { rendered?: DocumentPreviewRendered }) {
    if (!rendered) {
        return <EmptyState description="Run a backend preview to see rendered output." title="No preview yet" />;
    }

    return (
        <Card className="p-5">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">Document Preview</h2>
                    <p className="mt-1 text-sm text-slate-500">Rendered by the Document API.</p>
                </div>
                <StatusBadge status="Preview" />
            </div>
            <PreviewPanel rows={[
                { label: 'Document number', value: rendered.documentNumber },
                { label: 'Definition', value: rendered.definitionName },
                { label: 'Template', value: rendered.templateName },
            ]} title="Rendered Result" />
            <pre className="mt-4 max-h-96 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">{rendered.text || rendered.html}</pre>
        </Card>
    );
}

export function DocumentMetadataPanel({ document }: { document: DocumentRecord }) {
    const rows = Object.entries(document.data).map(([label, raw]) => ({ label, value: typeof raw === 'object' ? JSON.stringify(raw) : String(raw ?? '') }));
    return <PreviewPanel rows={rows.length ? rows : [{ label: 'Metadata', value: 'No metadata values stored.' }]} title="Metadata" />;
}

export function DocumentSourceReferencePanel({ document }: { document: DocumentRecord }) {
    return <PreviewPanel rows={[
        { label: 'Source module', value: document.sourceModule },
        { label: 'Source type', value: document.sourceType || 'none' },
        { label: 'Source reference', value: document.sourceReference || 'none' },
    ]} title="Source References" />;
}

export function DocumentRelationsTable({ relations, onRemove }: { onRemove?: (relation: DocumentRelation) => void; relations: DocumentRelation[] }) {
    return <DataTable columns={[
        { header: 'Relation', key: 'relationType' },
        { header: 'Document #', key: 'targetDocumentNumber' },
        { header: 'Target ID', key: 'targetDocumentId' },
        { header: 'Actions', key: 'actions', render: (row) => onRemove ? <Button onClick={() => onRemove(row)} type="button" variant="ghost">Remove</Button> : null },
    ]} getRowKey={(row) => row.id} rows={relations} />;
}

export function DocumentPermissionsPanel({ permissions }: { permissions: DocumentPermission[] }) {
    return <DataTable columns={[{ header: 'Principal Type', key: 'principalType' }, { header: 'Principal', key: 'principal' }, { header: 'Ability', key: 'ability' }]} getRowKey={(row) => row.id} rows={permissions} />;
}

export function DocumentEventsTimeline({ events }: { events: DocumentEvent[] }) {
    return <AuditTimeline events={events.map((entry) => ({ actor: entry.actor, description: `${entry.eventType}: ${entry.message}`, time: entry.time }))} />;
}

export function DocumentVersionsTable({ versions }: { versions: DocumentVersion[] }) {
    return <DataTable columns={[{ header: 'Version', key: 'version' }, { header: 'Created', key: 'createdAt' }, { header: 'Created By', key: 'createdBy' }, { header: 'Note', key: 'note' }]} getRowKey={(row) => row.id} rows={versions} />;
}

export function DocumentDefinitionFieldsTable({ fields }: { fields: DocumentFieldDefinition[] }) {
    return <DataTable columns={[
        { header: 'Order', key: 'displayOrder' },
        { header: 'Code', key: 'code' },
        { header: 'Label', key: 'label' },
        { header: 'Type', key: 'dataType' },
        { header: 'Required', key: 'isRequired', render: (row) => <StatusBadge status={row.isRequired ? 'Required' : 'Optional'} /> },
        { header: 'Readonly', key: 'isReadonly', render: (row) => <StatusBadge status={row.isReadonly ? 'Readonly' : 'Editable'} /> },
    ]} getRowKey={(row) => row.id} rows={fields} />;
}

export function DocumentLinesTable({ lines }: { lines: DocumentLine[] }) {
    return <DataTable columns={[
        { header: '#', key: 'lineNo' },
        { header: 'Type', key: 'lineType' },
        { header: 'Item / Label', key: 'itemLabel' },
        { header: 'Description', key: 'description' },
        { header: 'Qty', key: 'quantity' },
        { header: 'UOM', key: 'uomLabel' },
        { header: 'Unit Price', key: 'unitPrice' },
        { header: 'Line Total', key: 'lineTotal' },
    ]} getRowKey={(row) => row.id} rows={lines} />;
}

export function DocumentTemplatePreviewPanel({ template }: { template?: DocumentTemplate }) {
    if (!template) {
        return <EmptyState description="Select or save a template to preview configured sections." title="No template selected" />;
    }

    return <PreviewPanel rows={[
        { label: 'Code', value: template.code },
        { label: 'Layout', value: template.layoutType },
        { label: 'Header', value: template.headerContent },
        { label: 'Body', value: template.bodyContent },
        { label: 'Footer', value: template.footerContent },
    ]} title="Template Content" />;
}

export function DocumentWorkflowPanel({ workflow }: { workflow?: DocumentWorkflow }) {
    return (
        <Card className="p-5">
            <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">{workflow?.name ?? 'Workflow'}</h2>
            <p className="mt-1 text-sm text-slate-500">{workflow?.documentTypeName || 'Workflow steps are loaded from backend.'}</p>
            <div className="mt-5"><WorkflowTimeline steps={workflow?.steps} /></div>
        </Card>
    );
}

export function DocumentSequencePanel({ sequence }: { sequence?: DocumentSequence }) {
    return <PreviewPanel rows={[
        { label: 'Sequence', value: sequence?.code ?? 'No sequence selected' },
        { label: 'Document type', value: sequence?.documentType ?? 'none' },
        { label: 'Period', value: sequence?.periodType ?? 'none' },
        { label: 'Next number preview', value: sequence?.nextNumberPreview || 'Preview unavailable' },
    ]} title="Sequence" />;
}

export function DocumentStatusActionPanel({ document }: { document: DocumentRecord }) {
    return <PreviewPanel rows={[
        { label: 'Current status', value: document.status },
        { label: 'Status source', value: 'Document API' },
        { label: 'Version', value: document.version },
    ]} title="Status" />;
}

export function DocumentAttachmentPanel({ attachments, documentId, onChanged }: { attachments: DocumentAttachment[]; documentId: string; onChanged: () => void }) {
    const [isUploading, setIsUploading] = useState(false);

    async function upload(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const file = (new FormData(event.currentTarget).get('file') as File | null);
        if (!file || file.size === 0) return;
        setIsUploading(true);
        try {
            await documentApi.uploadAttachment(documentId, file);
            onChanged();
            event.currentTarget.reset();
        } finally {
            setIsUploading(false);
        }
    }

    async function remove(attachment: DocumentAttachment) {
        await documentApi.removeAttachment(documentId, attachment.id);
        onChanged();
    }

    return (
        <div className="space-y-4">
            <form className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 md:flex-row md:items-end" onSubmit={upload}>
                <div className="flex-1 space-y-2"><FieldLabel>Attachment file</FieldLabel><Input name="file" type="file" /></div>
                <Button disabled={isUploading} type="submit" variant="blue">{isUploading ? 'Uploading...' : 'Upload'}</Button>
            </form>
            <DataTable columns={[
                { header: 'File', key: 'fileName' },
                { header: 'Type', key: 'fileType' },
                { header: 'Size', key: 'fileSize' },
                { header: 'Uploaded', key: 'uploadedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <Button onClick={() => void remove(row)} type="button" variant="ghost">Remove</Button> },
            ]} getRowKey={(row) => row.id} rows={attachments} />
        </div>
    );
}

export function DocumentCommentPanel({ comments, documentId, onChanged }: { comments: DocumentComment[]; documentId: string; onChanged: () => void }) {
    const [isSaving, setIsSaving] = useState(false);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const comment = value(new FormData(event.currentTarget), 'comment').trim();
        if (!comment) return;
        setIsSaving(true);
        try {
            await documentApi.addComment(documentId, { comment });
            onChanged();
            event.currentTarget.reset();
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <div className="space-y-4">
            <form className="space-y-3 rounded-lg border border-slate-200 bg-white p-4" onSubmit={submit}>
                <FieldLabel>Comment</FieldLabel>
                <Textarea name="comment" />
                <div className="flex justify-end"><Button disabled={isSaving} type="submit" variant="blue">{isSaving ? 'Saving...' : 'Add Comment'}</Button></div>
            </form>
            <DataTable columns={[{ header: 'Author', key: 'author' }, { header: 'Comment', key: 'body' }, { header: 'Time', key: 'time' }]} getRowKey={(row) => row.id} rows={comments} />
        </div>
    );
}

function useDocumentSetup() {
    const [types, setTypes] = useState<DocumentType[]>([]);
    const [templates, setTemplates] = useState<DocumentTemplate[]>([]);
    const [sequences, setSequences] = useState<DocumentSequence[]>([]);
    const [workflows, setWorkflows] = useState<DocumentWorkflow[]>([]);

    useEffect(() => {
        let mounted = true;
        Promise.all([documentApi.listDocumentTypes(), documentApi.listTemplates(), documentApi.listSequences(), documentApi.listWorkflows()])
            .then(([typeResponse, templateResponse, sequenceResponse, workflowResponse]) => {
                if (!mounted) return;
                setTypes(typeResponse.data);
                setTemplates(templateResponse.data);
                setSequences(sequenceResponse.data);
                setWorkflows(workflowResponse.data);
            });
        return () => { mounted = false; };
    }, []);

    return { sequences, templates, types, workflows };
}

export function DocumentTypeForm({ mode, type }: { mode: 'create' | 'edit'; type?: DocumentType }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);
        const formData = new FormData(event.currentTarget);
        const input: DocumentTypeFormInput = {
            attachmentsEnabled: checked(formData, 'attachments_enabled'),
            code: value(formData, 'code'),
            commentsEnabled: checked(formData, 'comments_enabled'),
            description: value(formData, 'description'),
            isActive: checked(formData, 'is_active'),
            moduleScope: value(formData, 'module_scope') as DocumentType['moduleScope'],
            name: value(formData, 'name'),
            requiresSource: checked(formData, 'requires_source'),
            versioningEnabled: checked(formData, 'versioning_enabled'),
            workflowEnabled: checked(formData, 'workflow_enabled'),
        };

        try {
            await (mode === 'edit' && type ? documentApi.updateDocumentType(type.id, input) : documentApi.createDocumentType(input));
            navigate('/documents/types');
        } catch (caught) {
            const parsed = apiError(caught, 'Unable to save document type.');
            setErrors(parsed.errors);
            setFormError(parsed.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Document types define reusable document behavior." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Type code</FieldLabel><Input defaultValue={type?.code} name="code" /><FieldError message={errors.code?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Type name</FieldLabel><Input defaultValue={type?.name} name="name" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Module / scope</FieldLabel><Input defaultValue={type?.moduleScope ?? 'shared'} name="module_scope" /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={type?.description} name="description" /></div>
                    </div>
                </FormSection>
                <FormSection description="Capability flags are persisted by the Document API." title="Capabilities">
                    <div className="grid gap-3 md:grid-cols-3">
                        {[
                            ['is_active', 'Active', type?.isActive ?? true],
                            ['requires_source', 'Requires source', type?.requiresSource ?? false],
                            ['versioning_enabled', 'Versioning', type?.versioningEnabled ?? true],
                            ['attachments_enabled', 'Attachments', type?.attachmentsEnabled ?? true],
                            ['comments_enabled', 'Comments', type?.commentsEnabled ?? true],
                            ['workflow_enabled', 'Workflow', type?.workflowEnabled ?? true],
                        ].map(([name, label, defaultChecked]) => (
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={String(name)}>
                                <Checkbox defaultChecked={Boolean(defaultChecked)} name={String(name)} />{label}
                            </label>
                        ))}
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/documents/types"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Type' : 'Create Type'}</Button></div>
            </div>
            <PreviewPanel rows={[{ label: 'Numbering', value: 'Sequence API' }, { label: 'Rendering', value: 'Document API' }, { label: 'Status', value: 'Document workflow' }]} title="Backend ownership" />
        </form>
    );
}

export function DocumentDefinitionForm({ definition, mode }: { definition?: DocumentDefinition; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const setup = useDocumentSetup();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const firstField = definition?.fields[0];
    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);
        const formData = new FormData(event.currentTarget);
        const fields: DocumentFieldDefinition[] = [{
            code: value(formData, 'field_code') || 'reference',
            dataType: value(formData, 'field_type') as DocumentFieldDefinition['dataType'],
            defaultValue: value(formData, 'field_default'),
            displayOrder: Number(value(formData, 'field_order') || 1),
            id: firstField?.id ?? 'form-field',
            isReadonly: checked(formData, 'field_readonly'),
            isRequired: checked(formData, 'field_required'),
            label: value(formData, 'field_label') || 'Reference',
            sectionKey: value(formData, 'field_section'),
            validationRule: value(formData, 'field_validation'),
        }];
        const input: DocumentDefinitionFormInput = {
            code: value(formData, 'code'),
            defaultStatus: value(formData, 'default_status') as DocumentStatus,
            description: value(formData, 'description'),
            documentTypeId: value(formData, 'document_type_id'),
            fields,
            isActive: checked(formData, 'is_active'),
            name: value(formData, 'name'),
            sequenceId: value(formData, 'sequence_id'),
            sourceModule: value(formData, 'source_module') as DocumentDefinition['sourceModule'],
            supportsVersions: checked(formData, 'supports_versions'),
            templateId: value(formData, 'template_id'),
            workflowId: value(formData, 'workflow_id'),
        };

        try {
            const response = mode === 'edit' && definition ? await documentApi.updateDefinition(definition.id, input) : await documentApi.createDefinition(input);
            navigate(`/documents/definitions/${response.data.id}`);
        } catch (caught) {
            const parsed = apiError(caught, 'Unable to save document definition.');
            setErrors(parsed.errors);
            setFormError(parsed.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    const typeOptions = setup.types.map((type) => ({ label: type.name, value: type.id }));
    const templateOptions = setup.templates.map((template) => ({ label: template.name, value: template.id }));
    const sequenceOptions = setup.sequences.map((sequence) => ({ label: sequence.code, value: sequence.id }));
    const workflowOptions = setup.workflows.map((workflow) => ({ label: workflow.name, value: workflow.id }));

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Definitions bind a document type to metadata fields, template, sequence, and workflow." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Definition code</FieldLabel><Input defaultValue={definition?.code} name="code" /></div>
                        <div className="space-y-2"><FieldLabel>Definition name</FieldLabel><Input defaultValue={definition?.name} name="name" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Document type</FieldLabel><Select defaultValue={definition?.documentTypeId} name="document_type_id" options={typeOptions} /></div>
                        <div className="space-y-2"><FieldLabel>Source module</FieldLabel><Input defaultValue={definition?.sourceModule ?? 'shared'} name="source_module" /></div>
                        <div className="space-y-2"><FieldLabel>Default status</FieldLabel><Select defaultValue={definition?.defaultStatus ?? 'draft'} name="default_status" options={statusOptions} /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={definition?.isActive ?? true} name="is_active" />Active</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={definition?.supportsVersions ?? true} name="supports_versions" />Supports versions</label>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={definition?.description} name="description" /></div>
                    </div>
                </FormSection>
                <FormSection description="This field is persisted to document definition fields." title="Field Setup">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2"><FieldLabel>Field code</FieldLabel><Input defaultValue={firstField?.code} name="field_code" /></div>
                        <div className="space-y-2"><FieldLabel>Field label</FieldLabel><Input defaultValue={firstField?.label} name="field_label" /></div>
                        <div className="space-y-2"><FieldLabel>Field type</FieldLabel><Select defaultValue={firstField?.dataType ?? 'text'} name="field_type" options={[{ label: 'Text', value: 'text' }, { label: 'Number', value: 'number' }, { label: 'Date', value: 'date' }, { label: 'Money', value: 'money' }, { label: 'Boolean', value: 'boolean' }, { label: 'JSON', value: 'json' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Display order</FieldLabel><Input defaultValue={firstField?.displayOrder ?? 1} name="field_order" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Default value</FieldLabel><Input defaultValue={firstField?.defaultValue} name="field_default" /></div>
                        <div className="space-y-2"><FieldLabel>Section key</FieldLabel><Input defaultValue={firstField?.sectionKey} name="field_section" /></div>
                        <div className="space-y-2"><FieldLabel>Validation rule</FieldLabel><Input defaultValue={firstField?.validationRule} name="field_validation" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={firstField?.isRequired} name="field_required" />Required</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={firstField?.isReadonly} name="field_readonly" />Readonly</label>
                    </div>
                </FormSection>
                <FormSection description="Backend uses these references during document creation and rendering." title="Template, Sequence, Workflow">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2"><FieldLabel>Template</FieldLabel><Select defaultValue={definition?.templateId} name="template_id" options={templateOptions} /></div>
                        <div className="space-y-2"><FieldLabel>Sequence</FieldLabel><Select defaultValue={definition?.sequenceId} name="sequence_id" options={sequenceOptions} /></div>
                        <div className="space-y-2"><FieldLabel>Workflow</FieldLabel><Select defaultValue={definition?.workflowId} name="workflow_id" options={workflowOptions} /></div>
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/documents/definitions"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Definition' : 'Create Definition'}</Button></div>
            </div>
            <DocumentTemplatePreviewPanel template={setup.templates.find((template) => template.id === definition?.templateId)} />
        </form>
    );
}

export function DocumentTemplateForm({ mode, template }: { mode: 'create' | 'edit'; template?: DocumentTemplate }) {
    const navigate = useNavigate();
    const { types } = useDocumentSetup();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);
        const formData = new FormData(event.currentTarget);
        const input: DocumentTemplateFormInput = {
            bodyContent: value(formData, 'body'),
            code: value(formData, 'code'),
            documentTypeId: value(formData, 'document_type_id'),
            footerContent: value(formData, 'footer'),
            headerContent: value(formData, 'header'),
            isActive: checked(formData, 'is_active'),
            layoutType: value(formData, 'layout_type') as DocumentTemplate['layoutType'],
            name: value(formData, 'name'),
        };
        try {
            await (mode === 'edit' && template ? documentApi.updateTemplate(template.id, input) : documentApi.createTemplate(input));
            navigate('/documents/templates');
        } catch (caught) {
            const parsed = apiError(caught, 'Unable to save document template.');
            setErrors(parsed.errors);
            setFormError(parsed.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Templates define renderer inputs. Official output is generated by backend." title="Template">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Template code</FieldLabel><Input defaultValue={template?.code} name="code" /><FieldError message={errors.template_code?.[0] ?? errors.code?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Template name</FieldLabel><Input defaultValue={template?.name} name="name" /><FieldError message={errors.template_name?.[0] ?? errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Document type</FieldLabel><Select defaultValue={template?.documentTypeId} name="document_type_id" options={types.map((type) => ({ label: type.name, value: type.id }))} /></div>
                        <div className="space-y-2"><FieldLabel>Layout type</FieldLabel><Select defaultValue={template?.layoutType ?? 'html'} name="layout_type" options={[{ label: 'HTML', value: 'html' }, { label: 'PDF', value: 'pdf' }, { label: 'Thermal', value: 'thermal' }, { label: 'Plain', value: 'plain' }]} /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Header content</FieldLabel><Textarea defaultValue={template?.headerContent} name="header" /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Body content</FieldLabel><Textarea defaultValue={template?.bodyContent} name="body" /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Footer content</FieldLabel><Textarea defaultValue={template?.footerContent} name="footer" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={template?.isActive ?? true} name="is_active" />Active</label>
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/documents/templates"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Template' : 'Create Template'}</Button></div>
            </div>
            <DocumentTemplatePreviewPanel template={template} />
        </form>
    );
}

export function useDocumentDefinitionSupport(definition?: DocumentDefinition | null) {
    const [templates, setTemplates] = useState<DocumentTemplate[]>([]);
    const [sequences, setSequences] = useState<DocumentSequence[]>([]);
    const [workflows, setWorkflows] = useState<DocumentWorkflow[]>([]);

    useEffect(() => {
        let mounted = true;
        Promise.all([documentApi.listTemplates(), documentApi.listSequences(), documentApi.listWorkflows()]).then(([templateResponse, sequenceResponse, workflowResponse]) => {
            if (!mounted) return;
            setTemplates(templateResponse.data);
            setSequences(sequenceResponse.data);
            setWorkflows(workflowResponse.data);
        });
        return () => { mounted = false; };
    }, [definition?.id]);

    return {
        sequence: sequences.find((row) => row.id === definition?.sequenceId),
        template: templates.find((row) => row.id === definition?.templateId),
        workflow: workflows.find((row) => row.id === definition?.workflowId),
    };
}

export function DocumentDefinitionUsagePanel({ definition }: { definition: DocumentDefinition }) {
    const rows = useMemo(() => [
        { label: 'Fields', value: String(definition.fields.length) },
        { label: 'Template linked', value: definition.templateId ? 'yes' : 'no' },
        { label: 'Sequence linked', value: definition.sequenceId ? 'yes' : 'no' },
        { label: 'Workflow linked', value: definition.workflowId ? 'yes' : 'no' },
        { label: 'Source module', value: definition.sourceModule },
    ], [definition]);

    return <PreviewPanel rows={rows} title="Definition Usage" />;
}
