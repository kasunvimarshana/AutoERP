import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const financePage = () => lazyNamed(() => import('../../modules/finance/pages/FinancePage'), 'FinancePage');

export const financeRoutes: RouteObject[] = [
    { element: financePage(), path: 'finance' },
    { element: financePage(), path: 'finance/accounts' },
    { element: financePage(), path: 'finance/journal-entries' },
    { element: financePage(), path: 'finance/tax' },
    { element: financePage(), path: 'finance/banks' },
];
