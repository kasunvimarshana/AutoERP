import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { hrPermissions } from '@/modules/hr/hrPermissions';
import type { NavigationAccessRule, NavigationModuleItem } from './navigationTypes';

const HR_MODULES = [TENANT_MODULE_CODE.HR] as const;
const HR_NAVIGATION_PERMISSIONS = Object.values(hrPermissions);

function hrAccess(permissions: readonly string[]): NavigationAccessRule {
    return {
        requiresTenant: true,
        requiresOrganizationUnit: true,
        modules: HR_MODULES,
        permissions,
    };
}

export const hrNavigationItem = {
    id: 'human-resources',
    type: 'module',
    label: 'Human Resources',
    icon: 'users',
    access: hrAccess(HR_NAVIGATION_PERMISSIONS),
    children: [
        {
            id: 'hr-employees',
            type: 'link',
            label: 'Employees',
            to: '/hr/employees',
            match: ['/hr/employees'],
            exclude: ['/hr/employees/create'],
            access: hrAccess([hrPermissions.employeesView]),
        },
        {
            id: 'hr-employee-create',
            type: 'link',
            label: 'Create Employee',
            to: '/hr/employees/create',
            match: ['/hr/employees/create'],
            access: hrAccess([hrPermissions.employeesCreate]),
        },
        ...masterDataLinks(),
    ],
} satisfies NavigationModuleItem;

function masterDataLinks(): NavigationModuleItem['children'] {
    return [
        ['departments', 'Departments'],
        ['designations', 'Designations'],
        ['employment-types', 'Employment Types'],
        ['skills', 'Skills'],
        ['certifications', 'Certifications'],
        ['licenses', 'Licenses'],
    ].map(([view, label]) => ({
        id: `hr-${view}`,
        type: 'link' as const,
        label,
        to: `/hr/employees?view=${view}`,
        match: ['/hr/employees'],
        exclude: ['/hr/employees/create'],
        access: hrAccess([hrPermissions.masterDataView, hrPermissions.masterDataManage]),
    }));
}
