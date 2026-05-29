import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { suppliers } from '../mock/supplierMock';
import type { SupplierFormInput } from '../types/supplier.types';

export const supplierApi = {
    addresses: {
        create: (supplierId: string, input: unknown) => mockResponse({ input, supplierId }),
        list: (supplierId: string) => mockCollectionResponse(suppliers.map((supplier) => ({ ...supplier, supplierId }))),
    },
    bankAccounts: {
        create: (supplierId: string, input: unknown) => mockResponse({ input, supplierId }),
        list: (supplierId: string) => mockCollectionResponse(suppliers.map((supplier) => ({ ...supplier, supplierId }))),
    },
    contacts: {
        create: (supplierId: string, input: unknown) => mockResponse({ input, supplierId }),
        list: (supplierId: string) => mockCollectionResponse(suppliers.map((supplier) => ({ ...supplier, supplierId }))),
    },
    financeDefaults: {
        preview: (supplierId: string) => mockPreviewResponse({ supplierId }, { note: 'Backend AP/default accounts preview placeholder' }),
    },
    suppliers: {
        create: (input: SupplierFormInput) => mockResponse({ id: 'mock-supplier', ...input }),
        get: (id: string) => mockResponse(suppliers.find((supplier) => supplier.id === id) ?? suppliers[0]),
        list: () => mockCollectionResponse(suppliers),
        update: (id: string, input: SupplierFormInput) => mockResponse({ id, ...input }),
    },
    userAccess: {
        link: (supplierId: string, input: unknown) => mockResponse({ input, supplierId }),
        unlink: (supplierId: string) => mockResponse({ action: 'unlink-requested', supplierId }),
    },
};
