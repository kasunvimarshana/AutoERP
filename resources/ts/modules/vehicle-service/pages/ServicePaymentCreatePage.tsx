import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { PaymentCreateForm, VehicleServicePageHeader } from '../components/VehicleServiceComponents';

export function ServicePaymentCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/payments"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Record service payment inputs only. Backend previews allocation and owns all balances and finance effects."
                title="New Service Payment"
            />
            <PaymentCreateForm />
        </div>
    );
}
