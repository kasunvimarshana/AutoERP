import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const voucherPage = () => lazyNamed(() => import('../../modules/voucher/pages/VoucherPage'), 'VoucherPage');

export const voucherRoutes: RouteObject[] = [
    { element: voucherPage(), path: 'vouchers' },
    { element: voucherPage(), path: 'vouchers/new' },
    { element: voucherPage(), path: 'vouchers/:id' },
];
