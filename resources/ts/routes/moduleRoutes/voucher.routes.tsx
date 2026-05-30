import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/voucher/pages/VoucherDashboardPage'), 'VoucherDashboardPage');
const vouchers = () => import('../../modules/voucher/pages/VoucherPages');
const types = () => import('../../modules/voucher/pages/VoucherTypePages');
const operational = () => import('../../modules/voucher/pages/VoucherOperationalPages');

export const voucherRoutes: RouteObject[] = [
    { element: dashboard(), path: 'vouchers' },
    { element: lazyNamed(vouchers, 'VoucherListPage'), path: 'vouchers/list' },
    { element: lazyNamed(types, 'VoucherTypeListPage'), path: 'vouchers/types' },
    { element: lazyNamed(types, 'VoucherTypeCreatePage'), path: 'vouchers/types/new' },
    { element: lazyNamed(types, 'VoucherTypeDetailPage'), path: 'vouchers/types/:id' },
    { element: lazyNamed(types, 'VoucherTypeEditPage'), path: 'vouchers/types/:id/edit' },
    { element: lazyNamed(vouchers, 'VoucherCreatePage'), path: 'vouchers/new' },
    { element: lazyNamed(vouchers, 'VoucherDetailPage'), path: 'vouchers/:id' },
    { element: lazyNamed(vouchers, 'VoucherEditPage'), path: 'vouchers/:id/edit' },
    { element: lazyNamed(operational, 'VoucherApprovalListPage'), path: 'vouchers/approvals' },
    { element: lazyNamed(operational, 'VoucherAllocationListPage'), path: 'vouchers/allocations' },
    { element: lazyNamed(operational, 'VoucherPostingPreviewPage'), path: 'vouchers/posting-preview' },
    { element: lazyNamed(operational, 'VoucherSettingsPage'), path: 'vouchers/settings' },
];
