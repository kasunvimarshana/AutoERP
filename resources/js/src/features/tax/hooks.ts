import { useQuery } from '@tanstack/react-query';
import { taxApi } from './api';
import type { TaxGroupFilters, TaxRateFilters, TaxRuleFilters } from './types';

const taxKeys = {
    all: ['tax'] as const,
    groups: () => [...taxKeys.all, 'groups'] as const,
    groupList: (filters: TaxGroupFilters) => [...taxKeys.groups(), filters] as const,
    rates: (taxGroupId: number, filters: TaxRateFilters) => [...taxKeys.all, 'rates', taxGroupId, filters] as const,
    rules: (taxGroupId: number, filters: TaxRuleFilters) => [...taxKeys.all, 'rules', taxGroupId, filters] as const,
};

export function useTaxGroups(filters: TaxGroupFilters) {
    return useQuery({
        queryKey: taxKeys.groupList(filters),
        queryFn: () => taxApi.listTaxGroups(filters),
    });
}

export function useTaxRates(taxGroupId: number, filters: TaxRateFilters, enabled = true) {
    return useQuery({
        queryKey: taxKeys.rates(taxGroupId, filters),
        queryFn: () => taxApi.listTaxRates(taxGroupId, filters),
        enabled,
    });
}

export function useTaxRules(taxGroupId: number, filters: TaxRuleFilters, enabled = true) {
    return useQuery({
        queryKey: taxKeys.rules(taxGroupId, filters),
        queryFn: () => taxApi.listTaxRules(taxGroupId, filters),
        enabled,
    });
}
