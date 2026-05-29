import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/payment/pages/PaymentDashboardPage'), 'PaymentDashboardPage');
const paymentList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentListPage'), 'PaymentListPage');
const paymentCreate = () => lazyNamed(() => import('../../modules/payment/pages/PaymentFormPages'), 'PaymentCreatePage');
const paymentEdit = () => lazyNamed(() => import('../../modules/payment/pages/PaymentFormPages'), 'PaymentEditPage');
const paymentDetail = () => lazyNamed(() => import('../../modules/payment/pages/PaymentDetailPage'), 'PaymentDetailPage');
const methodList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'PaymentMethodListPage');
const groupList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'PaymentGroupListPage');
const allocationList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'PaymentAllocationListPage');
const advanceList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'AdvancePaymentListPage');
const refundList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'RefundListPage');
const writeOffList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'WriteOffListPage');
const cashRegisterList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'CashRegisterListPage');
const checkList = () => lazyNamed(() => import('../../modules/payment/pages/PaymentReferencePages'), 'CheckListPage');

export const paymentRoutes: RouteObject[] = [
    { element: dashboard(), path: 'payments' },
    { element: paymentList(), path: 'payments/payments' },
    { element: paymentCreate(), path: 'payments/payments/new' },
    { element: paymentDetail(), path: 'payments/payments/:id' },
    { element: paymentEdit(), path: 'payments/payments/:id/edit' },
    { element: methodList(), path: 'payments/methods' },
    { element: groupList(), path: 'payments/groups' },
    { element: allocationList(), path: 'payments/allocations' },
    { element: advanceList(), path: 'payments/advances' },
    { element: refundList(), path: 'payments/refunds' },
    { element: writeOffList(), path: 'payments/write-offs' },
    { element: cashRegisterList(), path: 'payments/cash-registers' },
    { element: checkList(), path: 'payments/checks' },
];
