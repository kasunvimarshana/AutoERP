import { mockCollectionResponse } from '../../../services/mock/mockResponse';
import { settingRecords } from '../mock/settingsMock';

export const settingsApi = {
    list: () => mockCollectionResponse(settingRecords),
};
