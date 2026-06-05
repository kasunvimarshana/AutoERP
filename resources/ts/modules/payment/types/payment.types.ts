export type PaymentDirection = 'inbound' | 'outbound';
export type Payment = { allocatedAmount: string; amount: string; direction: PaymentDirection; id: number; partyId: number; partyType: 'customer' | 'supplier'; paymentDate: string; paymentMethodId: number; paymentNumber: string; status: string; unallocatedAmount: string };
export type PaymentInput = { allocations?: { allocatedAmount: string; invoiceId: number }[]; amount: string; direction: PaymentDirection; partyId: number; partyType: 'customer' | 'supplier'; paymentDate: string; paymentMethodId: number; paymentNumber?: string; reference?: string };
export type PaymentPage = { meta: { currentPage: number; lastPage: number; perPage: number; total: number }; payments: Payment[] };
