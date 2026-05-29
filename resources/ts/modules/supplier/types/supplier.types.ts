export type Supplier = {
    code: string;
    contact: string;
    id: string;
    name: string;
    paymentProfile: string;
    status: string;
    userAccess: 'none' | 'linked';
};

export type SupplierFormInput = {
    contact: string;
    email: string;
    name: string;
    phone: string;
    taxNumber?: string;
};
