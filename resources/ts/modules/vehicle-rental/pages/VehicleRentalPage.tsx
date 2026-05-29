import { useLocation } from 'react-router-dom';
import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

const sections = [
    { description: 'Vehicle availability calendar and backend conflict checks.', label: 'Availability', path: '/vehicle-rental/availability', status: 'Mocked' },
    { description: 'Rental agreements, rates, deposits, driver/customer references.', label: 'Agreements', path: '/vehicle-rental/agreements', status: 'Ready' },
    { description: 'Usage capture for km/hour/day/month billing previews.', label: 'Running Charts', path: '/vehicle-rental/running-charts', status: 'Mocked' },
    { description: 'Customer rental invoice previews and issued invoices.', label: 'Rental Invoices', path: '/vehicle-rental/invoices', status: 'Mocked' },
    { description: 'Customer receipts and backend allocation preview.', label: 'Rental Payments', path: '/vehicle-rental/payments', status: 'Mocked' },
    { description: 'Provider settlement and payable preview.', label: 'Provider Payables', path: '/vehicle-rental/provider-payables', status: 'Planned' },
];

const titles: Record<string, string> = {
    '/vehicle-rental/agreements': 'Rental Agreements',
    '/vehicle-rental/availability': 'Vehicle Availability',
    '/vehicle-rental/invoices': 'Rental Invoices',
    '/vehicle-rental/payments': 'Rental Payments',
    '/vehicle-rental/provider-payables': 'Provider Payables',
    '/vehicle-rental/running-charts': 'Running Charts',
};

export function VehicleRentalPage() {
    const { pathname } = useLocation();

    return (
        <ModulePlaceholderPage
            actions={[
                { label: 'New Agreement', path: '/vehicle-rental/agreements/new', variant: 'primary' },
                { label: 'Availability Preview', path: '/vehicle-rental/availability', variant: 'secondary' },
            ]}
            description="Vehicle availability, rental agreements, running charts, rental invoices, payments, provider payables, replacements, and breakdowns. Backend owns availability, billing, overtime, provider payable, finance, and payment logic."
            metrics={[
                { helper: 'Mock fleet count', label: 'Available vehicles', value: 17 },
                { helper: 'Backend billing status later', label: 'Open running charts', value: 8 },
                { helper: 'Backend payable preview', label: 'Provider payable drafts', value: 3 },
            ]}
            sections={pathname === '/vehicle-rental' ? sections : undefined}
            title={titles[pathname] ?? 'Vehicle Rental Dashboard'}
        />
    );
}
