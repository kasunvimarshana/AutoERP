import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const pricingDashboardPage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingDashboardPage'), 'PricingDashboardPage');
const priceListPage = () => lazyNamed(() => import('../../modules/pricing/pages/PriceListPage'), 'PriceListPage');
const priceListCreatePage = () => lazyNamed(() => import('../../modules/pricing/pages/PriceListCreatePage'), 'PriceListCreatePage');
const priceListDetailPage = () => lazyNamed(() => import('../../modules/pricing/pages/PriceListDetailPage'), 'PriceListDetailPage');
const priceListEditPage = () => lazyNamed(() => import('../../modules/pricing/pages/PriceListEditPage'), 'PriceListEditPage');
const pricingRuleListPage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingRuleListPage'), 'PricingRuleListPage');
const pricingRuleCreatePage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingRuleCreatePage'), 'PricingRuleCreatePage');
const pricingRuleDetailPage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingRuleDetailPage'), 'PricingRuleDetailPage');
const pricingRuleEditPage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingRuleEditPage'), 'PricingRuleEditPage');
const discountListPage = () => lazyNamed(() => import('../../modules/pricing/pages/DiscountListPage'), 'DiscountListPage');
const pricingTierListPage = () => lazyNamed(() => import('../../modules/pricing/pages/PricingTierListPage'), 'PricingTierListPage');
const priceResolverPage = () => lazyNamed(() => import('../../modules/pricing/pages/PriceResolverPage'), 'PriceResolverPage');
const priceHistoryPage = () => lazyNamed(() => import('../../modules/pricing/pages/PriceHistoryPage'), 'PriceHistoryPage');

export const pricingRoutes: RouteObject[] = [
    { element: pricingDashboardPage(), path: 'pricing' },
    { element: priceListPage(), path: 'pricing/price-lists' },
    { element: priceListCreatePage(), path: 'pricing/price-lists/new' },
    { element: priceListDetailPage(), path: 'pricing/price-lists/:id' },
    { element: priceListEditPage(), path: 'pricing/price-lists/:id/edit' },
    { element: pricingRuleListPage(), path: 'pricing/rules' },
    { element: pricingRuleCreatePage(), path: 'pricing/rules/new' },
    { element: pricingRuleDetailPage(), path: 'pricing/rules/:id' },
    { element: pricingRuleEditPage(), path: 'pricing/rules/:id/edit' },
    { element: discountListPage(), path: 'pricing/discounts' },
    { element: pricingTierListPage(), path: 'pricing/tiers' },
    { element: priceResolverPage(), path: 'pricing/resolve' },
    { element: priceHistoryPage(), path: 'pricing/history' },
];
