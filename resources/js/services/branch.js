import api from './api';

export const getBranches = async (params = {}) => {
    return await api.get('/branches', { params });
};

export const getBranch = async (id) => {
    return await api.get(`/branches/${id}`);
};

export const createBranch = async (data) => {
    return await api.post('/branches', data);
};

export const updateBranch = async (id, data) => {
    return await api.put(`/branches/${id}`, data);
};

export const deleteBranch = async (id) => {
    return await api.delete(`/branches/${id}`);
};

export const getBranchStats = async (id) => {
    return await api.get(`/branches/${id}/stats`);
};
