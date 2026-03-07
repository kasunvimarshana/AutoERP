import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Search, Pencil, Trash2 } from 'lucide-react';
import { usersApi } from '../../api/users';
import { usePagination } from '../../hooks/usePagination';
import { User } from '../../types';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Table, { Column } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import Badge from '../../components/ui/Badge';
import UserModal from './UserModal';
import toast from 'react-hot-toast';
import { useAuth } from '../../contexts/AuthContext';

const UsersPage: React.FC = () => {
  const queryClient = useQueryClient();
  const { hasRole } = useAuth();
  const { page, perPage, setPage } = usePagination(15);
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['users', { page, perPage, search }],
    queryFn: () => usersApi.list({ page, per_page: perPage, search }),
  });

  const deleteMutation = useMutation({
    mutationFn: usersApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('User deleted successfully');
      setDeleteConfirm(null);
    },
    onError: (err: any) => {
      toast.error(err.response?.data?.message || 'Failed to delete user');
    },
  });

  const handleEdit = (user: User) => {
    setEditingUser(user);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingUser(null);
  };

  const getRoleVariant = (role: string): 'success' | 'info' | 'warning' | 'gray' => {
    const map: Record<string, 'success' | 'info' | 'warning' | 'gray'> = {
      admin: 'success',
      manager: 'info',
      staff: 'warning',
      viewer: 'gray',
    };
    return map[role] || 'gray';
  };

  const columns: Column<User>[] = [
    {
      key: 'name',
      header: 'Name',
      render: (row) => (
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-sm font-semibold shrink-0">
            {row.name.charAt(0).toUpperCase()}
          </div>
          <div>
            <p className="font-medium text-gray-900">{row.name}</p>
            <p className="text-xs text-gray-500">{row.email}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'role',
      header: 'Role',
      render: (row) => (
        <Badge variant={getRoleVariant(row.role)} className="capitalize">
          {row.role}
        </Badge>
      ),
    },
    {
      key: 'permissions',
      header: 'Permissions',
      render: (row) => (
        <span className="text-sm text-gray-600">{row.permissions?.length || 0} permissions</span>
      ),
    },
    {
      key: 'actions',
      header: 'Actions',
      className: 'text-right',
      render: (row) => (
        <div className="flex items-center justify-end gap-2">
          <Button variant="ghost" size="sm" leftIcon={<Pencil className="h-3.5 w-3.5" />} onClick={() => handleEdit(row)}>
            Edit
          </Button>
          {hasRole('admin') && (
            deleteConfirm === row.id ? (
              <div className="flex items-center gap-1">
                <Button
                  variant="danger"
                  size="sm"
                  isLoading={deleteMutation.isPending}
                  onClick={() => deleteMutation.mutate(row.id)}
                >
                  Confirm
                </Button>
                <Button variant="secondary" size="sm" onClick={() => setDeleteConfirm(null)}>
                  Cancel
                </Button>
              </div>
            ) : (
              <Button
                variant="ghost"
                size="sm"
                className="text-red-600 hover:bg-red-50"
                leftIcon={<Trash2 className="h-3.5 w-3.5" />}
                onClick={() => setDeleteConfirm(row.id)}
              >
                Delete
              </Button>
            )
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Users</h1>
          <p className="text-sm text-gray-500 mt-1">Manage team members and their access</p>
        </div>
        <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setIsModalOpen(true)}>
          Add User
        </Button>
      </div>

      <Card noPadding>
        <div className="p-4 border-b border-gray-100">
          <Input
            placeholder="Search users by name or email..."
            leftAddon={<Search className="h-4 w-4" />}
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="max-w-sm"
          />
        </div>
        <Table
          columns={columns}
          data={data?.data || []}
          isLoading={isLoading}
          rowKey={(row) => row.id}
          emptyMessage="No users found. Add your first team member."
        />
        {data && data.last_page > 1 && (
          <Pagination
            currentPage={data.current_page}
            lastPage={data.last_page}
            total={data.total}
            perPage={data.per_page}
            onPageChange={setPage}
          />
        )}
      </Card>

      <UserModal
        isOpen={isModalOpen}
        onClose={handleCloseModal}
        editingUser={editingUser}
      />
    </div>
  );
};

export default UsersPage;
