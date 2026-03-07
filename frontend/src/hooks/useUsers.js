import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import * as api from '../api/users';

export const useUsers   = (params) =>
  useQuery({ queryKey: ['users', params], queryFn: () => api.getUsers(params) });

export const useUser    = (id) =>
  useQuery({ queryKey: ['users', id], queryFn: () => api.getUser(id), enabled: !!id });

export const useProfile = () =>
  useQuery({ queryKey: ['profile'], queryFn: api.getProfile });

export const useUpdateProfile = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: api.updateProfile,
    onSuccess: () => { qc.invalidateQueries(['profile']); toast.success('Profile updated.'); },
  });
};

export const useAssignRole = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, role }) => api.assignRole(id, role),
    onSuccess: () => { qc.invalidateQueries(['users']); toast.success('Role assigned.'); },
  });
};

export const useRevokeRole = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, role }) => api.revokeRole(id, role),
    onSuccess: () => { qc.invalidateQueries(['users']); toast.success('Role revoked.'); },
  });
};
