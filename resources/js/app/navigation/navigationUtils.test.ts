import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { activeNavigationTrail, filterNavigation, initialExpandedIds, itemIsActive, resolveEnabledModules, routeMatches } from './navigationUtils.ts';
import type { NavigationItem, NavigationSection } from './navigationTypes.ts';
import { navigationActions, navigationSections } from './navigationConfig.ts';

const parent: NavigationItem = {
    id: 'sales',
    label: 'Sales',
    children: [
        { id: 'orders', label: 'Orders', route: '/sales/orders' },
        {
            id: 'invoices',
            label: 'Invoices',
            route: '/invoices?invoice_type=sales',
            activeRoutes: [
                { path: '/invoices', query: { invoice_type: 'sales' } },
                { path: '/invoices/:id', query: { invoice_type: 'sales' } },
            ],
        },
    ],
};

test('matches list, create, detail, and edit routes from one prefix rule', () => {
    for (const pathname of ['/sales/orders', '/sales/orders/create', '/sales/orders/12', '/sales/orders/12/edit']) {
        assert.equal(itemIsActive(parent.children![0], { pathname, search: '' }), true);
    }
});

test('keeps contextual shared workspaces distinct', () => {
    assert.equal(itemIsActive(parent.children![1], { pathname: '/invoices/12', search: '?invoice_type=sales' }), true);
    assert.equal(itemIsActive(parent.children![1], { pathname: '/invoices/12', search: '?invoice_type=purchase' }), false);
});

test('filters permissions, modules, features, and empty groups without changing order', () => {
    const sections: NavigationSection[] = [{
        id: 'operations',
        items: [
            { id: 'sales', label: 'Sales', route: '/sales', requiredModule: 'sales' },
            { id: 'rental', label: 'Rental', requiredFeature: 'rental', children: [
                { id: 'agreements', label: 'Agreements', route: '/agreements', requiredPermissions: ['rental.manage'] },
            ] },
        ],
    }];
    const filtered = filterNavigation(sections, {
        permissions: [],
        roles: [],
        enabledModules: ['sales'],
        features: { rental: false },
    });

    assert.deepEqual(filtered.map((section) => section.items.map((item) => item.id)), [['sales']]);
});

test('automatically expands the active module and preserves valid stored expansion', () => {
    const sections: NavigationSection[] = [{ id: 'operations', items: [
        parent,
        { id: 'purchase', label: 'Purchase', children: [{ id: 'po', label: 'Orders', route: '/purchase/orders' }] },
    ] }];
    assert.deepEqual(
        initialExpandedIds(sections, { pathname: '/sales/orders/5/edit', search: '' }, ['purchase']),
        ['purchase', 'sales'],
    );
});

test('supports exact routes and absent query requirements', () => {
    assert.equal(routeMatches(
        { pathname: '/payments', search: '' },
        { path: '/payments', exact: true, query: { payment_type: undefined } },
    ), true);
    assert.equal(routeMatches(
        { pathname: '/payments', search: '?payment_type=customer_receipt' },
        { path: '/payments', exact: true, query: { payment_type: undefined } },
    ), false);
});

test('keeps the requested domain hierarchy and ordering', () => {
    assert.deepEqual(
        navigationSections.map((section) => section.label ?? 'Dashboard'),
        ['Dashboard', 'Operations', 'Finance', 'Master Data', 'Reports', 'Administration'],
    );
    const operations = navigationSections.find((section) => section.id === 'operations');
    assert.deepEqual(
        operations?.items.map((item) => item.label),
        ['Sales', 'Purchase', 'Inventory', 'Vehicle Service', 'Vehicle Rental', 'HR'],
    );
    const vehicles = navigationSections
        .find((section) => section.id === 'master-data')
        ?.items.find((item) => item.id === 'vehicles');
    assert.deepEqual(
        vehicles?.children?.map((item) => item.label),
        ['All Vehicles', 'Fleet Vehicles', 'Customer Vehicles', 'Supplier / Owner Vehicles'],
    );
});

test('intersects tenant and organization module access', () => {
    assert.deepEqual(
        resolveEnabledModules(['Sales', 'Inventory', 'Finance'], ['sales', 'finance']),
        ['sales', 'finance'],
    );
    assert.deepEqual(resolveEnabledModules([], ['vehicle-service']), ['vehicle-service']);
    assert.deepEqual(resolveEnabledModules(['vehicle_rental'], ['Vehicle Rental']), ['vehicle-rental']);
});

test('builds a two-level breadcrumb from the active destination', () => {
    assert.deepEqual(
        activeNavigationTrail(
            [{ id: 'operations', items: [parent] }],
            { pathname: '/sales/orders/5/edit', search: '' },
        ).map((item) => item.label),
        ['Sales', 'Orders'],
    );
});

test('does not expose rental workspaces without a matching permission', () => {
    const filtered = filterNavigation(navigationSections, {
        permissions: [],
        roles: [],
    });
    assert.equal(
        filtered.find((section) => section.id === 'operations')?.items.some((item) => item.id === 'vehicle-rental'),
        false,
    );
});

test('all configured workspace and palette destinations resolve to frontend routes', () => {
    const router = readFileSync('resources/js/app/router.tsx', 'utf8');
    const routePaths = new Set(Array.from(router.matchAll(/<Route path="([^"]+)"/g), (match) => match[1]));
    const configured = [
        ...navigationSections.flatMap((section) => flatten(section.items)),
        ...navigationActions,
    ].filter((item) => item.route);

    for (const item of configured) {
        const pathname = new URL(item.route!, 'https://autoerp.local').pathname;
        assert.equal(routePaths.has(pathname), true, `${item.label} points to unavailable route ${pathname}`);
    }
});

function flatten(items: NavigationItem[]): NavigationItem[] {
    return items.flatMap((item) => [item, ...flatten(item.children ?? [])]);
}
