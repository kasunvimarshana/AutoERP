import { assignedCrew, crewMembers, orderLines, subItems, vehicleOptions } from '../mockData';

export async function fetchJobCardReferenceData() {
    return Promise.resolve({ vehicleOptions, crewMembers, subItems, orderLines, assignedCrew });
}
