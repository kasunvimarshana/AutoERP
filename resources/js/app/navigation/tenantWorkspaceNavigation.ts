import { inventoryRoutePermissions } from '@/modules/inventory/inventoryPermissions';
import { voucherViewPermissions } from '@/app/access/voucherRouteEntitlements';
import { hrNavigationItem } from './hrNavigation';
import { tenantNavigationSections as baseTenantNavigationSections } from './navigationConfig';
import type { NavigationItem, NavigationSection } from './navigationTypes';
import { uomNavigationItem } from './uomNavigation';

const MASTER_DATA_SECTION_ID = 'master-data';
const OPERATIONS_SECTION_ID = 'operations';
const FINANCE_SECTION_ID = 'finance';
const INVENTORY_ITEM_ID = 'inventory';
const VOUCHER_ITEM_ID = 'vouchers';
const VEHICLE_RENTAL_ITEM_ID = 'vehicle-rental';
const REDUNDANT_VEHICLE_RENTAL_CHILD_IDS = new Set(['rental-agreements']);

function inventoryNavigation(item: NavigationItem): NavigationItem {
    if (item.id !== INVENTORY_ITEM_ID || item.type !== 'module') return item;

    return {
        ...item,
        access: { ...item.access, permissions: inventoryRoutePermissions },
        children: item.children.map((child) => ({
            ...child,
            access: { ...child.access, permissions: inventoryRoutePermissions },
        })),
    };
}

function vehicleRentalNavigation(item: NavigationItem): NavigationItem {
    if (item.id !== VEHICLE_RENTAL_ITEM_ID || item.type !== 'module') return item;

    return {
        ...item,
        children: item.children.filter(
            (child) => !REDUNDANT_VEHICLE_RENTAL_CHILD_IDS.has(child.id),
        ),
    };
}

function voucherNavigation(item: NavigationItem): NavigationItem {
    if (item.id !== VOUCHER_ITEM_ID || item.type !== 'link') return item;

    return {
        ...item,
        access: {
            ...item.access,
            requiresOrganizationUnit: true,
            permissions: voucherViewPermissions,
        },
    };
}

export const tenantWorkspaceNavigationSections: NavigationSection[] = baseTenantNavigationSections.map((section) => {
    if (section.id === MASTER_DATA_SECTION_ID) {
        return { ...section, items: [...section.items, uomNavigationItem] };
    }
    if (section.id === OPERATIONS_SECTION_ID) {
        return {
            ...section,
            items: [
                ...section.items.map(inventoryNavigation).map(vehicleRentalNavigation),
                hrNavigationItem,
            ],
        };
    }
    if (section.id === FINANCE_SECTION_ID) {
        return { ...section, items: section.items.map(voucherNavigation) };
    }

    return section;
});
