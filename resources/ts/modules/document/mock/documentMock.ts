import type {
    DocumentAttachment,
    DocumentActivity,
    DocumentAuditEntry,
    DocumentComment,
    DocumentDefinition,
    DocumentEvent,
    DocumentLine,
    DocumentMetadataValue,
    DocumentPermission,
    DocumentPreviewResult,
    DocumentRecord,
    DocumentRelation,
    DocumentSequence,
    DocumentSourceReference,
    DocumentTemplate,
    DocumentType,
    DocumentVersion,
    DocumentWorkflow,
} from '../types/document.types';

export const documentTypes: DocumentType[] = [
    { attachmentsEnabled: true, code: 'PINV', commentsEnabled: true, description: 'Reusable purchase invoice document type.', id: 'type-purchase-invoice', isActive: true, moduleScope: 'purchase', name: 'Purchase Invoice', permissionsEnabled: true, versioningEnabled: true, workflowEnabled: true },
    { attachmentsEnabled: true, code: 'SINV', commentsEnabled: true, description: 'Reusable sales invoice document type.', id: 'type-sales-invoice', isActive: true, moduleScope: 'sales', name: 'Sales Invoice', permissionsEnabled: true, versioningEnabled: true, workflowEnabled: true },
    { attachmentsEnabled: true, code: 'SVINV', commentsEnabled: true, description: 'Vehicle service invoice shell. Totals come from VehicleService.', id: 'type-service-invoice', isActive: true, moduleScope: 'vehicle_service', name: 'Service Invoice', permissionsEnabled: true, versioningEnabled: true, workflowEnabled: true },
    { attachmentsEnabled: true, code: 'RAG', commentsEnabled: true, description: 'Vehicle rental agreement document.', id: 'type-rental-agreement', isActive: true, moduleScope: 'vehicle_rental', name: 'Rental Agreement', permissionsEnabled: true, versioningEnabled: true, workflowEnabled: true },
    { attachmentsEnabled: false, code: 'VCHR', commentsEnabled: true, description: 'Voucher document wrapper.', id: 'type-voucher', isActive: true, moduleScope: 'voucher', name: 'Voucher', permissionsEnabled: true, versioningEnabled: true, workflowEnabled: true },
    { attachmentsEnabled: true, code: 'JOBCARD', commentsEnabled: true, description: 'Workshop job card document.', id: 'type-job-card', isActive: true, moduleScope: 'vehicle_service', name: 'Job Card', permissionsEnabled: true, versioningEnabled: true, workflowEnabled: true },
];

export const documentTemplates: DocumentTemplate[] = [
    { bodyPlaceholder: '{{document_body}}', code: 'TPL-INVOICE-A4', documentTypeId: 'type-sales-invoice', footerPlaceholder: '{{legal_footer}}', headerPlaceholder: '{{company_header}}', id: 'template-invoice-a4', isActive: true, layoutType: 'pdf', name: 'A4 Invoice Template' },
    { bodyPlaceholder: '{{job_card_sections}}', code: 'TPL-JOBCARD', documentTypeId: 'type-job-card', footerPlaceholder: '{{advisor_signature}}', headerPlaceholder: '{{workshop_header}}', id: 'template-job-card', isActive: true, layoutType: 'html', name: 'Workshop Job Card Template' },
    { bodyPlaceholder: '{{agreement_terms}}', code: 'TPL-RENTAL-AG', documentTypeId: 'type-rental-agreement', footerPlaceholder: '{{customer_signature}}', headerPlaceholder: '{{rental_header}}', id: 'template-rental-agreement', isActive: true, layoutType: 'pdf', name: 'Rental Agreement Template' },
];

export const documentSequences: DocumentSequence[] = [
    { code: 'SEQ-SINV', documentType: 'Sales Invoice', id: 'sequence-sales-invoice', nextNumberPreview: 'SINV-2026-000128', periodType: 'yearly', prefix: 'SINV-{YYYY}-', status: 'active' },
    { code: 'SEQ-SVINV', documentType: 'Service Invoice', id: 'sequence-service-invoice', nextNumberPreview: 'SVINV-2026-000044', periodType: 'yearly', prefix: 'SVINV-{YYYY}-', status: 'active' },
    { code: 'SEQ-JOBCARD', documentType: 'Job Card', id: 'sequence-job-card', nextNumberPreview: 'JOB-2026-000512', periodType: 'yearly', prefix: 'JOB-{YYYY}-', status: 'active' },
    { code: 'SEQ-RAG', documentType: 'Rental Agreement', id: 'sequence-rental-agreement', nextNumberPreview: 'RAG-2026-000071', periodType: 'yearly', prefix: 'RAG-{YYYY}-', status: 'active' },
];

export const documentWorkflows: DocumentWorkflow[] = [
    {
        id: 'workflow-standard-approval',
        isActive: true,
        name: 'Standard Document Approval',
        steps: [
            { description: 'Document is editable.', id: 'step-draft', label: 'Draft', status: 'done' },
            { description: 'Submitted to approver.', id: 'step-submitted', label: 'Submitted', status: 'current' },
            { description: 'Backend approval action.', id: 'step-approved', label: 'Approved', status: 'pending' },
            { description: 'Finalized or posted by source module.', id: 'step-posted', label: 'Posted', status: 'pending' },
        ],
        usage: 'Invoices, vouchers, agreements',
    },
    {
        id: 'workflow-job-card',
        isActive: true,
        name: 'Job Card Lifecycle',
        steps: [
            { description: 'Intake captured.', id: 'step-open', label: 'Open', status: 'done' },
            { description: 'Work in progress.', id: 'step-progress', label: 'In Progress', status: 'current' },
            { description: 'Supervisor review.', id: 'step-review', label: 'Review', status: 'pending' },
            { description: 'Closed by VehicleService.', id: 'step-closed', label: 'Closed', status: 'pending' },
        ],
        usage: 'Vehicle service job cards',
    },
];

export const documentDefinitions: DocumentDefinition[] = [
    {
        allowedStatuses: ['draft', 'submitted', 'approved', 'posted', 'cancelled'],
        code: 'DEF-SALES-INVOICE',
        description: 'Module-agnostic sales invoice document definition. Sales supplies calculated values.',
        documentTypeId: 'type-sales-invoice',
        documentTypeName: 'Sales Invoice',
        fields: [
            { code: 'customer_name', displayOrder: 1, fieldType: 'text', id: 'field-customer-name', isReadonly: true, isRequired: true, label: 'Customer Name' },
            { code: 'invoice_total', displayOrder: 2, fieldType: 'money', id: 'field-invoice-total', isReadonly: true, isRequired: true, label: 'Invoice Total', validationRule: 'backend-calculated' },
        ],
        id: 'definition-sales-invoice',
        isActive: true,
        name: 'Sales Invoice Definition',
        sequenceId: 'sequence-sales-invoice',
        sourceModule: 'sales',
        templateId: 'template-invoice-a4',
        updatedAt: '2026-05-22',
        version: '1',
        workflowId: 'workflow-standard-approval',
    },
    {
        allowedStatuses: ['draft', 'submitted', 'approved', 'posted', 'cancelled'],
        code: 'DEF-SERVICE-INVOICE',
        description: 'Vehicle service invoice document definition. Service module owns labour/parts/tax totals.',
        documentTypeId: 'type-service-invoice',
        documentTypeName: 'Service Invoice',
        fields: [
            { code: 'job_card_number', displayOrder: 1, fieldType: 'text', id: 'field-job-card', isReadonly: true, isRequired: true, label: 'Job Card Number' },
            { code: 'service_total', displayOrder: 2, fieldType: 'money', id: 'field-service-total', isReadonly: true, isRequired: true, label: 'Service Total', validationRule: 'backend-calculated' },
        ],
        id: 'definition-service-invoice',
        isActive: true,
        name: 'Service Invoice Definition',
        sequenceId: 'sequence-service-invoice',
        sourceModule: 'vehicle_service',
        templateId: 'template-invoice-a4',
        updatedAt: '2026-05-23',
        version: '2',
        workflowId: 'workflow-standard-approval',
    },
    {
        allowedStatuses: ['draft', 'submitted', 'finalized', 'cancelled'],
        code: 'DEF-RENTAL-AGREEMENT',
        description: 'Rental agreement shell with terms, attachments, comments, and status history.',
        documentTypeId: 'type-rental-agreement',
        documentTypeName: 'Rental Agreement',
        fields: [
            { code: 'vehicle_registration', displayOrder: 1, fieldType: 'text', id: 'field-vehicle', isReadonly: true, isRequired: true, label: 'Vehicle Registration' },
            { code: 'rental_period', displayOrder: 2, fieldType: 'text', id: 'field-period', isReadonly: true, isRequired: true, label: 'Rental Period' },
        ],
        id: 'definition-rental-agreement',
        isActive: true,
        name: 'Rental Agreement Definition',
        sequenceId: 'sequence-rental-agreement',
        sourceModule: 'vehicle_rental',
        templateId: 'template-rental-agreement',
        updatedAt: '2026-05-24',
        version: '1',
        workflowId: 'workflow-standard-approval',
    },
];

export const documentRecords: DocumentRecord[] = [
    { createdAt: '2026-05-21', createdBy: 'Sales Clerk', definitionId: 'definition-sales-invoice', documentNumber: 'SINV-2026-000127', id: 'doc-sales-invoice-127', sourceModule: 'sales', sourceReference: 'SO-2026-000219', status: 'posted', title: 'Sales invoice for Apex Logistics', typeCode: 'SINV', typeName: 'Sales Invoice', version: '3' },
    { createdAt: '2026-05-22', createdBy: 'Service Advisor', definitionId: 'definition-service-invoice', documentNumber: 'SVINV-2026-000043', id: 'doc-service-invoice-43', sourceModule: 'vehicle_service', sourceReference: 'JOB-2026-000511', status: 'approved', title: 'Service invoice for WP-CAB-4219', typeCode: 'SVINV', typeName: 'Service Invoice', version: '2' },
    { createdAt: '2026-05-23', createdBy: 'Rental Officer', definitionId: 'definition-rental-agreement', documentNumber: 'RAG-2026-000070', id: 'doc-rental-agreement-70', sourceModule: 'vehicle_rental', sourceReference: 'AGR-2026-000088', status: 'finalized', title: 'Rental agreement for SUV monthly hire', typeCode: 'RAG', typeName: 'Rental Agreement', version: '1' },
    { createdAt: '2026-05-24', createdBy: 'Voucher Clerk', definitionId: 'definition-sales-invoice', documentNumber: 'VCHR-2026-000312', id: 'doc-voucher-312', sourceModule: 'voucher', sourceReference: 'JV-2026-000080', status: 'submitted', title: 'Voucher approval document', typeCode: 'VCHR', typeName: 'Voucher', version: '1' },
    { createdAt: '2026-05-25', createdBy: 'Service Advisor', definitionId: 'definition-service-invoice', documentNumber: 'JOB-2026-000512', id: 'doc-job-card-512', sourceModule: 'vehicle_service', sourceReference: 'JOB-2026-000512', status: 'draft', title: 'Job card for brake inspection', typeCode: 'JOBCARD', typeName: 'Job Card', version: '1' },
];

export const metadataValues: DocumentMetadataValue[] = [
    { code: 'customer_name', label: 'Customer', value: 'Apex Logistics Fleet' },
    { code: 'source_total', label: 'Source Total', value: 'Readonly source-module value' },
    { code: 'prepared_by', label: 'Prepared By', value: 'Backend user context' },
];

export const documentLines: DocumentLine[] = [
    { description: 'Printable source line supplied by Sales module', discountAmount: 'Source provided', displayOrder: 1, documentId: 'doc-sales-invoice-127', id: 'line-001', itemLabel: 'Engine Oil 10W-40', lineNo: 1, lineTotal: 'Source provided', lineType: 'item', quantity: '2', sourceLineId: 'sales-line-1001', sourceLineType: 'sales_invoice_line', taxAmount: 'Source provided', unitPrice: 'Source provided', uomLabel: 'L' },
    { description: 'Printable labour line supplied by VehicleService module', discountAmount: 'Source provided', displayOrder: 2, documentId: 'doc-service-invoice-512', id: 'line-002', itemLabel: 'General Technician Labour', lineNo: 2, lineTotal: 'Source provided', lineType: 'labour', quantity: '1.5', sourceLineId: 'job-labour-204', sourceLineType: 'service_labour_line', taxAmount: 'Source provided', unitPrice: 'Source provided', uomLabel: 'HOUR' },
];

export const sourceReferences: DocumentSourceReference[] = [
    { id: 'src-001', label: 'Source Sales Order', module: 'sales', reference: 'SO-2026-000219' },
    { id: 'src-002', label: 'Source Job Card', module: 'vehicle_service', reference: 'JOB-2026-000511' },
];

export const documentRelations: DocumentRelation[] = [
    { id: 'rel-001', relationType: 'replaces', targetDocumentId: 'doc-sales-invoice-126', targetDocumentNumber: 'SINV-2026-000126' },
    { id: 'rel-002', relationType: 'supports', targetDocumentId: 'doc-job-card-512', targetDocumentNumber: 'JOB-2026-000512' },
];

export const documentPermissions: DocumentPermission[] = [
    { ability: 'view', id: 'perm-001', principal: 'Finance Team', principalType: 'role' },
    { ability: 'approve', id: 'perm-002', principal: 'Service Manager', principalType: 'role' },
];

export const documentEvents: DocumentEvent[] = [
    { actor: 'System', eventType: 'number_assigned', id: 'event-001', message: 'Document number assigned by backend sequence service.', time: '2026-05-21 09:10' },
    { actor: 'Sales Clerk', eventType: 'status_changed', id: 'event-002', message: 'Document submitted for approval.', time: '2026-05-21 09:24' },
];

export const documentActivities: DocumentActivity[] = [
    { actor: 'System', activityType: 'created', description: 'Document created from source payload.', id: 'activity-001', time: '2026-05-21 09:10' },
    { actor: 'Approver', activityType: 'reviewed', description: 'Document reviewed without changing source totals.', id: 'activity-002', time: '2026-05-21 11:35' },
];

export const documentVersions: DocumentVersion[] = [
    { createdAt: '2026-05-21 09:10', createdBy: 'System', id: 'ver-001', note: 'Initial backend-rendered version.', version: '1' },
    { createdAt: '2026-05-21 11:35', createdBy: 'Approver', id: 'ver-002', note: 'Approval metadata updated.', version: '2' },
];

export const documentAttachments: DocumentAttachment[] = [
    { fileName: 'signed-agreement.pdf', fileSize: '412 KB', fileType: 'PDF', id: 'att-001', uploadedAt: '2026-05-23 14:20', uploadedBy: 'Rental Officer' },
    { fileName: 'inspection-photo.jpg', fileSize: '1.8 MB', fileType: 'Image', id: 'att-002', uploadedAt: '2026-05-25 10:15', uploadedBy: 'Service Advisor' },
];

export const documentComments: DocumentComment[] = [
    { author: 'Finance Manager', body: 'Confirmed that the document shell matches source posting reference.', id: 'comment-001', time: '2026-05-21 12:00' },
    { author: 'Service Manager', body: 'Attach customer approval before finalizing.', id: 'comment-002', time: '2026-05-25 10:45' },
];

export const documentAudit: DocumentAuditEntry[] = [
    { actor: 'System', description: 'Created document from source module event.', id: 'audit-001', time: '2026-05-21 09:10' },
    { actor: 'Approver', description: 'Approved document through backend workflow.', id: 'audit-002', time: '2026-05-21 11:40' },
];

export function getDocumentById(documentId: string) {
    return documentRecords.find((document) => document.id === documentId) ?? documentRecords[0];
}

export function getDocumentTypeById(typeId: string) {
    return documentTypes.find((type) => type.id === typeId) ?? documentTypes[0];
}

export function getDefinitionById(definitionId: string) {
    return documentDefinitions.find((definition) => definition.id === definitionId) ?? documentDefinitions[0];
}

export function getTemplateById(templateId: string) {
    return documentTemplates.find((template) => template.id === templateId) ?? documentTemplates[0];
}

export function mockDocumentPreview(input: DocumentPreviewResult['input']): DocumentPreviewResult {
    const definition = getDefinitionById(input.definitionId);
    const template = getTemplateById(definition.templateId);

    return {
        breakdown: [
            { label: 'Definition', value: definition.name },
            { label: 'Template', value: template.name },
            { label: 'Numbering', value: 'Backend sequence preview placeholder' },
            { label: 'Rendering', value: 'Backend document renderer placeholder' },
        ],
        errors: [],
        input,
        rendered: {
            documentNumber: 'DOC-PREVIEW-2026-000001',
            renderedPreview: `${template.headerPlaceholder}\n${definition.name}\n${JSON.stringify(input.metadata, null, 2)}\n${template.footerPlaceholder}`,
            templateUsed: template.name,
            versionInfo: 'Preview only. Official version is backend-owned.',
        },
        warnings: ['Mock preview only. Official rendering and versioning must come from backend.'],
    };
}
