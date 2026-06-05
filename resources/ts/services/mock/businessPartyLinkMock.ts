import type { BusinessPartyLink } from '../../shared/types/businessParty.types';

export const businessPartyLinks: BusinessPartyLink[] = [
    {
        id: 'party-link-001',
        isActive: true,
        notes: 'ABC Motors has both supplier/provider and customer billing roles.',
        relationType: 'same_party',
        sourcePartyId: 'sup-001',
        sourcePartyName: 'ABC Motors',
        sourcePartyType: 'supplier',
        startDate: '2026-01-01',
        targetPartyId: 'cus-002',
        targetPartyName: 'ABC Motors Customer Profile',
        targetPartyType: 'customer',
    },
    {
        id: 'party-link-002',
        isActive: true,
        notes: 'Fleet customer can also provide vehicles for rental operations.',
        relationType: 'provider_relation',
        sourcePartyId: 'cus-001',
        sourcePartyName: 'City Logistics',
        sourcePartyType: 'customer',
        startDate: '2026-02-15',
        targetPartyId: 'sup-003',
        targetPartyName: 'City Logistics Provider Profile',
        targetPartyType: 'supplier',
    },
    {
        id: 'party-link-003',
        isActive: true,
        notes: 'Insurance company may settle selected service invoices as payer.',
        relationType: 'payer_relation',
        sourcePartyId: 'cus-003',
        sourcePartyName: 'John Perera',
        sourcePartyType: 'customer',
        startDate: '2026-03-10',
        targetPartyName: 'Guardian Insurance',
        targetPartyType: 'external_party',
    },
];

