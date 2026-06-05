import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import { configurationRecords } from '../mock/configurationMock';

export const configurationApi = {
    list: () => mockCollectionResponse(configurationRecords),
    resolve: (key: string) => mockResponse({ key, note: 'Backend resolved configuration placeholder' }),
};
