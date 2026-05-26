import { apiClient, unwrapPaginated } from '../../api/client';
import type { ApiPaginatedEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type { TaxGroupFilters, TaxGroupRecord, TaxRateFilters, TaxRateRecord, TaxRuleFilters, TaxRuleRecord } from './types';

export const taxApi = {
    listTaxGroups(filters: TaxGroupFilters): Promise<PaginatedResult<TaxGroupRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<TaxGroupRecord>>('/tax/groups', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    listTaxRates(taxGroupId: number, filters: TaxRateFilters): Promise<PaginatedResult<TaxRateRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<TaxRateRecord>>('/tax-rates', { query: toQuery({ ...filters, tax_group_id: taxGroupId }) })
            .then((payload) => unwrapPaginated(payload));
    },
    listTaxRules(taxGroupId: number, filters: TaxRuleFilters): Promise<PaginatedResult<TaxRuleRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<TaxRuleRecord>>('/tax-rules', { query: toQuery({ ...filters, tax_group_id: taxGroupId }) })
            .then((payload) => unwrapPaginated(payload));
    },
};
