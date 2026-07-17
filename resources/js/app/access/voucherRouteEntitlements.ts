import { financePermissions } from '@/modules/finance/financePermissions';
import { paymentPermissions } from '@/modules/payment/paymentPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const voucherViewPermissions = [
    paymentPermissions.view,
    financePermissions.journalsView,
] as const;

export const voucherRouteEntitlements: readonly EntitlementRule[] = [
    operational('/vouchers/:voucherType/:sourceId', undefined, voucherViewPermissions),
    operational('/vouchers', undefined, voucherViewPermissions),
];
