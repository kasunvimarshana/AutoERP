import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import { getStoredAccessToken, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import type {
    DocumentActivity,
    DocumentAttachment,
    DocumentComment,
    DocumentCreateInput,
    DocumentDefinition,
    DocumentDefinitionFormInput,
    DocumentEvent,
    DocumentFieldDefinition,
    DocumentLine,
    DocumentMetadataValue,
    DocumentPermission,
    DocumentPreviewInput,
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
} from '../types/document.types';

type BackendRecord = Record<string, unknown>;

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '';

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asBool(value: unknown, fallback = false) {
    return value === null || value === undefined ? fallback : Boolean(value);
}

function asArray(value: unknown): BackendRecord[] {
    return Array.isArray(value) ? value.filter((row): row is BackendRecord => row !== null && typeof row === 'object' && !Array.isArray(row)) : [];
}

function nested(raw: BackendRecord, key: string): BackendRecord {
    const value = raw[key];
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as BackendRecord : {};
}

function normalizeStatus(value: unknown): DocumentStatus {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: DocumentStatus[] = ['draft', 'submitted', 'approved', 'posted', 'finalized', 'cancelled', 'archived', 'active'];
    return allowed.includes(status as DocumentStatus) ? status as DocumentStatus : 'draft';
}

function normalizeDocument(raw: BackendRecord): DocumentRecord {
    const data = nested(raw, 'data');
    return {
        createdAt: asString(raw.created_at ?? raw.document_date),
        data,
        definitionId: asString(raw.document_definition_id ?? data.definition_id),
        documentDate: asString(raw.document_date),
        documentNumber: asString(raw.document_number, `DOC-${asString(raw.id)}`),
        dueDate: asString(raw.due_date),
        grandTotal: asString(raw.grand_total, '0.0000'),
        id: asString(raw.id),
        items: asArray(raw.items).map(normalizeLine),
        notes: asString(raw.notes),
        ownerId: raw.owner_id === null || raw.owner_id === undefined ? undefined : asString(raw.owner_id),
        partyId: raw.party_id === null || raw.party_id === undefined ? undefined : asString(raw.party_id),
        sourceId: raw.source_id === null || raw.source_id === undefined ? undefined : asString(raw.source_id),
        sourceModule: asString(raw.source_module ?? data.source_module, 'core') as DocumentRecord['sourceModule'],
        sourceReference: asString(raw.source_reference ?? data.external_reference ?? data.source_reference),
        sourceType: asString(raw.source_type),
        status: normalizeStatus(raw.status),
        title: asString(raw.title ?? data.title ?? raw.notes, asString(raw.document_number, 'Document')),
        typeCode: asString(raw.document_type_code ?? data.type_code, 'DOC'),
        typeName: asString(raw.document_type_name ?? data.type_name, 'Document'),
        version: asString(raw.version, '1'),
    };
}

function normalizeDocumentType(raw: BackendRecord): DocumentType {
    return {
        attachmentsEnabled: asBool(raw.supports_attachments, true),
        code: asString(raw.code),
        commentsEnabled: asBool(raw.supports_comments, true),
        defaultStatus: normalizeStatus(raw.default_status),
        description: asString(raw.description),
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        moduleScope: asString(raw.module_scope, 'shared') as DocumentType['moduleScope'],
        name: asString(raw.name),
        requiresSource: asBool(raw.requires_source),
        versioningEnabled: asBool(raw.supports_versions, true),
        workflowEnabled: asBool(raw.supports_workflow, true),
    };
}

function normalizeField(raw: BackendRecord, index: number): DocumentFieldDefinition {
    return {
        code: asString(raw.field_key ?? raw.code, `field_${index + 1}`),
        dataType: asString(raw.data_type, 'text') as DocumentFieldDefinition['dataType'],
        defaultValue: asString(raw.default_value),
        displayOrder: Number(raw.display_order ?? index + 1),
        id: asString(raw.id, `field-${index + 1}`),
        isReadonly: asBool(raw.is_readonly),
        isRequired: asBool(raw.is_required),
        label: asString(raw.label ?? raw.field_key, `Field ${index + 1}`),
        sectionKey: asString(raw.section_key),
        validationRule: asString(raw.validation_rule),
    };
}

function normalizeDefinition(rawInput: BackendRecord): DocumentDefinition {
    const raw = nested(rawInput, 'definition');
    const source = Object.keys(raw).length > 0 ? raw : rawInput;
    const fields = asArray(rawInput.fields ?? source.fields).map(normalizeField);
    return {
        code: asString(source.definition_code ?? source.code, `DEF-${asString(source.id)}`),
        defaultStatus: normalizeStatus(source.default_status),
        description: asString(source.description),
        documentTypeCode: asString(source.document_type_code),
        documentTypeId: asString(source.document_type_id),
        documentTypeName: asString(source.document_type_name, 'Document Type'),
        fields,
        id: asString(source.id),
        isActive: asBool(source.is_active, true),
        name: asString(source.name, 'Document Definition'),
        sequenceId: asString(source.sequence_id),
        sourceModule: asString(source.source_module, 'shared') as DocumentDefinition['sourceModule'],
        supportsVersions: asBool(source.supports_versions, true),
        templateId: asString(source.template_id),
        updatedAt: asString(source.updated_at),
        version: asString(source.version, '1'),
        workflowId: asString(source.workflow_id),
    };
}

function normalizeTemplate(raw: BackendRecord): DocumentTemplate {
    return {
        bodyContent: asString(raw.body_content),
        code: asString(raw.template_code ?? raw.code),
        documentTypeId: asString(raw.document_type_id),
        footerContent: asString(raw.footer_content),
        headerContent: asString(raw.header_content),
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        layoutType: asString(raw.layout_type, 'html') as DocumentTemplate['layoutType'],
        name: asString(raw.template_name ?? raw.name),
    };
}

function normalizeLine(raw: BackendRecord): DocumentLine {
    const data = nested(raw, 'data');
    return {
        description: asString(raw.description),
        discountAmount: asString(raw.discount_amount ?? data.discount_amount, '0.0000'),
        displayOrder: Number(raw.display_order ?? raw.sequence ?? 1),
        documentId: asString(raw.document_id),
        id: asString(raw.id),
        itemLabel: asString(raw.item_label ?? data.label ?? raw.item_type),
        lineNo: Number(raw.line_no ?? raw.sequence ?? raw.display_order ?? 1),
        lineTotal: asString(raw.line_total, '0.0000'),
        lineType: asString(raw.line_type ?? raw.item_type, 'line'),
        quantity: asString(raw.quantity ?? data.quantity),
        sourceLineId: raw.source_line_id === null || raw.source_line_id === undefined ? undefined : asString(raw.source_line_id),
        sourceLineType: raw.source_line_type === null || raw.source_line_type === undefined ? undefined : asString(raw.source_line_type),
        taxAmount: asString(raw.tax_amount ?? data.tax_amount, '0.0000'),
        unitPrice: asString(raw.unit_price ?? data.unit_price),
        uomLabel: asString(raw.uom_label ?? data.uom),
    };
}

function normalizeMetadata(raw: BackendRecord): DocumentMetadataValue {
    return {
        code: asString(raw.field_key ?? raw.code),
        label: asString(raw.label ?? raw.field_key),
        value: asString(raw.value ?? raw.value_string ?? raw.value_text),
        valueType: asString(raw.value_type, 'string'),
    };
}

function normalizeAttachment(raw: BackendRecord): DocumentAttachment {
    return {
        fileName: asString(raw.file_name),
        fileSize: asString(raw.file_size),
        fileType: asString(raw.mime_type ?? raw.file_type),
        id: asString(raw.id),
        uploadedAt: asString(raw.created_at ?? raw.uploaded_at),
        uploadedBy: asString(raw.uploaded_by),
    };
}

function normalizeComment(raw: BackendRecord): DocumentComment {
    return {
        author: asString(raw.author_name ?? raw.author_id, 'System'),
        body: asString(raw.comment ?? raw.body),
        id: asString(raw.id),
        time: asString(raw.created_at),
    };
}

function normalizeEvent(raw: BackendRecord): DocumentEvent {
    return {
        actor: asString(raw.performed_by, 'System'),
        eventType: asString(raw.event_type),
        id: asString(raw.id),
        message: asString(raw.message ?? raw.event_type),
        time: asString(raw.created_at),
    };
}

function normalizeActivity(raw: BackendRecord): DocumentActivity {
    return {
        actor: asString(raw.performed_by, 'System'),
        activityType: asString(raw.activity_type),
        description: asString(raw.description),
        id: asString(raw.id),
        time: asString(raw.created_at),
    };
}

function normalizeVersion(raw: BackendRecord): DocumentVersion {
    return {
        createdAt: asString(raw.created_at),
        createdBy: asString(raw.changed_by, 'System'),
        id: asString(raw.id),
        note: asString(raw.change_reason),
        version: asString(raw.version),
    };
}

function normalizePermission(raw: BackendRecord): DocumentPermission {
    return {
        ability: asString(raw.ability),
        id: asString(raw.id),
        principal: asString(raw.principal_identifier),
        principalType: asString(raw.principal_type),
    };
}

function normalizeRelation(raw: BackendRecord): DocumentRelation {
    return {
        id: asString(raw.id),
        relationType: asString(raw.relation_type, 'reference'),
        targetDocumentId: asString(raw.target_document_id),
        targetDocumentNumber: asString(raw.target_document_number ?? raw.target_document_id),
    };
}

function normalizeWorkflow(raw: BackendRecord): DocumentWorkflow {
    const steps = asArray(raw.steps).map((step, index) => ({
        description: asString(step.display_name ?? step.name),
        id: asString(step.id),
        label: asString(step.display_name ?? step.name),
        status: index === 0 ? 'current' as const : 'pending' as const,
    }));
    return {
        documentTypeName: asString(raw.document_type_name),
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        name: asString(raw.name),
        steps,
        usage: asString(raw.document_type_code, 'Document workflow'),
    };
}

function normalizeSequence(raw: BackendRecord): DocumentSequence {
    return {
        code: asString(raw.code ?? raw.sequence_code ?? raw.document_type, `SEQ-${asString(raw.id)}`),
        documentType: asString(raw.document_type ?? raw.context_type ?? raw.name, 'Document'),
        id: asString(raw.id),
        nextNumberPreview: asString(raw.next_number_preview ?? raw.preview_number ?? raw.next_number, ''),
        periodType: asString(raw.period_type, 'yearly') as DocumentSequence['periodType'],
        prefix: asString(raw.prefix),
        status: asBool(raw.is_active, true) ? 'active' : 'inactive',
    };
}

function typePayload(input: DocumentTypeFormInput) {
    return {
        code: input.code,
        default_status: 'draft',
        description: input.description,
        is_active: input.isActive,
        module_scope: input.moduleScope,
        name: input.name,
        requires_source: input.requiresSource,
        supports_attachments: input.attachmentsEnabled,
        supports_comments: input.commentsEnabled,
        supports_items: true,
        supports_versions: input.versioningEnabled,
        supports_workflow: input.workflowEnabled,
    };
}

function definitionPayload(input: DocumentDefinitionFormInput) {
    return {
        default_status: input.defaultStatus,
        definition_code: input.code,
        description: input.description,
        document_type_id: Number(input.documentTypeId),
        fields: input.fields.map((field) => ({
            data_type: field.dataType,
            default_value: field.defaultValue,
            display_order: field.displayOrder,
            field_key: field.code,
            is_readonly: field.isReadonly,
            is_required: field.isRequired,
            label: field.label,
            section_key: field.sectionKey || null,
            validation_rule: field.validationRule,
        })),
        is_active: input.isActive,
        name: input.name,
        sequence_id: Number(input.sequenceId) || null,
        source_module: input.sourceModule,
        supports_versions: input.supportsVersions,
        template_id: Number(input.templateId) || null,
        version: 1,
        workflow_id: Number(input.workflowId) || null,
    };
}

function templatePayload(input: DocumentTemplateFormInput) {
    return {
        body_content: input.bodyContent,
        document_type_id: Number(input.documentTypeId) || null,
        footer_content: input.footerContent,
        header_content: input.headerContent,
        is_active: input.isActive,
        layout_type: input.layoutType,
        template_code: input.code,
        template_name: input.name,
    };
}

function documentPayload(input: DocumentCreateInput) {
    return {
        data: input.data ?? {},
        document_date: input.documentDate,
        document_definition_id: Number(input.documentDefinitionId) || null,
        document_type_id: Number(input.documentTypeId),
        due_date: input.dueDate || null,
        items: input.items.map((item) => ({
            data: item.data ?? {},
            description: item.description || null,
            item_type: item.itemType,
            line_total: item.lineTotal,
        })),
        notes: input.notes || null,
        owner_id: Number(input.ownerId) || null,
        party_id: Number(input.partyId) || null,
        source_id: Number(input.sourceId) || null,
        source_module: input.sourceModule || null,
        source_reference: input.sourceReference || null,
        source_type: input.sourceType || null,
        title: input.title || null,
    };
}

function normalizedCollection<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord) => T): ApiCollectionResponse<T> {
    return { ...response, data: response.data.map(mapper) };
}

export const documentApi = {
    addComment: async (documentId: string, input: { comment: string }) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}/comments`, { body: input, method: 'POST' });
        return { ...response, data: normalizeComment(response.data) };
    },
    addRelation: async (documentId: string, input: { relationType?: string; targetDocumentId: string }) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}/relations`, {
            body: { relation_type: input.relationType ?? 'reference', target_document_id: Number(input.targetDocumentId) },
            method: 'POST',
        });
        return { ...response, data: normalizeRelation(response.data) };
    },
    changeDocumentStatus: async (documentId: string, status: DocumentStatus, actionName = 'status_action') => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}/status`, { body: { action_name: actionName, status }, method: 'PATCH' });
        return { ...response, data: normalizeDocument(response.data) };
    },
    createDefinition: async (input: DocumentDefinitionFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/definitions', { body: definitionPayload(input), method: 'POST' });
        return { ...response, data: normalizeDefinition(response.data) };
    },
    createDocumentType: async (input: DocumentTypeFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/types', { body: typePayload(input), method: 'POST' });
        return { ...response, data: normalizeDocumentType(response.data) };
    },
    createDocument: async (input: DocumentCreateInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/documents', { body: documentPayload(input), method: 'POST' });
        return { ...response, data: normalizeDocument(response.data) };
    },
    createTemplate: async (input: DocumentTemplateFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/document/templates', { body: templatePayload(input), method: 'POST' });
        return { ...response, data: normalizeTemplate(response.data) };
    },
    getDefinition: async (definitionId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/definitions/${definitionId}`);
        return { ...response, data: normalizeDefinition(response.data) };
    },
    getDocument: async (documentId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}`);
        return { ...response, data: normalizeDocument(response.data) };
    },
    getDocumentType: async (typeId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/types/${typeId}`);
        return { ...response, data: normalizeDocumentType(response.data) };
    },
    getTemplate: async (templateId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/templates/${templateId}`);
        return { ...response, data: normalizeTemplate(response.data) };
    },
    getVersion: async (documentId: string, versionId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/documents/${documentId}/versions/${versionId}`);
        return { ...response, data: normalizeVersion(response.data) };
    },
    listActivities: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/activities`), normalizeActivity),
    listAttachments: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/attachments`), normalizeAttachment),
    listComments: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/comments`), normalizeComment),
    listDefinitions: async () => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/definitions'), normalizeDefinition),
    listDocumentLines: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/lines`), normalizeLine),
    listDocumentTypes: async () => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/types'), normalizeDocumentType),
    listDocuments: async (query: Record<string, string | number | boolean | undefined> = {}) => normalizedCollection(
        await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/documents', {
            query: {
                page: query.page,
                per_page: query.per_page ?? query.perPage,
                search: query.search,
                source_id: query.source_id,
                source_module: query.source_module,
                source_type: query.source_type,
                status: query.status,
            },
        }),
        normalizeDocument,
    ),
    listEvents: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/events`), normalizeEvent),
    listPermissions: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/permissions`), normalizePermission),
    listRelations: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/relations`), normalizeRelation),
    listSequences: async () => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sequence/sequences'), normalizeSequence),
    listTemplates: async () => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/templates'), normalizeTemplate),
    listVersions: async (documentId: string) => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/document/documents/${documentId}/versions`), normalizeVersion),
    listWorkflows: async () => normalizedCollection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/document/workflows'), normalizeWorkflow),
    previewDocument: async (input: DocumentPreviewInput): Promise<ApiPreviewResponse<DocumentPreviewInput, DocumentPreviewRendered>> => {
        const response = await httpClient<BackendRecord>('/api/document/preview', {
            body: {
                definition_id: Number(input.definitionId) || null,
                document_number: input.documentNumber,
                metadata: input.metadata,
                source_id: Number(input.sourceId) || null,
                source_module: input.sourceModule,
                source_reference: input.sourceReference,
            },
            method: 'POST',
        });
        const rendered = nested(response, 'rendered');
        return {
            breakdown: Array.isArray(response.breakdown) ? response.breakdown as Array<Record<string, unknown>> : [],
            calculated: {
                definitionId: asString(rendered.definition_id),
                definitionName: asString(rendered.definition_name),
                documentNumber: asString(rendered.document_number),
                html: asString(rendered.html),
                templateId: asString(rendered.template_id),
                templateName: asString(rendered.template_name),
                text: asString(rendered.text),
            },
            errors: Array.isArray(response.errors) ? response.errors.map(String) : [],
            input,
            warnings: Array.isArray(response.warnings) ? response.warnings.map(String) : [],
        };
    },
    previewTemplate: async (input: { body: string; documentNumber: string; templateId?: string; title: string }) => {
        const response = await httpClient<BackendRecord>('/api/document/templates/preview', {
            body: {
                body: input.body,
                document_number: input.documentNumber,
                template_id: Number(input.templateId) || null,
                title: input.title,
            },
            method: 'POST',
        });
        return response;
    },
    removeAttachment: async (documentId: string, attachmentId: string) => {
        await httpClient<void>(`/api/document/documents/${documentId}/attachments/${attachmentId}`, { method: 'DELETE' });
        return { data: { attachmentId } };
    },
    removeRelation: async (documentId: string, relationId: string) => {
        await httpClient<void>(`/api/document/documents/${documentId}/relations/${relationId}`, { method: 'DELETE' });
        return { data: { relationId } };
    },
    updateDefinition: async (definitionId: string, input: DocumentDefinitionFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/definitions/${definitionId}`, { body: definitionPayload(input), method: 'PUT' });
        return { ...response, data: normalizeDefinition(response.data) };
    },
    updateDocumentMetadata: async (documentId: string, metadata: DocumentMetadataValue[]) => {
        const response = await httpClient<ApiResponse<BackendRecord[]>>(`/api/document/documents/${documentId}/metadata`, {
            body: { metadata: metadata.map((entry) => ({ field_key: entry.code, value: entry.value, value_type: entry.valueType })) },
            method: 'PATCH',
        });
        return { ...response, data: response.data.map((row) => normalizeMetadata(row)) };
    },
    updateDocumentType: async (typeId: string, input: DocumentTypeFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/types/${typeId}`, { body: typePayload(input), method: 'PUT' });
        return { ...response, data: normalizeDocumentType(response.data) };
    },
    updatePermissions: async (documentId: string, permissions: DocumentPermission[]) => {
        const response = await httpClient<ApiResponse<BackendRecord[]>>(`/api/document/documents/${documentId}/permissions`, {
            body: { permissions: permissions.map((permission) => ({ ability: permission.ability, principal_identifier: permission.principal, principal_type: permission.principalType })) },
            method: 'PUT',
        });
        return { ...response, data: response.data.map(normalizePermission) };
    },
    updateTemplate: async (templateId: string, input: DocumentTemplateFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/document/templates/${templateId}`, { body: templatePayload(input), method: 'PUT' });
        return { ...response, data: normalizeTemplate(response.data) };
    },
    uploadAttachment: async (documentId: string, file: File): Promise<ApiResponse<DocumentAttachment>> => {
        const formData = new FormData();
        formData.append('file', file);
        const url = new URL(`${API_BASE_URL}/api/document/documents/${documentId}/attachments`, window.location.origin);
        const token = getStoredAccessToken();
        const tenantId = getStoredTenantId();
        const organizationUnitId = getStoredOrganizationUnitId();
        const response = await fetch(url.toString(), {
            body: formData,
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}),
                ...(organizationUnitId ? { 'X-Organization-Unit-ID': organizationUnitId } : {}),
            },
            method: 'POST',
        });
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(asString(payload.message, 'Unable to upload attachment.'));
        }
        return { ...payload, data: normalizeAttachment(payload.data) };
    },
};
