import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function VehiclePage() {
    return (
        <ModulePlaceholderPage
            actions={[{ label: 'New Vehicle', path: '/vehicles/new', variant: 'primary' }]}
            description="Fleet and customer vehicle records used by service and rental workflows. Backend validates tenant ownership, availability, and module eligibility."
            title="Vehicles"
        />
    );
}
