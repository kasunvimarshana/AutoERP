import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const pricingListPage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingListPage'), 'PricingListPage');
const pricingCreatePage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingCreatePage'), 'PricingCreatePage');

export const pricingRoutes: RouteObject[] = [
    { element: pricingListPage(), path: 'pricing' },
    { element: pricingListPage(), path: 'pricing/price-lists' },
    { element: pricingCreatePage(), path: 'pricing/rules' },
];
