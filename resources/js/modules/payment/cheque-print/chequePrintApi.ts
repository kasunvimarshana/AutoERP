/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    ChequePrintLog,
    ChequePrintPreview,
    ChequeTemplate,
    ChequeTemplatePayload,
} from './chequePrintTypes';

const templatesPath = `${endpoints.payments}/cheque-templates`;

export interface ListChequeTemplateOptions {
    activeOnly?: boolean;
    all?: boolean;
    search?: string;
    per_page?: number;
    page?: number;
}

export const listChequeTemplates = (
    optionsOrActiveOnly: boolean | ListChequeTemplateOptions = false,
    signal?: AbortSignal,
) => {
    const params: Record<string, unknown> = {};

    if (typeof optionsOrActiveOnly === 'boolean') {
        if (optionsOrActiveOnly) params.is_active = true;
    } else {
        if (optionsOrActiveOnly.activeOnly) params.is_active = true;
        if (optionsOrActiveOnly.all) params.all = true;
        if (optionsOrActiveOnly.search) params.search = optionsOrActiveOnly.search;
        if (optionsOrActiveOnly.per_page) params.per_page = optionsOrActiveOnly.per_page;
        if (optionsOrActiveOnly.page) params.page = optionsOrActiveOnly.page;
    }

    return apiClient.get<ApiCollection<ChequeTemplate>>(templatesPath, {
        params: Object.keys(params).length > 0 ? params : undefined,
        signal,
    }).then((response) => response.data);
};

export const getChequeTemplate = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<ChequeTemplate>>(`${templatesPath}/${id}`, { signal })
        .then((response) => response.data.data);

export const createChequeTemplate = (payload: ChequeTemplatePayload) =>
    apiClient.post<ApiResource<ChequeTemplate>>(templatesPath, payload)
        .then((response) => response.data.data);

export const updateChequeTemplate = (id: number, payload: Partial<ChequeTemplatePayload>) =>
    apiClient.put<ApiResource<ChequeTemplate>>(`${templatesPath}/${id}`, payload)
        .then((response) => response.data.data);

export const deleteChequeTemplate = (id: number) => apiClient.delete(`${templatesPath}/${id}`);

export const previewCheque = (paymentId: number, lineId: number, templateId?: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<ChequePrintPreview & { line?: ChequePrintPreview['payment'] }>>(`${endpoints.payments}/${paymentId}/lines/${lineId}/cheque-print/preview`, {
        params: templateId ? { cheque_template_id: templateId } : undefined,
        signal,
    }).then((response) => {
        const data = response.data.data;
        return data.line ? { ...data, payment: { ...data.payment, ...data.line } } : data;
    });

export const getChequeExportUrl = (
    paymentId: number,
    lineId: number,
    format: 'html' | 'print' | 'pdf',
    templateId?: number,
): string => {
    const base = `${endpoints.payments}/${paymentId}/lines/${lineId}/cheque-print/export/${format}`;
    return templateId ? `${base}?cheque_template_id=${templateId}` : base;
};

export const markChequePrinted = (paymentId: number, lineId: number, templateId: number, notes?: string) =>
    apiClient.post<ApiResource<ChequePrintLog>>(`${endpoints.payments}/${paymentId}/lines/${lineId}/cheque-print`, {
        cheque_template_id: templateId,
        notes: notes || undefined,
    }).then((response) => response.data.data);
