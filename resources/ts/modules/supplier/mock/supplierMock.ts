import type { Supplier } from '../types/supplier.types';

export const suppliers: Supplier[] = [
    { code: 'SUP-001', contact: 'Parts Desk', id: 'sup-001', name: 'Auto Parts Lanka', paymentProfile: 'Backend AP defaults pending', status: 'Active', userAccess: 'none' },
    { code: 'SUP-002', contact: 'External Services', id: 'sup-002', name: 'Wheel Align Pro', paymentProfile: 'Provider payable enabled', status: 'Active', userAccess: 'linked' },
    { code: 'SUP-003', contact: 'Finance Team', id: 'sup-003', name: 'Fleet Supplies', paymentProfile: 'Backend AP defaults pending', status: 'Pending', userAccess: 'none' },
];
