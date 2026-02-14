import api from './api';

export const login = async (credentials) => {
    return await api.post('/auth/login', credentials);
};

export const register = async (data) => {
    return await api.post('/auth/register', data);
};

export const logout = async () => {
    return await api.post('/auth/logout');
};

export const getMe = async () => {
    return await api.get('/auth/me');
};

export const refreshToken = async () => {
    return await api.post('/auth/refresh');
};
