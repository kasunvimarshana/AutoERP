import { userClient } from './apiClient';

export const getUsers      = (params) => userClient.get('/users', { params }).then(r => r.data);
export const getUser       = (id)     => userClient.get(`/users/${id}`).then(r => r.data);
export const updateUser    = (id, data) => userClient.put(`/users/${id}`, data).then(r => r.data);
export const assignRole    = (id, role) => userClient.post(`/users/${id}/roles`, { role }).then(r => r.data);
export const revokeRole    = (id, role) => userClient.delete(`/users/${id}/roles/${role}`).then(r => r.data);
export const getProfile    = ()       => userClient.get('/profile').then(r => r.data);
export const updateProfile = (data)   => userClient.put('/profile', data).then(r => r.data);
