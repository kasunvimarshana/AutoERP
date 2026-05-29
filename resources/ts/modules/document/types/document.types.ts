export type DocumentStatus = 'draft' | 'submitted' | 'approved' | 'posted' | 'finalized' | 'cancelled' | 'archived';

export type DocumentSourceModule =
    | 'purchase'
    | 'sales'
    | 'vehicle_service'
    | 'vehicle_rental'
    | 'voucher'
    | 'finance'
    | 'supplier'
    | 'customer'
    | 'hr'
    | 'core';

export type DocumentRecord = {
    createdAt: string;
    createdBy: string;
    definitionId: string;
    documentNumber: string;
    id: string;
    sourceModule: DocumentSourceModule;
    sourceReference: string;
    status: DocumentStatus;
    title: string;
    typeCode: string;
    typeName: string;
    version: string;
};

export type DocumentType = {
    attachmentsEnabled: boolean;
    code: string;
    commentsEnabled: boolean;
    description: string;
    id: string;
    isActive: boolean;
    moduleScope: DocumentSourceModule | 'shared';
    name: string;
    permissionsEnabled: boolean;
    versioningEnabled: boolean;
    workflowEnabled: boolean;
};

export type DocumentFieldDefinition = {
    code: string;
    defaultValue?: string;
    displayOrder: number;
    fieldType: 'text' | 'number' | 'date' | 'money' | 'boolean' | 'json';
    id: string;
    isReadonly: boolean;
    isRequired: boolean;
    label: string;
    validationRule?: string;
};

export type DocumentDefinition = {
    allowedStatuses: DocumentStatus[];
    code: string;
    description: string;
    documentTypeId: string;
    documentTypeName: string;
    fields: DocumentFieldDefinition[];
    id: string;
    isActive: boolean;
    name: string;
    sequenceId: string;
    sourceModule: DocumentSourceModule | 'shared';
    templateId: string;
    updatedAt: string;
    version: string;
    workflowId: string;
};

export type DocumentTemplate = {
    bodyPlaceholder: string;
    code: string;
    documentTypeId: string;
    footerPlaceholder: string;
    headerPlaceholder: string;
    id: string;
    isActive: boolean;
    layoutType: 'html' | 'pdf' | 'thermal' | 'plain';
    name: string;
};

export type DocumentTemplateSection = {
    content: string;
    displayOrder: number;
    id: string;
    isActive: boolean;
    label: string;
    sectionKey: string;
    sectionType: 'header' | 'body' | 'line_table' | 'totals' | 'signature' | 'terms' | 'custom';
    templateId: string;
};

export type DocumentMetadataValue = {
    code: string;
    label: string;
    value: string;
};

export type DocumentLine = {
    description: string;
    discountAmount: string;
    displayOrder: number;
    documentId?: string;
    id: string;
    itemLabel: string;
    lineNo: number;
    lineTotal: string;
    lineType: string;
    quantity: string;
    sourceLineId?: string;
    sourceLineType?: string;
    taxAmount: string;
    unitPrice: string;
    uomLabel: string;
};

export type DocumentSourceReference = {
    id: string;
    label: string;
    module: DocumentSourceModule;
    reference: string;
};

export type DocumentRelation = {
    id: string;
    relationType: string;
    targetDocumentId: string;
    targetDocumentNumber: string;
};

export type DocumentPermission = {
    ability: string;
    id: string;
    principal: string;
    principalType: string;
};

export type DocumentEvent = {
    actor: string;
    eventType: string;
    id: string;
    message: string;
    time: string;
};

export type DocumentActivity = {
    actor: string;
    activityType: string;
    description: string;
    id: string;
    time: string;
};

export type DocumentVersion = {
    createdAt: string;
    createdBy: string;
    id: string;
    note: string;
    version: string;
};

export type DocumentAttachment = {
    fileName: string;
    fileSize: string;
    fileType: string;
    id: string;
    uploadedAt: string;
    uploadedBy: string;
};

export type DocumentComment = {
    author: string;
    body: string;
    id: string;
    time: string;
};

export type DocumentWorkflowStep = {
    description: string;
    id: string;
    label: string;
    status: 'done' | 'current' | 'pending';
};

export type DocumentWorkflow = {
    id: string;
    isActive: boolean;
    name: string;
    steps: DocumentWorkflowStep[];
    usage: string;
};

export type DocumentSequence = {
    code: string;
    documentType: string;
    id: string;
    nextNumberPreview: string;
    periodType: 'yearly' | 'monthly' | 'infinite';
    prefix: string;
    status: 'active' | 'inactive';
};

export type DocumentPreviewResult = {
    breakdown: Array<{ label: string; value: string }>;
    errors: string[];
    input: {
        definitionId: string;
        metadata: Record<string, string>;
        sourceId: string;
        sourceModule: string;
    };
    rendered: {
        documentNumber: string;
        renderedPreview: string;
        templateUsed: string;
        versionInfo: string;
    };
    warnings: string[];
};

export type DocumentAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
};

export type DocumentTypeFormInput = {
    attachmentsEnabled: boolean;
    code: string;
    commentsEnabled: boolean;
    description: string;
    isActive: boolean;
    moduleScope: DocumentType['moduleScope'];
    name: string;
    permissionsEnabled: boolean;
    versioningEnabled: boolean;
    workflowEnabled: boolean;
};

export type DocumentDefinitionFormInput = {
    allowedStatuses: DocumentStatus[];
    code: string;
    description: string;
    documentTypeId: string;
    fields: DocumentFieldDefinition[];
    isActive: boolean;
    name: string;
    sequenceId: string;
    sourceModule: DocumentDefinition['sourceModule'];
    templateId: string;
    workflowId: string;
};

export type DocumentTemplateFormInput = {
    bodyPlaceholder: string;
    code: string;
    documentTypeId: string;
    footerPlaceholder: string;
    headerPlaceholder: string;
    isActive: boolean;
    layoutType: DocumentTemplate['layoutType'];
    name: string;
};
