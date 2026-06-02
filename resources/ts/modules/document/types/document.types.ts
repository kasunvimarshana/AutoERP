export type DocumentStatus = 'draft' | 'submitted' | 'approved' | 'posted' | 'finalized' | 'cancelled' | 'archived' | 'active';

export type DocumentSourceModule = string;

export type LookupOption = {
    label: string;
    value: string;
};

export type DocumentRecord = {
    createdAt: string;
    data: Record<string, unknown>;
    definitionId: string;
    documentDate: string;
    documentNumber: string;
    dueDate: string;
    grandTotal: string;
    id: string;
    items: DocumentLine[];
    notes: string;
    ownerId?: string;
    partyId?: string;
    sourceId?: string;
    sourceModule: DocumentSourceModule;
    sourceReference: string;
    sourceType: string;
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
    defaultStatus: DocumentStatus;
    description: string;
    id: string;
    isActive: boolean;
    moduleScope: DocumentSourceModule;
    name: string;
    requiresSource: boolean;
    versioningEnabled: boolean;
    workflowEnabled: boolean;
};

export type DocumentFieldDefinition = {
    code: string;
    dataType: 'text' | 'number' | 'date' | 'money' | 'boolean' | 'json';
    defaultValue: string;
    displayOrder: number;
    id: string;
    isReadonly: boolean;
    isRequired: boolean;
    label: string;
    sectionKey: string;
    validationRule: string;
};

export type DocumentDefinition = {
    code: string;
    defaultStatus: DocumentStatus;
    description: string;
    documentTypeCode: string;
    documentTypeId: string;
    documentTypeName: string;
    fields: DocumentFieldDefinition[];
    id: string;
    isActive: boolean;
    name: string;
    sequenceId: string;
    sourceModule: DocumentSourceModule;
    supportsVersions: boolean;
    templateId: string;
    updatedAt: string;
    version: string;
    workflowId: string;
};

export type DocumentTemplate = {
    bodyContent: string;
    code: string;
    documentTypeId: string;
    footerContent: string;
    headerContent: string;
    id: string;
    isActive: boolean;
    layoutType: 'html' | 'pdf' | 'thermal' | 'plain';
    name: string;
};

export type DocumentLine = {
    description: string;
    discountAmount: string;
    displayOrder: number;
    documentId: string;
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

export type DocumentMetadataValue = {
    code: string;
    label: string;
    value: string;
    valueType: string;
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
    documentTypeName: string;
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

export type DocumentPreviewInput = {
    definitionId: string;
    documentNumber?: string;
    metadata: Record<string, string>;
    sourceId: string;
    sourceModule: string;
    sourceReference: string;
};

export type DocumentPreviewRendered = {
    definitionId: string;
    definitionName: string;
    documentNumber: string;
    html: string;
    templateId: string;
    templateName: string;
    text: string;
};

export type DocumentTypeFormInput = {
    attachmentsEnabled: boolean;
    code: string;
    commentsEnabled: boolean;
    description: string;
    isActive: boolean;
    moduleScope: DocumentType['moduleScope'];
    name: string;
    requiresSource: boolean;
    versioningEnabled: boolean;
    workflowEnabled: boolean;
};

export type DocumentDefinitionFormInput = {
    code: string;
    defaultStatus: DocumentStatus;
    description: string;
    documentTypeId: string;
    fields: DocumentFieldDefinition[];
    isActive: boolean;
    name: string;
    sequenceId: string;
    sourceModule: DocumentDefinition['sourceModule'];
    supportsVersions: boolean;
    templateId: string;
    workflowId: string;
};

export type DocumentTemplateFormInput = {
    bodyContent: string;
    code: string;
    documentTypeId: string;
    footerContent: string;
    headerContent: string;
    isActive: boolean;
    layoutType: DocumentTemplate['layoutType'];
    name: string;
};

export type DocumentCreateItemInput = {
    data?: Record<string, unknown>;
    description?: string;
    itemType: string;
    lineTotal: string | number;
};

export type DocumentCreateInput = {
    data?: Record<string, unknown>;
    documentDate: string;
    documentDefinitionId?: string;
    documentTypeId: string;
    dueDate?: string;
    items: DocumentCreateItemInput[];
    notes?: string;
    ownerId?: string;
    partyId?: string;
    sourceId?: string;
    sourceModule?: string;
    sourceReference?: string;
    sourceType?: string;
    title?: string;
};
