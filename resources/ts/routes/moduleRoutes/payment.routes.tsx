import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const paymentPage = () => lazyNamed(() => import('../../modules/payment/pages/PaymentPage'), 'PaymentPage');

export const paymentRoutes: RouteObject[] = [{ element: paymentPage(), path: 'payments' }];
