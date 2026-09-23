import { hasPermission, type AccessSubject } from '@/modules/auth/accessControl';

export const expensePermissions = {
    view: 'expenses.view',
    create: 'expenses.create',
    reverse: 'expenses.reverse',
    typesView: 'expense-types.view',
    typesManage: 'expense-types.manage',
} as const;

export function hasExpensePermission(subject: AccessSubject, permission: string): boolean {
    return hasPermission(subject, permission);
}
