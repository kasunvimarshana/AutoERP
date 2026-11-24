/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import type { NavigationAccessRule, NavigationModuleItem } from './navigationTypes';

const PAYROLL_MODULES = [TENANT_MODULE_CODE.HR] as const;

function payrollAccess(): NavigationAccessRule {
    return {
        requiresTenant: true,
        requiresOrganizationUnit: true,
        modules: PAYROLL_MODULES,
    };
}

export const payrollNavigationItem = {
    id: 'payroll-management',
    type: 'module',
    label: 'Payroll',
    icon: 'payment',
    access: payrollAccess(),
    children: [
        {
            id: 'payroll-pay-runs',
            type: 'link',
            label: 'Pay Runs & Execution',
            to: '/payroll/pay-runs',
            match: ['/payroll/pay-runs'],
            access: payrollAccess(),
        },
        {
            id: 'payroll-structures',
            type: 'link',
            label: 'Salary Structures',
            to: '/payroll/structures',
            match: ['/payroll/structures'],
            access: payrollAccess(),
        },
        {
            id: 'payroll-loans',
            type: 'link',
            label: 'Loans & Advances',
            to: '/payroll/loans',
            match: ['/payroll/loans'],
            access: payrollAccess(),
        },
        {
            id: 'payroll-payslips',
            type: 'link',
            label: 'Payslips',
            to: '/payroll/payslips',
            match: ['/payroll/payslips'],
            access: payrollAccess(),
        },
    ],
} satisfies NavigationModuleItem;
