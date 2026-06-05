export type BusinessPartyType =
    | 'company'
    | 'customer'
    | 'employee'
    | 'external_party'
    | 'other'
    | 'partner'
    | 'party'
    | 'provider'
    | 'supplier'
    | 'user';

export type BusinessPartyRelationType =
    | 'acts_as'
    | 'billing_relation'
    | 'payee_relation'
    | 'payer_relation'
    | 'provider_relation'
    | 'same_party';

export type BusinessPartyLink = {
    endDate?: string;
    id: string;
    isActive: boolean;
    notes?: string;
    relationType: BusinessPartyRelationType;
    sourcePartyId?: string;
    sourcePartyName?: string;
    sourcePartyType: BusinessPartyType;
    startDate?: string;
    targetPartyId?: string;
    targetPartyName?: string;
    targetPartyType: BusinessPartyType;
};

