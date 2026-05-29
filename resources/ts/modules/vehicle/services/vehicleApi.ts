import { mockCollectionResponse, mockPreviewResponse } from '../../../services/mock/mockResponse';
import { vehicleRecords } from '../mock/vehicleMock';

export const vehicleApi = {
    list: () => mockCollectionResponse(vehicleRecords),
    previewAvailability: (vehicleId: string) => mockPreviewResponse({ vehicleId }, { note: 'Backend vehicle availability preview placeholder' }),
};
