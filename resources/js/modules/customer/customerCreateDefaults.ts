import type { NamedResource } from '@/shared/types/common';
import { generateCustomerCode, searchCurrencies } from './customerApi';
import type { CustomerPayload } from './customerTypes';

export const DEFAULT_CUSTOMER_TYPE = 'individual';
export const DEFAULT_CUSTOMER_STATUS = 'active';
export const DEFAULT_CUSTOMER_CURRENCY_CODE = 'LKR';

export function defaultCustomerPayload(): CustomerPayload {
    return {
        customer_number: null,
        code: '',
        name: '',
        customer_type: DEFAULT_CUSTOMER_TYPE,
        status: DEFAULT_CUSTOMER_STATUS,
        default_currency_id: null,
        is_tax_exempt: false,
        marketing_consent: false,
        preferred_communication_channel: null,
    };
}

export async function loadCustomerCreationDefaults(signal: AbortSignal): Promise<{
    code: string;
    currency: NamedResource;
}> {
    const [code, currencies] = await Promise.all([
        generateCustomerCode(signal),
        searchCurrencies({
            search: DEFAULT_CUSTOMER_CURRENCY_CODE,
            page: 1,
            perPage: 25,
            signal,
        }),
    ]);
    const currency = currencies.data.find(
        (candidate) => candidate.code?.toUpperCase() === DEFAULT_CUSTOMER_CURRENCY_CODE,
    );

    if (!currency) {
        throw new Error(`${DEFAULT_CUSTOMER_CURRENCY_CODE} currency is not configured.`);
    }

    return { code, currency };
}
