import { useLocation, useParams } from 'react-router-dom';
import { VehicleCreatePage } from './VehicleCreatePage';
import { VehicleDetailPage } from './VehicleDetailPage';
import { VehicleEditPage } from './VehicleEditPage';
import { VehicleListPage } from './VehicleListPage';

export function VehiclePage() {
    const { id } = useParams();
    const location = useLocation();

    if (location.pathname.endsWith('/new')) {
        return <VehicleCreatePage />;
    }

    if (location.pathname.endsWith('/edit')) {
        return <VehicleEditPage />;
    }

    if (id) {
        return <VehicleDetailPage />;
    }

    return <VehicleListPage />;
}
