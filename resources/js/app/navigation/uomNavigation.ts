import { uomPermissions } from '@/modules/uom/uomPermissions';
import type { NavigationAccessRule, NavigationModuleItem } from './navigationTypes';

const UOM_NAVIGATION_PERMISSIONS = Object.values(uomPermissions);

function uomAccess(permissions: readonly string[]): NavigationAccessRule {
    return {
        requiresTenant: true,
        requiresOrganizationUnit: true,
        permissions,
    };
}

export const uomNavigationItem = {
    id: 'units-of-measure',
    type: 'module',
    label: 'Units of Measure',
    icon: 'list',
    access: uomAccess(UOM_NAVIGATION_PERMISSIONS),
    children: [
        {
            id: 'uom-list',
            type: 'link',
            label: 'Units',
            to: '/uoms',
            match: ['/uoms'],
            exclude: ['/uoms/create'],
            access: uomAccess([uomPermissions.view]),
        },
        {
            id: 'uom-create',
            type: 'link',
            label: 'Create Unit',
            to: '/uoms/create',
            match: ['/uoms/create'],
            access: uomAccess([uomPermissions.create]),
        },
        {
            id: 'uom-conversions',
            type: 'link',
            label: 'Conversions',
            to: '/uom-conversions',
            match: ['/uom-conversions'],
            exclude: ['/uom-conversions/create'],
            access: uomAccess([uomPermissions.conversionsView]),
        },
        {
            id: 'uom-conversion-create',
            type: 'link',
            label: 'Create Conversion',
            to: '/uom-conversions/create',
            match: ['/uom-conversions/create'],
            access: uomAccess([uomPermissions.conversionsCreate]),
        },
        {
            id: 'uom-convert',
            type: 'link',
            label: 'Convert Quantity',
            to: '/uom-convert',
            match: ['/uom-convert'],
            access: uomAccess([uomPermissions.conversionsRun]),
        },
    ],
} satisfies NavigationModuleItem;
