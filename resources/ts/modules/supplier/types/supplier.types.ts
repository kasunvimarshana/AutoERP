export type SupplierStatus = 'active' | 'archived' | 'blocked' | 'draft' | 'inactive' | 'pending_approval' | 'suspended';

export type SupplierCategory = {
    id: string;
    name: string;
};

export type Supplier = {
    category: string;
    code: string;
    defaultCurrency?: string;
    displayName: string;
    email: string;
    id: string;
    legalName?: string;
    mobile?: string;
    name: string;
    notes?: string;
    phone: string;
    registrationNumber?: string;
    status: SupplierStatus;
    supplierType: string;
    taxNumber?: string;
    updatedAt: string;
    userAccessStatus: 'linked' | 'none';
    vatNumber?: string;
    website?: string;
};

export type SupplierContact = {
    department?: string;
    designation?: string;
    email: string;
    id: string;
    isPrimary: boolean;
    name: string;
    phone: string;
    supplierId: string;
};

export type SupplierAddress = {
    city: string;
    country: string;
    id: string;
    isDefault: boolean;
    line1: string;
    line2?: string;
    postalCode?: string;
    supplierId: string;
    type: 'billing' | 'registered' | 'shipping';
};

export type SupplierBankAccount = {
    accountName: string;
    accountNumber: string;
    bankName: string;
    branchName?: string;
    currency: string;
    id: string;
    isActive: boolean;
    isPrimary: boolean;
    supplierId: string;
};

export type SupplierTaxProfile = {
    isTaxExempt: boolean;
    supplierId: string;
    taxIdentifier?: string;
    taxType: string;
    vatIdentifier?: string;
    withholdingRate: string;
};

export type SupplierFinanceDefaults = {
    creditLimit: string;
    defaultCurrency: string;
    expenseAccount: string;
    payableAccount: string;
    paymentTerm: string;
    supplierId: string;
};

export type SupplierUserAccess = {
    email: string;
    id: string;
    invitedAt?: string;
    isPrimary: boolean;
    lastLogin?: string;
    status: 'active' | 'deactivated' | 'invited';
    supplierId: string;
    userName: string;
};

export type SupplierUserAccessLinkInput = {
    accessType?: string;
    isPrimary?: boolean;
    userId: string;
};

export type SupplierUserAccessCreateInput = {
    accessType?: string;
    email: string;
    isPrimary?: boolean;
    name?: string;
};

export type SupplierAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
};

export type SupplierFormInput = {
    category: string;
    code: string;
    displayName: string;
    email: string;
    legalName: string;
    mobile: string;
    name: string;
    notes?: string;
    phone: string;
    registrationNumber: string;
    status: SupplierStatus;
    supplierType: string;
    taxNumber?: string;
    vatNumber?: string;
    website?: string;
};
