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

export const listChequeTemplates = (activeOnly = false, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ChequeTemplate>>(templatesPath, {
        params: activeOnly ? { is_active: true } : undefined,
        signal,
    }).then((response) => response.data);

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

export const markChequePrinted = (paymentId: number, lineId: number, templateId: number, notes?: string) =>
    apiClient.post<ApiResource<ChequePrintLog>>(`${endpoints.payments}/${paymentId}/lines/${lineId}/cheque-print`, {
        cheque_template_id: templateId,
        notes: notes || undefined,
    }).then((response) => response.data.data);