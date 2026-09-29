import type { PaymentLinePayload, PaymentMethod } from './paymentApi';
import type { PaymentLineDraft } from './components/PaymentLineTable';
import { isPositiveDecimal } from '@/shared/utils/decimal';
const PAYMENT_DIRECTION_OUTBOUND = 'outbound';

function methodKind(method?: PaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'direct_debit'].includes(type)) return 'bank_transfer';
    if (['digital_wallet', 'mobile_wallet'].includes(type)) return 'wallet';
    return type || 'other';
}

export function linePayload(line: PaymentLineDraft, method: PaymentMethod, direction: string): PaymentLinePayload {
    const kind = methodKind(method);
    const payload: PaymentLinePayload = {
        payment_method_id: method.id,
        amount: line.amount,
        reference_number: line.reference.trim() || undefined,
        instrument_direction: direction === PAYMENT_DIRECTION_OUTBOUND ? 'issued' : 'received',
    };

    if (kind === 'cheque') {
        payload.instrument_number = line.metadata.cheque_number?.trim() || undefined;
        payload.instrument_date = line.metadata.cheque_date || undefined;
        payload.external_bank_name = line.metadata.bank_account?.trim() || undefined;
    }
    if (kind === 'bank_transfer') {
        payload.instrument_number = line.metadata.transfer_reference?.trim() || undefined;
        payload.instrument_date = line.metadata.transfer_date || undefined;
        payload.external_bank_name = line.metadata.bank_account?.trim() || undefined;
    }
    if (kind === 'card') {
        payload.instrument_number = line.metadata.card_reference?.trim() || line.metadata.authorization_code?.trim() || undefined;
        payload.external_bank_name = line.metadata.terminal?.trim() || undefined;
    }
    if (kind === 'wallet') {
        payload.instrument_number = line.metadata.wallet_reference?.trim() || undefined;
        payload.external_bank_name = line.metadata.provider?.trim() || undefined;
    }

    return payload;
}

export function lineIsValid(line: PaymentLineDraft, method: PaymentMethod | undefined, headerReference: string, direction: string): boolean {
    if (!method || !isPositiveDecimal(line.amount)) return false;

    const payload = linePayload(line, method, direction);
    const hasReference = Boolean(payload.reference_number || headerReference.trim());
    const hasInstrumentDetails = Boolean(
        payload.instrument_number
        || payload.instrument_date
        || payload.external_bank_name
        || payload.external_bank_branch,
    );

    return (!method.requires_reference || hasReference)
        && (!method.requires_instrument_details || hasInstrumentDetails);
}
