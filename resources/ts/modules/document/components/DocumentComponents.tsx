import { FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { AttachmentPanel } from '../../../shared/components/business/AttachmentPanel';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { CommentPanel } from '../../../shared/components/business/CommentPanel';
import { DocumentPreview } from '../../../shared/components/business/DocumentPreview';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { WorkflowTimeline } from '../../../shared/components/business/WorkflowTimeline';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { documentDefinitions, documentSequences, documentTemplates, documentTypes, documentWorkflows } from '../mock/documentMock';
import { documentApi, documentMockPanels } from '../services/documentApi';
import type {
    DocumentAttachment,
    DocumentAuditEntry,
    DocumentComment,
    DocumentDefinition,
    DocumentDefinitionFormInput,
    DocumentEvent,
    DocumentFieldDefinition,
    DocumentLine,
    DocumentPermission,
    DocumentPreviewResult,
    DocumentRecord,
    DocumentRelation,
    DocumentSequence,
    DocumentSourceReference,
    DocumentStatus,
    DocumentTemplate,
    DocumentTemplateFormInput,
    DocumentType,
    DocumentTypeFormInput,
    DocumentVersion,
    DocumentWorkflow,
} from '../types/document.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checked(formData: FormData, name: string) {
    return formData.get(name) === 'on';
}

const sourceModuleOptions = [
    { label: 'Shared/Core', value: 'shared' },
    { label: 'Purchase', value: 'purchase' },
    { label: 'Sales', value: 'sales' },
    { label: 'Vehicle Service', value: 'vehicle_service' },
    { label: 'Vehicle Rental', value: 'vehicle_rental' },
    { label: 'Voucher', value: 'voucher' },
    { label: 'Finance', value: 'finance' },
    { label: 'Supplier', value: 'supplier' },
    { label: 'Customer', value: 'customer' },
    { label: 'HR', value: 'hr' },
];

export function DocumentRecordSummaryCard({ document }: { document: DocumentRecord }) {
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
                    <p className="mt-1 text-sm text-slate-500">{document.typeName} sourced from {document.sourceReference}.</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Version</p><p className="mt-1 font-semibold text-slate-800">{document.version}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Created</p><p className="mt-1 font-semibold text-slate-800">{document.createdAt}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Owner</p><p className="mt-1 font-semibold text-slate-800">{document.createdBy}</p></div>
                </div>
            </div>
        </Card>
    );
}

export function DocumentRecordTable({ records }: { records: DocumentRecord[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Document #', key: 'documentNumber', render: (row) => <Link className="font-semibold text-slate-950" to={`/documents/records/${row.id}`}>{row.documentNumber}</Link> },
                { header: 'Title / Reference', key: 'title', render: (row) => <div><p className="font-semibold text-slate-900">{row.title}</p><p className="text-xs text-slate-400">{row.sourceReference}</p></div> },
                { header: 'Type', key: 'typeName' },
                { header: 'Source', key: 'sourceModule' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Version', key: 'version' },
                { header: 'Created', key: 'createdAt' },
                { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-3 text-sm font-semibold"><Link className="text-slate-950" to={`/documents/records/${row.id}`}>View</Link><Link className="text-slate-500" to="/documents/preview">Preview</Link></div> },
            ]}
            getRowKey={(row) => row.id}
            rows={records}
        />
    );
}

export function DocumentPreviewPanel({ preview }: { preview?: DocumentPreviewResult | { calculated?: DocumentPreviewResult['rendered']; input?: DocumentPreviewResult['input']; warnings?: string[]; errors?: string[]; breakdown?: Array<Record<string, unknown>> } }) {
    const rendered = preview && 'rendered' in preview ? preview.rendered : preview?.calculated;

    return (
        <Card className="p-5">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">Document Preview</h2>
                    <p className="mt-1 text-sm text-slate-500">Readonly backend/mock rendering. Official output, numbering, and versioning are backend-owned.</p>
                </div>
                <StatusBadge status="Preview" />
            </div>
            {rendered ? (
                <div className="space-y-4">
                    <PreviewPanel rows={[
                        { label: 'Document number', value: rendered.documentNumber },
                        { label: 'Template used', value: rendered.templateUsed },
                        { label: 'Version info', value: rendered.versionInfo },
                    ]} title="Rendered Result" />
                    <pre className="max-h-96 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">{rendered.renderedPreview}</pre>
                </div>
            ) : <DocumentPreview title="Preview output" />}
        </Card>
    );
}

export function DocumentMetadataPanel() {
    return <PreviewPanel rows={documentMockPanels.metadataValues.map((row) => ({ label: row.label, value: row.value }))} title="Metadata" />;
}

export function DocumentSourceReferencePanel({ references }: { references: DocumentSourceReference[] }) {
    return <PreviewPanel rows={references.map((row) => ({ label: row.label, value: `${row.module}: ${row.reference}` }))} title="Source References" />;
}

export function DocumentRelationsTable({ relations }: { relations: DocumentRelation[] }) {
    return <DataTable columns={[{ header: 'Relation', key: 'relationType' }, { header: 'Document #', key: 'targetDocumentNumber' }, { header: 'Target ID', key: 'targetDocumentId' }]} getRowKey={(row) => row.id} rows={relations} />;
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
    return (
        <DataTable
            columns={[
                { header: 'Order', key: 'displayOrder' },
                { header: 'Code', key: 'code' },
                { header: 'Label', key: 'label' },
                { header: 'Type', key: 'fieldType' },
                { header: 'Required', key: 'isRequired', render: (row) => <StatusBadge status={row.isRequired ? 'Required' : 'Optional'} /> },
                { header: 'Readonly', key: 'isReadonly', render: (row) => <StatusBadge status={row.isReadonly ? 'Readonly' : 'Editable'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={fields}
        />
    );
}

export function DocumentLinesTable({ lines }: { lines: DocumentLine[] }) {
    return (
        <DataTable
            columns={[
                { header: '#', key: 'lineNo' },
                { header: 'Type', key: 'lineType' },
                { header: 'Item / Label', key: 'itemLabel' },
                { header: 'Description', key: 'description' },
                { header: 'Qty', key: 'quantity' },
                { header: 'UOM', key: 'uomLabel' },
                { header: 'Unit Price', key: 'unitPrice' },
                { header: 'Discount', key: 'discountAmount' },
                { header: 'Tax', key: 'taxAmount' },
                { header: 'Line Total', key: 'lineTotal' },
            ]}
            getRowKey={(row) => row.id}
            rows={lines}
        />
    );
}

export function DocumentTemplatePreviewPanel({ template }: { template?: DocumentTemplate }) {
    if (!template) {
        return <DocumentPreviewPanel />;
    }

    return (
        <PreviewPanel
            rows={[
                { label: 'Code', value: template.code },
                { label: 'Layout', value: template.layoutType },
                { label: 'Header', value: template.headerPlaceholder },
                { label: 'Body', value: template.bodyPlaceholder },
                { label: 'Footer', value: template.footerPlaceholder },
            ]}
            title="Template Preview"
        />
    );
}

export function DocumentWorkflowPanel({ workflow }: { workflow?: DocumentWorkflow }) {
    return (
        <Card className="p-5">
            <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">{workflow?.name ?? 'Workflow'}</h2>
            <p className="mt-1 text-sm text-slate-500">Workflow transitions and allowed actions are backend-owned.</p>
            <div className="mt-5"><WorkflowTimeline steps={workflow?.steps} /></div>
        </Card>
    );
}

export function DocumentSequencePanel({ sequence }: { sequence?: DocumentSequence }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Sequence', value: sequence?.code ?? 'Select sequence' },
                { label: 'Document type', value: sequence?.documentType ?? 'Backend sequence type' },
                { label: 'Period', value: sequence?.periodType ?? 'Backend period' },
                { label: 'Next number preview', value: sequence?.nextNumberPreview ?? 'Use backend preview endpoint' },
            ]}
            title="Sequence"
        />
    );
}

export function DocumentStatusActionPanel({ document }: { document: DocumentRecord }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Current status', value: document.status },
                { label: 'Allowed actions', value: 'Backend workflow decides' },
                { label: 'Transition effects', value: 'Backend-owned' },
            ]}
            title="Status Actions"
        />
    );
}

export function DocumentAttachmentPanel({ attachments }: { attachments: DocumentAttachment[] }) {
    return (
        <div className="space-y-4">
            <AttachmentPanel />
            <DataTable columns={[{ header: 'File', key: 'fileName' }, { header: 'Type', key: 'fileType' }, { header: 'Size', key: 'fileSize' }, { header: 'Uploaded', key: 'uploadedAt' }, { header: 'By', key: 'uploadedBy' }]} getRowKey={(row) => row.id} rows={attachments} />
        </div>
    );
}

export function DocumentCommentPanel({ comments }: { comments: DocumentComment[] }) {
    return (
        <div className="space-y-4">
            <CommentPanel />
            <DataTable columns={[{ header: 'Author', key: 'author' }, { header: 'Comment', key: 'body' }, { header: 'Time', key: 'time' }]} getRowKey={(row) => row.id} rows={comments} />
        </div>
    );
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
            code: String(formData.get('code') ?? ''),
            commentsEnabled: checked(formData, 'comments_enabled'),
            description: String(formData.get('description') ?? ''),
            isActive: checked(formData, 'is_active'),
            moduleScope: String(formData.get('module_scope') ?? 'shared') as DocumentType['moduleScope'],
            name: String(formData.get('name') ?? ''),
            permissionsEnabled: checked(formData, 'permissions_enabled'),
            versioningEnabled: checked(formData, 'versioning_enabled'),
            workflowEnabled: checked(formData, 'workflow_enabled'),
        };

        try {
            const response = mode === 'edit' && type ? await documentApi.updateDocumentType(type.id, input) : await documentApi.createDocumentType(input);
            navigate('/documents/types');
            void response;
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save document type.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Document types are reusable shells, not business-specific calculation engines." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Type code</FieldLabel><Input defaultValue={type?.code} name="code" placeholder="SINV" /><FieldError message={errors.code?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Type name</FieldLabel><Input defaultValue={type?.name} name="name" placeholder="Sales Invoice" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Module / scope</FieldLabel><Select defaultValue={type?.moduleScope ?? 'shared'} name="module_scope" options={sourceModuleOptions} /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={type?.isActive ?? true} name="is_active" />Active</label>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={type?.description} name="description" placeholder="Reusable document behavior and scope." /></div>
                    </div>
                </FormSection>
                <FormSection description="Behavior flags enable shared document capabilities. Backend enforces actual behavior." title="Behavior">
                    <div className="grid gap-3 md:grid-cols-3">
                        {[['versioning_enabled', 'Versioning', type?.versioningEnabled], ['attachments_enabled', 'Attachments', type?.attachmentsEnabled], ['comments_enabled', 'Comments', type?.commentsEnabled], ['workflow_enabled', 'Workflow', type?.workflowEnabled], ['permissions_enabled', 'Permissions', type?.permissionsEnabled]].map(([name, label, defaultChecked]) => (
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={String(name)}><Checkbox defaultChecked={Boolean(defaultChecked ?? true)} name={String(name)} />{label}</label>
                        ))}
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/documents/types"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Type' : 'Create Type'}</Button></div>
            </div>
            <PreviewPanel rows={[{ label: 'Numbering', value: 'Backend Sequence module' }, { label: 'Status transitions', value: 'Backend workflow' }, { label: 'Official rendering', value: 'Backend renderer' }]} title="Document backend ownership" />
        </form>
    );
}

export function DocumentDefinitionForm({ definition, mode }: { definition?: DocumentDefinition; mode: 'create' | 'edit' }) {
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
        const fields: DocumentFieldDefinition[] = [
            {
                code: String(formData.get('field_code') ?? 'reference'),
                defaultValue: String(formData.get('field_default') ?? ''),
                displayOrder: Number(formData.get('field_order') ?? 1),
                fieldType: String(formData.get('field_type') ?? 'text') as DocumentFieldDefinition['fieldType'],
                id: 'form-field',
                isReadonly: checked(formData, 'field_readonly'),
                isRequired: checked(formData, 'field_required'),
                label: String(formData.get('field_label') ?? 'Reference'),
                validationRule: String(formData.get('field_validation') ?? ''),
            },
        ];
        const input: DocumentDefinitionFormInput = {
            allowedStatuses: ['draft', 'submitted', 'approved', 'posted'],
            code: String(formData.get('code') ?? ''),
            description: String(formData.get('description') ?? ''),
            documentTypeId: String(formData.get('document_type_id') ?? ''),
            fields,
            isActive: checked(formData, 'is_active'),
            name: String(formData.get('name') ?? ''),
            sequenceId: String(formData.get('sequence_id') ?? ''),
            sourceModule: String(formData.get('source_module') ?? 'shared') as DocumentDefinition['sourceModule'],
            templateId: String(formData.get('template_id') ?? ''),
            workflowId: String(formData.get('workflow_id') ?? ''),
        };

        try {
            const response = mode === 'edit' && definition ? await documentApi.updateDefinition(definition.id, input) : await documentApi.createDefinition(input);
            navigate(`/documents/definitions/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save document definition.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Definitions bind a document type to fields, template, sequence, and workflow." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Definition code</FieldLabel><Input defaultValue={definition?.code} name="code" placeholder="DEF-SALES-INVOICE" /></div>
                        <div className="space-y-2"><FieldLabel>Definition name</FieldLabel><Input defaultValue={definition?.name} name="name" placeholder="Sales Invoice Definition" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Document type</FieldLabel><Select defaultValue={definition?.documentTypeId} name="document_type_id" options={documentTypes.map((type) => ({ label: type.name, value: type.id }))} placeholder="Select type" /></div>
                        <div className="space-y-2"><FieldLabel>Source module</FieldLabel><Select defaultValue={definition?.sourceModule ?? 'shared'} name="source_module" options={sourceModuleOptions} /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={definition?.isActive ?? true} name="is_active" />Active</label>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={definition?.description} name="description" /></div>
                    </div>
                </FormSection>
                <FormSection description="Field definitions describe metadata. Values and validation remain backend-owned." title="Fields / Metadata">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2"><FieldLabel>Field code</FieldLabel><Input defaultValue={definition?.fields[0]?.code} name="field_code" placeholder="customer_name" /></div>
                        <div className="space-y-2"><FieldLabel>Field label</FieldLabel><Input defaultValue={definition?.fields[0]?.label} name="field_label" placeholder="Customer Name" /></div>
                        <div className="space-y-2"><FieldLabel>Field type</FieldLabel><Select defaultValue={definition?.fields[0]?.fieldType ?? 'text'} name="field_type" options={[{ label: 'Text', value: 'text' }, { label: 'Number', value: 'number' }, { label: 'Date', value: 'date' }, { label: 'Money', value: 'money' }, { label: 'Boolean', value: 'boolean' }, { label: 'JSON', value: 'json' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Display order</FieldLabel><Input defaultValue={definition?.fields[0]?.displayOrder ?? 1} name="field_order" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Default value</FieldLabel><Input defaultValue={definition?.fields[0]?.defaultValue} name="field_default" /></div>
                        <div className="space-y-2"><FieldLabel>Validation placeholder</FieldLabel><Input defaultValue={definition?.fields[0]?.validationRule} name="field_validation" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={definition?.fields[0]?.isRequired} name="field_required" />Required</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={definition?.fields[0]?.isReadonly} name="field_readonly" />Readonly</label>
                    </div>
                </FormSection>
                <FormSection description="Backend will use selected template, sequence, and workflow during record creation/rendering." title="Template, Sequence, Workflow">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2"><FieldLabel>Template</FieldLabel><Select defaultValue={definition?.templateId} name="template_id" options={documentTemplates.map((template) => ({ label: template.name, value: template.id }))} /></div>
                        <div className="space-y-2"><FieldLabel>Sequence</FieldLabel><Select defaultValue={definition?.sequenceId} name="sequence_id" options={documentSequences.map((sequence) => ({ label: sequence.code, value: sequence.id }))} /></div>
                        <div className="space-y-2"><FieldLabel>Workflow</FieldLabel><Select defaultValue={definition?.workflowId} name="workflow_id" options={documentWorkflows.map((workflow) => ({ label: workflow.name, value: workflow.id }))} /></div>
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/documents/definitions"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Definition' : 'Create Definition'}</Button></div>
            </div>
            <DocumentTemplatePreviewPanel template={documentTemplates.find((template) => template.id === definition?.templateId)} />
        </form>
    );
}

export function DocumentTemplateForm({ mode, template }: { mode: 'create' | 'edit'; template?: DocumentTemplate }) {
    const navigate = useNavigate();

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const input: DocumentTemplateFormInput = {
            bodyPlaceholder: String(formData.get('body') ?? ''),
            code: String(formData.get('code') ?? ''),
            documentTypeId: String(formData.get('document_type_id') ?? ''),
            footerPlaceholder: String(formData.get('footer') ?? ''),
            headerPlaceholder: String(formData.get('header') ?? ''),
            isActive: checked(formData, 'is_active'),
            layoutType: String(formData.get('layout_type') ?? 'html') as DocumentTemplate['layoutType'],
            name: String(formData.get('name') ?? ''),
        };
        const response = mode === 'edit' && template ? await documentApi.updateTemplate(template.id, input) : await documentApi.createTemplate(input);
        navigate('/documents/templates');
        void response;
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                <FormSection description="Templates define renderer inputs. Official PDF/HTML generation happens in backend." title="Template">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Template code</FieldLabel><Input defaultValue={template?.code} name="code" /></div>
                        <div className="space-y-2"><FieldLabel>Template name</FieldLabel><Input defaultValue={template?.name} name="name" /></div>
                        <div className="space-y-2"><FieldLabel>Document type</FieldLabel><Select defaultValue={template?.documentTypeId} name="document_type_id" options={documentTypes.map((type) => ({ label: type.name, value: type.id }))} /></div>
                        <div className="space-y-2"><FieldLabel>Layout type</FieldLabel><Select defaultValue={template?.layoutType ?? 'html'} name="layout_type" options={[{ label: 'HTML', value: 'html' }, { label: 'PDF', value: 'pdf' }, { label: 'Thermal', value: 'thermal' }, { label: 'Plain', value: 'plain' }]} /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Header placeholder</FieldLabel><Textarea defaultValue={template?.headerPlaceholder} name="header" /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Body placeholder</FieldLabel><Textarea defaultValue={template?.bodyPlaceholder} name="body" /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Footer placeholder</FieldLabel><Textarea defaultValue={template?.footerPlaceholder} name="footer" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={template?.isActive ?? true} name="is_active" />Active</label>
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/documents/templates"><Button variant="secondary">Cancel</Button></Link><Button type="submit" variant="blue">{mode === 'edit' ? 'Update Template' : 'Create Template'}</Button></div>
            </div>
            <DocumentTemplatePreviewPanel template={template} />
        </form>
    );
}
