import type {
    Supplier,
    SupplierAddress,
    SupplierAuditEntry,
    SupplierBankAccount,
    SupplierCategory,
    SupplierContact,
    SupplierFinanceDefaults,
    SupplierTaxProfile,
    SupplierUserAccess,
} from '../types/supplier.types';

export const supplierCategories: SupplierCategory[] = [
    { id: 'parts', name: 'Parts Supplier' },
    { id: 'service', name: 'External Service Provider' },
    { id: 'fleet', name: 'Fleet Provider' },
];

export const suppliers: Supplier[] = [
    {
        category: 'Parts Supplier',
        code: 'SUP-001',
        defaultCurrency: 'LKR',
        displayName: 'Auto Parts Lanka',
        email: 'accounts@autopartslanka.example',
        id: 'sup-001',
        legalName: 'Auto Parts Lanka Pvt Ltd',
        mobile: '+94 77 220 1100',
        name: 'Auto Parts Lanka',
        phone: '+94 11 220 1100',
        registrationNumber: 'PV-114500',
        status: 'active',
        supplierType: 'stock',
        taxNumber: 'TIN-2011220',
        updatedAt: '2026-05-27',
        userAccessStatus: 'none',
        vatNumber: 'VAT-2011220',
        website: 'https://autopartslanka.example',
    },
    {
        category: 'External Service Provider',
        code: 'SUP-002',
        defaultCurrency: 'LKR',
        displayName: 'Wheel Align Pro',
        email: 'ops@wheelalignpro.example',
        id: 'sup-002',
        legalName: 'Wheel Align Pro Services',
        name: 'Wheel Align Pro',
        phone: '+94 11 540 9870',
        registrationNumber: 'BR-88421',
        status: 'active',
        supplierType: 'service',
        taxNumber: 'TIN-5409870',
        updatedAt: '2026-05-25',
        userAccessStatus: 'linked',
        vatNumber: 'VAT-5409870',
    },
    {
        category: 'Fleet Provider',
        code: 'SUP-003',
        defaultCurrency: 'USD',
        displayName: 'Fleet Supplies International',
        email: 'finance@fleetsupplies.example',
        id: 'sup-003',
        legalName: 'Fleet Supplies International LLC',
        name: 'Fleet Supplies International',
        phone: '+1 555 440 9911',
        registrationNumber: 'FSI-99021',
        status: 'inactive',
        supplierType: 'general',
        updatedAt: '2026-05-20',
        userAccessStatus: 'none',
    },
    {
        category: 'Parts Supplier',
        code: 'SUP-004',
        defaultCurrency: 'LKR',
        displayName: 'Blocked Diesel Parts',
        email: 'accounts@blockeddiesel.example',
        id: 'sup-004',
        name: 'Blocked Diesel Parts',
        phone: '+94 11 333 7000',
        registrationNumber: 'BR-77881',
        status: 'blocked',
        supplierType: 'stock',
        taxNumber: 'TIN-3337000',
        updatedAt: '2026-05-18',
        userAccessStatus: 'linked',
    },
];

export const supplierContacts: SupplierContact[] = [
    { designation: 'Accounts Lead', email: 'accounts@autopartslanka.example', id: 'sup-con-001', isPrimary: true, name: 'Nadeeka Perera', phone: '+94 77 101 2020', supplierId: 'sup-001' },
    { designation: 'Procurement Desk', email: 'parts@autopartslanka.example', id: 'sup-con-002', isPrimary: false, name: 'Parts Desk', phone: '+94 77 101 2030', supplierId: 'sup-001' },
    { designation: 'Operations Manager', email: 'ops@wheelalignpro.example', id: 'sup-con-003', isPrimary: true, name: 'Sajith Fernando', phone: '+94 77 540 9870', supplierId: 'sup-002' },
];

export const supplierAddresses: SupplierAddress[] = [
    { city: 'Colombo', country: 'Sri Lanka', id: 'sup-addr-001', isDefault: true, line1: 'No 41, Baseline Road', postalCode: '01000', supplierId: 'sup-001', type: 'registered' },
    { city: 'Kelaniya', country: 'Sri Lanka', id: 'sup-addr-002', isDefault: false, line1: 'Warehouse 3, Industrial Estate', postalCode: '11600', supplierId: 'sup-001', type: 'shipping' },
    { city: 'Nugegoda', country: 'Sri Lanka', id: 'sup-addr-003', isDefault: true, line1: 'Service Lane 8', postalCode: '10250', supplierId: 'sup-002', type: 'billing' },
];

export const supplierBankAccounts: SupplierBankAccount[] = [
    { accountName: 'Auto Parts Lanka Pvt Ltd', accountNumber: '001-450-8831', bankName: 'Commercial Bank', branchName: 'Colombo 03', currency: 'LKR', id: 'sup-bank-001', isActive: true, isPrimary: true, supplierId: 'sup-001' },
    { accountName: 'Wheel Align Pro Services', accountNumber: '778-998-4401', bankName: 'Sampath Bank', branchName: 'Nugegoda', currency: 'LKR', id: 'sup-bank-002', isActive: true, isPrimary: true, supplierId: 'sup-002' },
];

export const supplierTaxProfiles: SupplierTaxProfile[] = [
    { isTaxExempt: false, supplierId: 'sup-001', taxIdentifier: 'TIN-2011220', taxType: 'VAT Registered', vatIdentifier: 'VAT-2011220', withholdingRate: 'Backend-owned withholding preview' },
    { isTaxExempt: false, supplierId: 'sup-002', taxIdentifier: 'TIN-5409870', taxType: 'Service Provider', vatIdentifier: 'VAT-5409870', withholdingRate: 'Backend-owned withholding preview' },
];

export const supplierFinanceDefaults: SupplierFinanceDefaults[] = [
    { creditLimit: 'Backend-owned supplier limit', defaultCurrency: 'LKR', expenseAccount: 'Repairs and parts expense', payableAccount: 'Trade payables', paymentTerm: '30 days', supplierId: 'sup-001' },
    { creditLimit: 'Backend-owned supplier limit', defaultCurrency: 'LKR', expenseAccount: 'External service expense', payableAccount: 'Service provider payables', paymentTerm: '15 days', supplierId: 'sup-002' },
];

export const supplierUserAccess: SupplierUserAccess[] = [
    { email: 'portal@wheelalignpro.example', id: 'sup-user-001', invitedAt: '2026-05-01', isPrimary: true, lastLogin: '2026-05-24', status: 'active', supplierId: 'sup-002', userName: 'Wheel Align Portal' },
    { email: 'blocked@blockeddiesel.example', id: 'sup-user-002', invitedAt: '2026-04-11', isPrimary: true, lastLogin: '', status: 'deactivated', supplierId: 'sup-004', userName: 'Blocked Diesel Portal' },
];

export const supplierAuditEntries: SupplierAuditEntry[] = [
    { actor: 'Operations', description: 'Supplier profile reviewed for active source contexts.', id: 'sup-audit-001', time: 'Today 09:40' },
    { actor: 'System', description: 'Finance defaults preview requested from backend placeholder.', id: 'sup-audit-002', time: 'Today 10:05' },
];

export function getSupplierById(id: string): Supplier {
    return suppliers.find((supplier) => supplier.id === id) ?? suppliers[0];
}
