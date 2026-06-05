export type PartyStatus = 'active' | 'inactive';

export type PartyAddress = {
    addressLine1: string;
    addressLine2?: string;
    city: string;
    countryName?: string;
    label?: string;
    postalCode: string;
    stateProvince?: string;
};

export type PartyListItem = {
    code: string;
    createdAt: string;
    creditLimit: string;
    displayName?: string;
    email?: string;
    id: number;
    mobile?: string;
    name: string;
    organizationUnitId?: number;
    paymentTermsDays: number;
    phone?: string;
    status: PartyStatus;
    updatedAt: string;
};

export type PartyDetail = PartyListItem & {
    address: PartyAddress | null;
    availableCredit: string | null;
    currentCreditBalance: string | null;
    notes?: string;
    rowVersion: number;
    taxNumber?: string;
    tenantId: number;
    vatNumber?: string;
};

export type PartyInput = {
    address: PartyAddress | null;
    code: string;
    creditLimit: string;
    displayName?: string;
    email?: string;
    mobile?: string;
    name: string;
    notes?: string;
    organizationUnitId?: number;
    paymentTermsDays: number;
    phone?: string;
    status: PartyStatus;
    taxNumber?: string;
    vatNumber?: string;
};

export type PartyListQuery = {
    page: number;
    perPage: number;
    search?: string;
    status?: PartyStatus;
};

export type PartyPage = {
    items: PartyListItem[];
    meta: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
};

export type PartyApi = {
    create: (input: PartyInput) => Promise<PartyDetail>;
    get: (id: number) => Promise<PartyDetail>;
    list: (query: PartyListQuery) => Promise<PartyPage>;
    remove: (id: number) => Promise<void>;
    update: (id: number, input: PartyInput) => Promise<PartyDetail>;
};
