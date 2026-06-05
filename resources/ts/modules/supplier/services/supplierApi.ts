import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { PartyAddress, PartyApi, PartyDetail, PartyInput, PartyListItem } from '../../../shared/party/party.types';

type SupplierRecord = {
    address?: {
        address_line_1: string;
        address_line_2?: string | null;
        city: string;
        country_name?: string | null;
        label?: string | null;
        postal_code: string;
        state_province?: string | null;
    } | null;
    available_credit?: string | null;
    created_at: string;
    credit_limit: string;
    current_credit_balance?: string | null;
    display_name?: string | null;
    email?: string | null;
    id: number;
    mobile?: string | null;
    name: string;
    notes?: string | null;
    organization_unit_id?: number | null;
    payment_terms_days: number;
    phone?: string | null;
    row_version?: number;
    status: 'active' | 'inactive';
    supplier_code: string;
    tax_number?: string | null;
    tenant_id?: number;
    updated_at: string;
    vat_number?: string | null;
};

function address(record: SupplierRecord['address']): PartyAddress | null {
    return record
        ? {
              addressLine1: record.address_line_1,
              addressLine2: record.address_line_2 ?? undefined,
              city: record.city,
              countryName: record.country_name ?? undefined,
              label: record.label ?? undefined,
              postalCode: record.postal_code,
              stateProvince: record.state_province ?? undefined,
          }
        : null;
}

function listItem(record: SupplierRecord): PartyListItem {
    return {
        code: record.supplier_code,
        createdAt: record.created_at,
        creditLimit: record.credit_limit,
        displayName: record.display_name ?? undefined,
        email: record.email ?? undefined,
        id: record.id,
        mobile: record.mobile ?? undefined,
        name: record.name,
        organizationUnitId: record.organization_unit_id ?? undefined,
        paymentTermsDays: record.payment_terms_days,
        phone: record.phone ?? undefined,
        status: record.status,
        updatedAt: record.updated_at,
    };
}

function detail(record: SupplierRecord): PartyDetail {
    return {
        ...listItem(record),
        address: address(record.address),
        availableCredit: record.available_credit ?? null,
        currentCreditBalance: record.current_credit_balance ?? null,
        notes: record.notes ?? undefined,
        rowVersion: record.row_version ?? 1,
        taxNumber: record.tax_number ?? undefined,
        tenantId: record.tenant_id ?? 0,
        vatNumber: record.vat_number ?? undefined,
    };
}

function payload(input: PartyInput) {
    return {
        address: input.address
            ? {
                  address_line_1: input.address.addressLine1,
                  address_line_2: input.address.addressLine2,
                  city: input.address.city,
                  country_name: input.address.countryName,
                  label: input.address.label,
                  postal_code: input.address.postalCode,
                  state_province: input.address.stateProvince,
              }
            : null,
        credit_limit: input.creditLimit,
        display_name: input.displayName,
        email: input.email,
        mobile: input.mobile,
        name: input.name,
        notes: input.notes,
        organization_unit_id: input.organizationUnitId,
        payment_terms_days: input.paymentTermsDays,
        phone: input.phone,
        status: input.status,
        supplier_code: input.code,
        tax_number: input.taxNumber,
        vat_number: input.vatNumber,
    };
}

export const supplierApi: PartyApi = {
    async create(input) {
        const response = await httpClient<ApiResponse<SupplierRecord>>('/api/supplier/suppliers', {
            body: payload(input),
            method: 'POST',
        });

        return detail(response.data);
    },
    async get(id) {
        const response = await httpClient<ApiResponse<SupplierRecord>>(`/api/supplier/suppliers/${id}`);

        return detail(response.data);
    },
    async list(query) {
        const response = await httpClient<ApiCollectionResponse<SupplierRecord>>('/api/supplier/suppliers', {
            query: {
                page: query.page,
                per_page: query.perPage,
                search: query.search,
                status: query.status,
            },
        });

        return {
            items: response.data.map(listItem),
            meta: {
                currentPage: response.meta?.current_page ?? query.page,
                lastPage: response.meta?.last_page ?? 1,
                perPage: response.meta?.per_page ?? query.perPage,
                total: response.meta?.total ?? response.data.length,
            },
        };
    },
    async remove(id) {
        await httpClient<void>(`/api/supplier/suppliers/${id}`, { method: 'DELETE' });
    },
    async update(id, input) {
        const response = await httpClient<ApiResponse<SupplierRecord>>(`/api/supplier/suppliers/${id}`, {
            body: payload(input),
            method: 'PUT',
        });

        return detail(response.data);
    },
};
