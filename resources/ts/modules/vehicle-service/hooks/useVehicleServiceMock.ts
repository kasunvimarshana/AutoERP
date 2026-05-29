import { assignedSubItems, crewMembers, customers, jobTypes, orderItems, products, serviceItems, statuses, subItems, supervisors, vehicles } from '../mock/vehicleServiceMock';

export function useVehicleServiceMock() {
    return {
        assignedSubItems,
        crewMembers,
        customers,
        jobTypes,
        orderItems,
        products,
        serviceItems,
        statuses,
        subItems,
        supervisors,
        vehicles,
    };
}
