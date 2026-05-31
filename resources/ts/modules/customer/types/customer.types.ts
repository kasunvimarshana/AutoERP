export type CustomerStatus = 'active' | 'blocked' | 'inactive' | 'pending';

export type Customer = {
    code: string;
    contactPerson: string;
    createdAt: string;
    email: string;
    id: string;
    industry: string;
    name: string;
    notes?: string;
    phone: string;
    status: CustomerStatus;
    taxNumber?: string;
    userAccessStatus: 'linked' | 'none';
};

export type CustomerContact = {
    customerId: string;
    email: string;
    id: string;
    isPrimary: boolean;
    name: string;
    phone: string;
    role: string;
};

export type CustomerAddress = {
    city: string;
    country: string;
    customerId: string;
    id: string;
    isPrimary: boolean;
    line1: string;
    line2?: string;
    postalCode: string;
    type: 'billing' | 'delivery' | 'service';
};

export type CustomerVehicle = {
    customerId: string;
    id: string;
    make: string;
    model: string;
    plateNumber: string;
    status: string;
    vin?: string;
    year: string;
};

export type CustomerTaxProfile = {
    customerId: string;
    exemptionReason?: string;
    taxGroup: string;
    taxRegistrationNumber?: string;
    taxStatus: 'registered' | 'exempt' | 'unregistered';
};

export type CustomerCreditProfile = {
    agingSummary: string;
    backendPreviewStatus: string;
    creditLimit: string;
    creditStatus: string;
    customerId: string;
    outstandingBalance: string;
    paymentTerms: string;
};

export type CustomerFinanceDefaults = {
    arAccount: string;
    costCenter: string;
    currency: string;
    customerId: string;
    paymentTerm: string;
    revenueAccount: string;
};

export type CustomerUserAccess = {
    customerId: string;
    email: string;
    id: string;
    lastLogin?: string;
    status: 'active' | 'inactive' | 'invited';
    userName: string;
};

export type CustomerUserAccessLinkInput = {
    accessRole?: string;
    invited?: boolean;
    isPrimary?: boolean;
    userId: string;
};

export type CustomerFormInput = {
    code: string;
    contactPerson: string;
    email: string;
    industry: string;
    name: string;
    notes?: string;
    phone: string;
    taxNumber?: string;
};
