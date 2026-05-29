import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    documentAttachments,
    documentActivities,
    documentAudit,
    documentComments,
    documentDefinitions,
    documentEvents,
    documentLines,
    documentPermissions,
    documentRecords,
    documentRelations,
    documentSequences,
    documentTemplates,
    documentTypes,
    documentVersions,
    documentWorkflows,
    getDefinitionById,
    getDocumentById,
    getDocumentTypeById,
    getTemplateById,
    metadataValues,
    mockDocumentPreview,
    sourceReferences,
} from '../mock/documentMock';
import type {
    DocumentAttachment,
    DocumentActivity,
    DocumentAuditEntry,
    DocumentComment,
    DocumentDefinition,
    DocumentDefinitionFormInput,
    DocumentEvent,
    DocumentFieldDefinition,
    DocumentMetadataValue,
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

type BackendRecord = Record<string, unknown>;

const DOCUMENT_API_MODE = import.meta.env.VITE_DOCUMENT_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return DOCUMENT_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (DOCUMENT_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asBool(value: unknown, fallback = false) {
    return value === null || value === undefined ? fallback : Boolean(value);
}

function normalizeStatus(value: unknown): DocumentStatus {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: DocumentStatus[] = ['draft', 'submitted', 'approved', 'posted', 'finalized', 'cancelled', 'archived'];

    return allowed.includes(status as DocumentStatus) ? status as DocumentStatus : 'draft';
}

function data(raw: BackendRecord): BackendRecord {
    return raw.data && typeof raw.data === 'object' ? raw.data as BackendRecord : {};
}

function normalizeDocument(raw: BackendRecord): DocumentRecord {
    const meta = data(raw);
    const typeId = asString(raw.document_type_id);
    const type = documentTypes.find((row) => row.id === typeId || row.code === asString(meta.type_code));

    return {
        createdAt: asString(raw.created_at ?? raw.document_date, 'Backend date pending'),
        createdBy: asString(meta.created_by, 'Backend user'),
        definitionId: asString(meta.definition_id, documentDefinitions[0].id),
        documentNumber: asString(raw.document_number, `DOC-${asString(raw.id, 'backend')}`),
        id: asString(raw.id),
        sourceModule: asString(meta.source_module, 'core') as DocumentRecord['sourceModule'],
        sourceReference: asString(meta.source_reference, 'Backend source reference'),
        status: normalizeStatus(raw.status),
        title: asString(meta.title ?? raw.notes, 'Backend document'),
        typeCode: type?.code ?? asString(meta.type_code, 'DOC'),
        typeName: type?.name ?? asString(meta.type_name, 'Document'),
        version: asString(meta.version, 'Backend version'),
    };
}

function normalizeDocumentLine(raw: BackendRecord): DocumentLine {
    return {
        description: asString(raw.description),
        discountAmount: asString(raw.discount_amount ?? raw.discountAmount ?? '0'),
        displayOrder: Number(raw.display_order ?? raw.displayOrder ?? raw.line_no ?? raw.lineNo ?? 1),
        documentId: asString(raw.document_id ?? raw.documentId),
        id: asString(raw.id),
        itemLabel: asString(raw.item_label ?? raw.itemLabel),
        lineNo: Number(raw.line_no ?? raw.lineNo ?? raw.display_order ?? raw.displayOrder ?? 1),
        lineTotal: asString(raw.line_total ?? raw.lineTotal ?? '0'),
        lineType: asString(raw.line_type ?? raw.lineType, 'line') as DocumentLine['lineType'],
        quantity: asString(raw.quantity ?? ''),
        sourceLineId: raw.source_line_id === null || raw.source_line_id === undefined ? undefined : asString(raw.source_line_id),
        sourceLineType: raw.source_line_type === null || raw.source_line_type === undefined ? undefined : asString(raw.source_line_type),
        taxAmount: asString(raw.tax_amount ?? raw.taxAmount ?? '0'),
        unitPrice: asString(raw.unit_price ?? raw.unitPrice ?? ''),
        uomLabel: asString(raw.uom_label ?? raw.uomLabel),
    };
}

function normalizeDocumentType(raw: BackendRecord): DocumentType {
    const meta = data(raw);

    return {
        attachmentsEnabled: asBool(raw.supports_attachments ?? meta.attachments_enabled, true),
        code: asString(raw.code, `TYPE-${asString(raw.id, 'backend')}`),
        commentsEnabled: asBool(raw.supports_comments ?? meta.comments_enabled, true),
        description: asString(raw.description ?? meta.description, ''),
        id: asString(raw.id),
        isActive: raw.is_active === false ? false : true,
        moduleScope: asString(raw.module_scope ?? meta.module_scope, 'shared') as DocumentType['moduleScope'],
        name: asString(raw.name, 'Document Type'),
        permissionsEnabled: asBool(meta.permissions_enabled, true),
        versioningEnabled: asBool(raw.supports_versions ?? meta.versioning_enabled, true),
        workflowEnabled: asBool(raw.supports_workflow ?? meta.workflow_enabled, true),
    };
}

function normalizeDefinition(raw: BackendRecord): DocumentDefinition {
    const settings = raw.settings && typeof raw.settings === 'object' ? raw.settings as BackendRecord : {};
    const fields = Array.isArray(raw.fields) ? raw.fields as BackendRecord[] : [];
    const documentTypeId = asString(raw.document_type_id);
    const type = documentTypes.find((row) => row.id === documentTypeId);

    return {
        allowedStatuses: Array.isArray(settings.allowed_statuses) ? settings.allowed_statuses as DocumentStatus[] : ['draft', 'submitted', 'approved'],
        code: asString(raw.definition_code ?? settings.code, `DEF-${asString(raw.id, 'backend')}`),
        description: asString(raw.description ?? settings.description, ''),
        documentTypeId,
        documentTypeName: type?.name ?? asString(settings.document_type_name, 'Document Type'),
        fields: fields.map((field, index): DocumentFieldDefinition => ({
            code: asString(field.field_key ?? field.code, `field_${index + 1}`),
            defaultValue: asString(field.default_value, ''),
            displayOrder: Number(field.display_order ?? index + 1),
            fieldType: asString(field.data_type ?? field.field_type, 'text') as DocumentFieldDefinition['fieldType'],
            id: asString(field.id, `field-${index + 1}`),
            isReadonly: asBool(field.is_readonly),
            isRequired: asBool(field.is_required),
            label: asString(field.label, `Field ${index + 1}`),
            validationRule: asString(field.validation_rule, ''),
        })),
        id: asString(raw.id),
        isActive: raw.is_active === false ? false : true,
        name: asString(raw.name, 'Document Definition'),
        sequenceId: asString(raw.sequence_id ?? settings.sequence_id, ''),
        sourceModule: asString(raw.source_module ?? settings.source_module, 'shared') as DocumentDefinition['sourceModule'],
        templateId: asString(raw.template_id ?? settings.template_id, ''),
        updatedAt: asString(raw.updated_at, 'Backend timestamp pending'),
        version: asString(raw.version, '1'),
        workflowId: asString(raw.workflow_id ?? settings.workflow_id, ''),
    };
}

function normalizeTemplate(raw: BackendRecord): DocumentTemplate {
    return {
        bodyPlaceholder: asString(raw.body_content, ''),
        code: asString(raw.template_code ?? raw.code, `TPL-${asString(raw.id, 'backend')}`),
        documentTypeId: asString(raw.document_type_id, ''),
        footerPlaceholder: asString(raw.footer_content, ''),
        headerPlaceholder: asString(raw.header_content, ''),
        id: asString(raw.id),
        isActive: raw.is_active === false ? false : true,
        layoutType: asString(raw.layout_type, 'html') as DocumentTemplate['layoutType'],
        name: asString(raw.template_name ?? raw.name, 'Document Template'),
    };
}

function toDocumentTypePayload(input: DocumentTypeFormInput) {
    return {
        code: input.code,
        default_status: 'draft',
        description: input.description,
        is_active: input.isActive,
        module_scope: input.moduleScope,
        name: input.name,
        supports_attachments: input.attachmentsEnabled,
        supports_comments: input.commentsEnabled,
        supports_items: true,
        supports_versions: input.versioningEnabled,
        supports_workflow: input.workflowEnabled,
        requires_source: input.moduleScope !== 'shared',
        data: {
            attachments_enabled: input.attachmentsEnabled,
            comments_enabled: input.commentsEnabled,
            description: input.description,
            module_scope: input.moduleScope,
            permissions_enabled: input.permissionsEnabled,
            versioning_enabled: input.versioningEnabled,
            workflow_enabled: input.workflowEnabled,
        },
    };
}

function toDefinitionPayload(input: DocumentDefinitionFormInput) {
    return {
        document_type_id: Number(input.documentTypeId) || 1,
        fields: input.fields.map((field) => ({
            data_type: field.fieldType,
            default_value: field.defaultValue,
            display_order: field.displayOrder,
            field_key: field.code,
            is_required: field.isRequired,
            label: field.label,
            validation_rule: field.validationRule,
        })),
        is_active: input.isActive,
        name: input.name,
        sequence_id: Number(input.sequenceId) || null,
        settings: {
            allowed_statuses: input.allowedStatuses,
            code: input.code,
            description: input.description,
            sequence_id: input.sequenceId,
            source_module: input.sourceModule,
            template_id: input.templateId,
            workflow_id: input.workflowId,
        },
        source_module: input.sourceModule,
        supports_versions: true,
        template_id: Number(input.templateId) || null,
        version: 1,
        workflow_id: Number(input.workflowId) || null,
    };
}

function toTemplatePayload(input: DocumentTemplateFormInput) {
    return {
        body_content: input.bodyPlaceholder,
        document_type_id: Number(input.documentTypeId) || null,
        footer_content: input.footerPlaceholder,
        header_content: input.headerPlaceholder,
        is_active: input.isActive,
        layout_type: input.layoutType,
        template_code: input.code,
        template_name: input.name,
    };
}

export const documentApi = {
    addComment: (documentId: string, input: { comment: string }) =>
        withMockFallback(
            () => httpClient<ApiResponse<DocumentComment>>(`/api/document/documents/${documentId}/comments`, { body: input, method: 'POST' }),
            () => mockResponse({ author: 'Mock User', body: input.comment, id: 'mock-comment', time: 'Just now' }),
        ),
    addRelation: (documentId: string, input: Partial<DocumentRelation>) =>
        withMockFallback(
            () => httpClient<ApiResponse<DocumentRelation>>(`/api/document/documents/${documentId}/relations`, { body: input, method: 'POST' }),
            () => mockResponse({ ...documentRelations[0], ...input, id: 'mock-relation' }),
        ),
    changeDocumentStatus: (documentId: string, status: DocumentStatus, actionName = 'status_action') =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}/status`, { body: { action_name: actionName, status }, method: 'PATCH' });
                return { ...response, data: normalizeDocument(response.data) };
            },
            () => mockResponse({ ...getDocumentById(documentId), status }),
        ),
    createDefinition: (input: DocumentDefinitionFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/definitions', { body: toDefinitionPayload(input), method: 'POST' });
                return { ...response, data: normalizeDefinition(response.data) };
            },
            () => mockResponse({ ...documentDefinitions[0], code: input.code, id: 'mock-definition', isActive: input.isActive, name: input.name }),
        ),
    createDocument: (input: unknown) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/documents', { body: input, method: 'POST' });
                return { ...response, data: normalizeDocument(response.data) };
            },
            () => mockResponse({ ...documentRecords[0], id: 'mock-document' }),
        ),
    createDocumentType: (input: DocumentTypeFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/types', { body: toDocumentTypePayload(input), method: 'POST' });
                return { ...response, data: normalizeDocumentType(response.data) };
            },
            () => mockResponse({ ...documentTypes[0], code: input.code, id: 'mock-document-type', isActive: input.isActive, name: input.name }),
        ),
    createTemplate: (input: DocumentTemplateFormInput): Promise<ApiResponse<DocumentTemplate>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/templates', { body: toTemplatePayload(input), method: 'POST' });
                return { ...response, data: normalizeTemplate(response.data) };
            },
            () => mockResponse({ ...documentTemplates[0], ...input, id: 'mock-template' }),
        ),
    downloadDocument: (documentId: string): Promise<ApiResponse<{ documentId: string; status: string }>> => mockResponse({ documentId, status: 'Backend download endpoint pending' }),
    getDefinition: (definitionId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/definitions/${definitionId}`);
                return { ...response, data: normalizeDefinition(response.data) };
            },
            () => mockResponse(getDefinitionById(definitionId)),
        ),
    getDocument: (documentId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}`);
                return { ...response, data: normalizeDocument(response.data) };
            },
            () => mockResponse(getDocumentById(documentId)),
        ),
    getDocumentType: (typeId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/types/${typeId}`);
                return { ...response, data: normalizeDocumentType(response.data) };
            },
            () => mockResponse(getDocumentTypeById(typeId)),
        ),
    getTemplate: (templateId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/templates/${templateId}`);
                return { ...response, data: normalizeTemplate(response.data) };
            },
            () => mockResponse(getTemplateById(templateId)),
        ),
    getVersion: (versionId: string): Promise<ApiResponse<DocumentVersion>> => mockResponse(documentVersions.find((version) => version.id === versionId) ?? documentVersions[0]),
    listActivities: (_documentId: string): Promise<ApiCollectionResponse<DocumentActivity>> => mockCollectionResponse(documentActivities),
    listAttachments: (documentId: string) =>
        withMockFallback(
            () => httpClient<ApiCollectionResponse<DocumentAttachment>>(`/api/document/documents/${documentId}/attachments`),
            () => mockCollectionResponse(documentAttachments),
        ),
    listDocumentLines: async (documentId: string): Promise<ApiCollectionResponse<DocumentLine>> => {
        try {
            const response = await httpClient<ApiCollectionResponse<DocumentLine> | { data: DocumentLine[] }>(
                `/api/document/documents/${documentId}/lines`,
            );

            return {
                ...response,
                data: (response.data ?? []).map((line: DocumentLine | BackendRecord) => normalizeDocumentLine(line as unknown as BackendRecord)),
            };
        } catch {
            return mockCollectionResponse(documentLines.filter((line) => line.documentId === documentId));
        }
    },
    listComments: (documentId: string) =>
        withMockFallback(
            () => httpClient<ApiCollectionResponse<DocumentComment>>(`/api/document/documents/${documentId}/comments`),
            () => mockCollectionResponse(documentComments),
        ),
    listDefinitions: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/definitions');
                return { ...response, data: response.data.map(normalizeDefinition) };
            },
            () => mockCollectionResponse(documentDefinitions),
        ),
    listDocumentTypes: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/types');
                return { ...response, data: response.data.map(normalizeDocumentType) };
            },
            () => mockCollectionResponse(documentTypes),
        ),
    listDocuments: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/documents');
                return { ...response, data: response.data.map(normalizeDocument) };
            },
            () => mockCollectionResponse(documentRecords),
        ),
    listEvents: (_documentId: string): Promise<ApiCollectionResponse<DocumentEvent>> => mockCollectionResponse(documentEvents),
    listPermissions: (_documentId: string): Promise<ApiCollectionResponse<DocumentPermission>> => mockCollectionResponse(documentPermissions),
    listRelations: (documentId: string) =>
        withMockFallback(
            () => httpClient<ApiCollectionResponse<DocumentRelation>>(`/api/document/documents/${documentId}/relations`),
            () => mockCollectionResponse(documentRelations),
        ),
    listSequences: (): Promise<ApiCollectionResponse<DocumentSequence>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sequence/sequences');
                return { ...response, data: response.data.map((raw): DocumentSequence => ({ code: asString(raw.code ?? raw.document_type, `SEQ-${asString(raw.id)}`), documentType: asString(raw.document_type, 'Document'), id: asString(raw.id), nextNumberPreview: asString(raw.next_number_preview ?? raw.next_number, 'Backend next number'), periodType: asString(raw.period_type, 'yearly') as DocumentSequence['periodType'], prefix: asString(raw.prefix, ''), status: raw.is_active === false ? 'inactive' : 'active' })) };
            },
            () => mockCollectionResponse(documentSequences),
        ),
    listTemplates: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/templates');
                return { ...response, data: response.data.map(normalizeTemplate) };
            },
            () => mockCollectionResponse(documentTemplates),
        ),
    listVersions: (documentId: string) =>
        withMockFallback(
            () => httpClient<ApiCollectionResponse<DocumentVersion>>(`/api/document/documents/${documentId}/versions`),
            () => mockCollectionResponse(documentVersions),
        ),
    listWorkflows: (): Promise<ApiCollectionResponse<DocumentWorkflow>> => mockCollectionResponse(documentWorkflows),
    previewDocument: (input: DocumentPreviewResult['input']): Promise<ApiPreviewResponse<DocumentPreviewResult['input'], DocumentPreviewResult['rendered']>> =>
        mockPreviewResponse(input, mockDocumentPreview(input).rendered, mockDocumentPreview(input).breakdown, mockDocumentPreview(input).warnings),
    previewSequenceNumber: (input: { documentType: string }) =>
        withMockFallback(
            async () => {
                const response = await httpClient<BackendRecord>('/api/sequence/sequences/preview-number', {
                    body: {
                        document_type: input.documentType,
                        period_type: 'yearly',
                    },
                    method: 'POST',
                });
                return {
                    breakdown: [{ label: 'Sequence service', value: 'Backend sequence preview' }],
                    calculated: {
                        documentNumber: asString(response.preview_number ?? response.number ?? response.value, 'Backend number preview'),
                    },
                    errors: [],
                    input,
                    warnings: [],
                };
            },
            () => mockPreviewResponse(input, { documentNumber: documentSequences[0].nextNumberPreview }),
        ),
    removeAttachment: (attachmentId: string): Promise<ApiResponse<{ attachmentId: string }>> => mockResponse({ attachmentId }),
    removeRelation: (relationId: string): Promise<ApiResponse<{ relationId: string }>> => mockResponse({ relationId }),
    renderDocumentPreview: (input: DocumentPreviewResult['input']) => documentApi.previewDocument(input),
    updateDefinition: (definitionId: string, input: DocumentDefinitionFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/definitions/${definitionId}`, { body: toDefinitionPayload(input), method: 'PUT' });
                return { ...response, data: normalizeDefinition(response.data) };
            },
            () => mockResponse({ ...getDefinitionById(definitionId), ...input, id: definitionId }),
        ),
    updateDocumentMetadata: (documentId: string, metadata: DocumentMetadataValue[]): Promise<ApiResponse<{ documentId: string; metadata: DocumentMetadataValue[] }>> => mockResponse({ documentId, metadata }),
    updateDocumentType: (typeId: string, input: DocumentTypeFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/types/${typeId}`, { body: toDocumentTypePayload(input), method: 'PUT' });
                return { ...response, data: normalizeDocumentType(response.data) };
            },
            () => mockResponse({ ...getDocumentTypeById(typeId), ...input, id: typeId }),
        ),
    updatePermissions: (documentId: string, permissions: DocumentPermission[]): Promise<ApiResponse<{ documentId: string; permissions: DocumentPermission[] }>> => mockResponse({ documentId, permissions }),
    updateTemplate: (templateId: string, input: DocumentTemplateFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/templates/${templateId}`, { body: toTemplatePayload(input), method: 'PUT' });
                return { ...response, data: normalizeTemplate(response.data) };
            },
            () => mockResponse({ ...getTemplateById(templateId), ...input, id: templateId }),
        ),
    uploadAttachment: (documentId: string, file: File): Promise<ApiResponse<DocumentAttachment>> => mockResponse({ fileName: file.name, fileSize: `${Math.ceil(file.size / 1024)} KB`, fileType: file.type || 'File', id: 'mock-upload', uploadedAt: 'Just now', uploadedBy: `Document ${documentId}` }),
};

export const documentMockPanels = {
    metadataValues,
    sourceReferences,
};
