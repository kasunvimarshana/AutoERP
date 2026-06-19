export const paymentPermissions = {
    view: 'payments.view',
    create: 'payments.create',
    update: 'payments.update',
    submit: 'payments.submit',
    approve: 'payments.approve',
    post: 'payments.post',
    void: 'payments.void',
    reverse: 'payments.reverse',
    allocate: 'payments.allocate',
    refund: 'payments.refund',
    settle: 'payments.settle',
    methodsView: 'payment-methods.view',
    methodsCreate: 'payment-methods.create',
    methodsUpdate: 'payment-methods.update',
    methodsDelete: 'payment-methods.delete',
    templatesView: 'cheque-templates.view',
    templatesCreate: 'cheque-templates.create',
    templatesUpdate: 'cheque-templates.update',
    templatesDelete: 'cheque-templates.delete',
    chequesPreview: 'cheques.preview',
    chequesPrint: 'cheques.print',
} as const;

export function hasPaymentPermission(permissions: string[], permission: string): boolean {
    return permissions.includes(permission);
}