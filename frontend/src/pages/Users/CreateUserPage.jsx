import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { UserPlus, ArrowLeft } from 'lucide-react'

import { usersApi } from '../../api/usersApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'
import Select from '../../components/UI/Select'

const schema = z
  .object({
    name: z.string().min(1, 'Name is required'),
    email: z.string().min(1, 'Email is required').email('Invalid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
    role: z.enum(['admin', 'manager', 'staff', 'viewer'], {
      errorMap: () => ({ message: 'Please select a role' }),
    }),
    tenant_id: z
      .string()
      .min(1, 'Tenant ID is required')
      .refine((v) => !isNaN(Number(v)) && Number(v) > 0, 'Must be a positive number'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

const ROLE_OPTIONS = [
  { value: 'admin', label: 'Admin' },
  { value: 'manager', label: 'Manager' },
  { value: 'staff', label: 'Staff' },
  { value: 'viewer', label: 'Viewer' },
]

export default function CreateUserPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      role: 'staff',
      tenant_id: '',
    },
  })

  const mutation = useMutation({
    mutationFn: (data) => usersApi.createUser(data),
    onSuccess: () => {
      toast.success('User created successfully!')
      queryClient.invalidateQueries({ queryKey: ['users'] })
      navigate('/users')
    },
    onError: (err) => {
      const data = err?.response?.data
      const msg =
        data?.message ??
        (data?.errors ? Object.values(data.errors).flat().join(' ') : null) ??
        'Failed to create user'
      toast.error(msg)
    },
  })

  function onSubmit(values) {
    mutation.mutate({
      name: values.name,
      email: values.email,
      password: values.password,
      password_confirmation: values.password_confirmation,
      role: values.role,
      tenant_id: Number(values.tenant_id),
    })
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => navigate('/users')}>
          <ArrowLeft className="w-4 h-4" />
          Back
        </Button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <UserPlus className="w-6 h-6 text-blue-600" />
            Create User
          </h1>
          <p className="mt-1 text-sm text-gray-500">Add a new user to the system</p>
        </div>
      </div>

      {/* Form card */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input
              label="Full Name"
              name="name"
              type="text"
              placeholder="Jane Smith"
              error={errors.name?.message}
              {...register('name')}
            />
            <Input
              label="Email Address"
              name="email"
              type="email"
              placeholder="jane@company.com"
              error={errors.email?.message}
              {...register('email')}
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input
              label="Password"
              name="password"
              type="password"
              placeholder="Minimum 8 characters"
              error={errors.password?.message}
              {...register('password')}
            />
            <Input
              label="Confirm Password"
              name="password_confirmation"
              type="password"
              placeholder="Re-enter password"
              error={errors.password_confirmation?.message}
              {...register('password_confirmation')}
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Select
              label="Role"
              name="role"
              options={ROLE_OPTIONS}
              placeholder="Select a role"
              error={errors.role?.message}
              {...register('role')}
            />
            <Input
              label="Tenant ID"
              name="tenant_id"
              type="text"
              placeholder="e.g. 1"
              error={errors.tenant_id?.message}
              {...register('tenant_id')}
            />
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => navigate('/users')}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" loading={mutation.isPending}>
              <UserPlus className="w-4 h-4" />
              Create User
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
