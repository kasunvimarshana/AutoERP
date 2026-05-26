import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { accessApi } from './api';
import type {
    ChangePasswordPayload,
    PermissionListFilters,
    ProfilePayload,
    RoleListFilters,
    RolePayload,
    SyncRolePermissionsPayload,
    UserListFilters,
    UserPayload,
} from './types';

const accessKeys = {
    users: ['users'] as const,
    userLists: () => [...accessKeys.users, 'list'] as const,
    userList: (filters: UserListFilters) => [...accessKeys.userLists(), filters] as const,
    userDetails: () => [...accessKeys.users, 'detail'] as const,
    userDetail: (userId: number, include?: string) => [...accessKeys.userDetails(), userId, include ?? ''] as const,
    roles: ['roles'] as const,
    roleLists: () => [...accessKeys.roles, 'list'] as const,
    roleList: (filters: RoleListFilters) => [...accessKeys.roleLists(), filters] as const,
    roleDetails: () => [...accessKeys.roles, 'detail'] as const,
    roleDetail: (roleId: number) => [...accessKeys.roleDetails(), roleId] as const,
    permissions: ['permissions'] as const,
    permissionList: (filters: PermissionListFilters) => [...accessKeys.permissions, filters] as const,
    profile: ['profile'] as const,
};

export function useUsers(filters: UserListFilters) {
    return useQuery({
        queryKey: accessKeys.userList(filters),
        queryFn: () => accessApi.listUsers(filters),
    });
}

export function useUser(userId: number, include?: string, enabled = true) {
    return useQuery({
        queryKey: accessKeys.userDetail(userId, include),
        queryFn: () => accessApi.getUser(userId, include),
        enabled,
    });
}

export function useCreateUser() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UserPayload) => accessApi.createUser(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.userLists() });
        },
    });
}

export function useUpdateUser(userId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: Partial<UserPayload>) => accessApi.updateUser(userId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.userDetail(userId) });
            void queryClient.invalidateQueries({ queryKey: accessKeys.userLists() });
        },
    });
}

export function useDeleteUser() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (userId: number) => accessApi.deleteUser(userId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.userLists() });
        },
    });
}

export function useRoles(filters: RoleListFilters) {
    return useQuery({
        queryKey: accessKeys.roleList(filters),
        queryFn: () => accessApi.listRoles(filters),
    });
}

export function useRole(roleId: number, enabled = true) {
    return useQuery({
        queryKey: accessKeys.roleDetail(roleId),
        queryFn: () => accessApi.getRole(roleId),
        enabled,
    });
}

export function useCreateRole() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: RolePayload) => accessApi.createRole(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.roleLists() });
        },
    });
}

export function useSyncRolePermissions(roleId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: SyncRolePermissionsPayload) => accessApi.syncRolePermissions(roleId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.roleDetail(roleId) });
            void queryClient.invalidateQueries({ queryKey: accessKeys.roleLists() });
        },
    });
}

export function useDeleteRole() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (roleId: number) => accessApi.deleteRole(roleId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.roleLists() });
        },
    });
}

export function usePermissions(filters: PermissionListFilters) {
    return useQuery({
        queryKey: accessKeys.permissionList(filters),
        queryFn: () => accessApi.listPermissions(filters),
    });
}

export function useProfile() {
    return useQuery({
        queryKey: accessKeys.profile,
        queryFn: () => accessApi.getProfile(),
    });
}

export function useUpdateProfile() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProfilePayload) => accessApi.updateProfile(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: accessKeys.profile });
            void queryClient.invalidateQueries({ queryKey: accessKeys.userDetails() });
        },
    });
}

export function useChangePassword() {
    return useMutation({
        mutationFn: (payload: ChangePasswordPayload) => accessApi.changePassword(payload),
    });
}
