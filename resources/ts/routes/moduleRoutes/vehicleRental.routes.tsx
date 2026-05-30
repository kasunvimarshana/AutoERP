import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/vehicle-rental/pages/VehicleRentalDashboardPage'), 'VehicleRentalDashboardPage');
const agreements = () => import('../../modules/vehicle-rental/pages/RentalAgreementPages');
const runningCharts = () => import('../../modules/vehicle-rental/pages/RunningChartPages');
const operational = () => import('../../modules/vehicle-rental/pages/VehicleRentalOperationalPages');
const settings = () => import('../../modules/vehicle-rental/pages/VehicleRentalSettingsPage');

export const vehicleRentalRoutes: RouteObject[] = [
    { element: dashboard(), path: 'vehicle-rental' },
    { element: lazyNamed(operational, 'VehicleAvailabilityPage'), path: 'vehicle-rental/availability' },
    { element: lazyNamed(agreements, 'RentalAgreementListPage'), path: 'vehicle-rental/agreements' },
    { element: lazyNamed(agreements, 'RentalAgreementCreatePage'), path: 'vehicle-rental/agreements/new' },
    { element: lazyNamed(agreements, 'RentalAgreementDetailPage'), path: 'vehicle-rental/agreements/:id' },
    { element: lazyNamed(agreements, 'RentalAgreementEditPage'), path: 'vehicle-rental/agreements/:id/edit' },
    { element: lazyNamed(runningCharts, 'RunningChartListPage'), path: 'vehicle-rental/running-charts' },
    { element: lazyNamed(runningCharts, 'RunningChartCreatePage'), path: 'vehicle-rental/running-charts/new' },
    { element: lazyNamed(runningCharts, 'RunningChartDetailPage'), path: 'vehicle-rental/running-charts/:id' },
    { element: lazyNamed(runningCharts, 'RunningChartEditPage'), path: 'vehicle-rental/running-charts/:id/edit' },
    { element: lazyNamed(operational, 'RentalInvoiceListPage'), path: 'vehicle-rental/invoices' },
    { element: lazyNamed(operational, 'RentalInvoiceDetailPage'), path: 'vehicle-rental/invoices/:id' },
    { element: lazyNamed(operational, 'RentalPaymentListPage'), path: 'vehicle-rental/payments' },
    { element: lazyNamed(operational, 'RentalPaymentCreatePage'), path: 'vehicle-rental/payments/new' },
    { element: lazyNamed(operational, 'ProviderPayableListPage'), path: 'vehicle-rental/provider-payables' },
    { element: lazyNamed(operational, 'ProviderPayableDetailPage'), path: 'vehicle-rental/provider-payables/:id' },
    { element: lazyNamed(operational, 'ReplacementVehicleListPage'), path: 'vehicle-rental/replacements' },
    { element: lazyNamed(operational, 'BreakdownListPage'), path: 'vehicle-rental/breakdowns' },
    { element: lazyNamed(settings, 'VehicleRentalSettingsPage'), path: 'vehicle-rental/settings' },
];
