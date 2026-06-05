import type {
    Customer,
    CustomerAddress,
    CustomerContact,
    CustomerCreditProfile,
    CustomerFinanceDefaults,
    CustomerTaxProfile,
    CustomerUserAccess,
    CustomerVehicle,
} from '../types/customer.types';

export const customers: Customer[] = [
    {
        code: 'CUS-001',
        contactPerson: 'Amila Perera',
        createdAt: '2026-05-12',
        email: 'accounts@northline.example',
        id: 'cus-001',
        industry: 'Fleet operations',
        name: 'Northline Logistics',
        phone: '+94 77 100 2001',
        status: 'active',
        taxNumber: 'VAT-102938',
        userAccessStatus: 'none',
    },
    {
        code: 'CUS-002',
        contactPerson: 'Maya Silva',
        createdAt: '2026-04-21',
        email: 'finance@metrohire.example',
        id: 'cus-002',
        industry: 'Vehicle rental',
        name: 'Metro Hire Services',
        phone: '+94 77 100 2002',
        status: 'active',
        taxNumber: 'VAT-837221',
        userAccessStatus: 'linked',
    },
    {
        code: 'CUS-003',
        contactPerson: 'Ravi Fernando',
        createdAt: '2026-05-25',
        email: 'owner@johnworkshop.example',
        id: 'cus-003',
        industry: 'Workshop',
        name: 'John Workshop',
        phone: '+94 77 100 2003',
        status: 'pending',
        userAccessStatus: 'none',
    },
];

export const customerContacts: CustomerContact[] = [
    { customerId: 'cus-001', email: 'amila@northline.example', id: 'con-001', isPrimary: true, name: 'Amila Perera', phone: '+94 77 220 1001', role: 'Fleet Manager' },
    { customerId: 'cus-001', email: 'billing@northline.example', id: 'con-002', isPrimary: false, name: 'Nethmi Jayasinghe', phone: '+94 77 220 1002', role: 'Accounts' },
    { customerId: 'cus-002', email: 'maya@metrohire.example', id: 'con-003', isPrimary: true, name: 'Maya Silva', phone: '+94 77 220 1003', role: 'Operations Lead' },
];

export const customerAddresses: CustomerAddress[] = [
    { city: 'Colombo', country: 'Sri Lanka', customerId: 'cus-001', id: 'addr-001', isPrimary: true, line1: 'No. 24, Warehouse Road', line2: 'Orugodawatta', postalCode: '10600', type: 'billing' },
    { city: 'Colombo', country: 'Sri Lanka', customerId: 'cus-001', id: 'addr-002', isPrimary: false, line1: 'Northline Service Yard', postalCode: '10600', type: 'service' },
    { city: 'Kandy', country: 'Sri Lanka', customerId: 'cus-002', id: 'addr-003', isPrimary: true, line1: 'Metro Hire Depot', postalCode: '20000', type: 'billing' },
];

export const customerVehicles: CustomerVehicle[] = [
    { customerId: 'cus-001', id: 'veh-001', make: 'Toyota', model: 'HiAce', plateNumber: 'WP CAD-4521', status: 'Active', vin: 'VIN-MOCK-4521', year: '2021' },
    { customerId: 'cus-001', id: 'veh-002', make: 'Mitsubishi', model: 'L200', plateNumber: 'CP CAB-9410', status: 'Active', year: '2020' },
    { customerId: 'cus-002', id: 'veh-003', make: 'Nissan', model: 'Caravan', plateNumber: 'WP KA-7781', status: 'Active', year: '2019' },
];

export const customerTaxProfiles: CustomerTaxProfile[] = [
    { customerId: 'cus-001', taxGroup: 'Local VAT Customer', taxRegistrationNumber: 'VAT-102938', taxStatus: 'registered' },
    { customerId: 'cus-002', taxGroup: 'Local VAT Customer', taxRegistrationNumber: 'VAT-837221', taxStatus: 'registered' },
    { customerId: 'cus-003', exemptionReason: 'Awaiting registration documents', taxGroup: 'Standard Customer', taxStatus: 'unregistered' },
];

export const customerCreditProfiles: CustomerCreditProfile[] = [
    {
        agingSummary: 'Backend aging preview: 0-30 days bucket visible after integration',
        backendPreviewStatus: 'Mock backend credit preview',
        creditLimit: 'LKR 2,500,000',
        creditStatus: 'Within backend-approved limit',
        customerId: 'cus-001',
        outstandingBalance: 'Backend-owned outstanding balance',
        paymentTerms: '30 days',
    },
    {
        agingSummary: 'Backend aging preview: rental receivables included later',
        backendPreviewStatus: 'Mock backend credit preview',
        creditLimit: 'LKR 1,000,000',
        creditStatus: 'Requires review before new credit sale',
        customerId: 'cus-002',
        outstandingBalance: 'Backend-owned outstanding balance',
        paymentTerms: '15 days',
    },
];

export const customerFinanceDefaults: CustomerFinanceDefaults[] = [
    { arAccount: 'Accounts Receivable - Trade', costCenter: 'Fleet Service', currency: 'LKR', customerId: 'cus-001', paymentTerm: '30 days', revenueAccount: 'Service Revenue' },
    { arAccount: 'Accounts Receivable - Rental', costCenter: 'Rental Operations', currency: 'LKR', customerId: 'cus-002', paymentTerm: '15 days', revenueAccount: 'Rental Revenue' },
];

export const customerUserAccess: CustomerUserAccess[] = [
    { customerId: 'cus-002', email: 'maya@metrohire.example', id: 'access-001', lastLogin: '2026-05-28 15:20', status: 'active', userName: 'maya.metro' },
];

export function getCustomerById(id?: string) {
    return customers.find((customer) => customer.id === id) ?? customers[0];
}
