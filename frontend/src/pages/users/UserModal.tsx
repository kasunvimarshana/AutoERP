import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { usersApi } from '../../api/users';
import { User } from '../../types';
import Modal from '../../components/ui/Modal';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Button from '../../components/ui/Button';
import toast from 'react-hot-toast';

const userFormSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Invalid email address'),
  role: z.string().min(1, 'Role is required'),
  password: z.string().min(8, 'Password must be at least 8 characters').optional().or(z.literal('')),
});

type UserFormData = z.infer<typeof userFormSchema>;

interface UserModalProps {
  isOpen: boolean;
  onClose: () => void;
  editingUser: User | null;
}

const ROLE_OPTIONS = [
  { value: 'admin', label: 'Admin' },
  { value: 'manager', label: 'Manager' },
  { value: 'staff', label: 'Staff' },
  { value: 'viewer', label: 'Viewer' },
];

const UserModal: React.FC<UserModalProps> = ({ isOpen, onClose, editingUser }) => {
  const queryClient = useQueryClient();
  const isEdit = !!editingUser;

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<UserFormData>({
    resolver: zodResolver(userFormSchema),
    defaultValues: { name: '', email: '', role: 'staff', password: '' },
  });

  useEffect(() => {
    if (editingUser) {
      reset({ name: editingUser.name, email: editingUser.email, role: editingUser.role, password: '' });
    } else {
      reset({ name: '', email: '', role: 'staff', password: '' });
    }
  }, [editingUser, reset]);

  const createMutation = useMutation({
    mutationFn: (data: UserFormData) =>
      usersApi.create({ name: data.name, email: data.email, role: data.role, password: data.password! }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('User created successfully');
      onClose();
    },
    onError: (err: any) => {
      toast.error(err.response?.data?.message || 'Failed to create user');
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: UserFormData) =>
      usersApi.update(editingUser!.id, { name: data.name, email: data.email, role: data.role }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('User updated successfully');
      onClose();
    },
    onError: (err: any) => {
      toast.error(err.response?.data?.message || 'Failed to update user');
    },
  });

  const onSubmit = (data: UserFormData) => {
    if (isEdit) {
      updateMutation.mutate(data);
    } else {
      createMutation.mutate(data);
    }
  };

  const isPending = createMutation.isPending || updateMutation.isPending;

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEdit ? 'Edit User' : 'Add New User'}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={isPending}>
            Cancel
          </Button>
          <Button onClick={handleSubmit(onSubmit)} isLoading={isPending}>
            {isEdit ? 'Save Changes' : 'Create User'}
          </Button>
        </>
      }
    >
      <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
        <Input
          label="Full Name"
          placeholder="John Doe"
          required
          error={errors.name?.message}
          {...register('name')}
        />
        <Input
          label="Email Address"
          type="email"
          placeholder="user@example.com"
          required
          error={errors.email?.message}
          {...register('email')}
        />
        <Select
          label="Role"
          required
          options={ROLE_OPTIONS}
          error={errors.role?.message}
          {...register('role')}
        />
        {!isEdit && (
          <Input
            label="Password"
            type="password"
            placeholder="Min. 8 characters"
            required
            error={errors.password?.message}
            {...register('password')}
          />
        )}
      </form>
    </Modal>
  );
};

export default UserModal;
