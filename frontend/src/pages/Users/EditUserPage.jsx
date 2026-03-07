import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Pencil, ArrowLeft } from 'lucide-react'

import { usersApi } from '../../api/usersApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'
import Select from '../../components/UI/Select'
import LoadingSpinner from '../../components/UI/LoadingSpinner'

const schema = z.object({
  name: z.string().min(1, 'Name is required'),
  email: z.string().min(1, 'Email is required').email('Invalid email address'),
  role: z.enum(['admin', 'manager', 'staff', 'viewer'], {
    errorMap: () => ({ message: 'Please select a role' }),
  }),
})

const ROLE_OPTIONS = [
  { value: 'admin', label: 'Admin' },
  { value: 'manager', label: 'Manager' },
  { value: 'staff', label: 'Staff' },
  { value: 'viewer', label: 'Viewer' },
]

export default function EditUserPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['users', id],
    queryFn: () => usersApi.getUserById(id),
    enabled: !!id,
  })

  const user = data?.data?.data ?? data?.data ?? null

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '', role: 'staff' },
  })

  useEffect(() => {
    if (user) {
      reset({ name: user.name ?? '', email: user.email ?? '', role: user.role ?? 'staff' })
    }
  }, [user, reset])

  const mutation = useMutation({
    mutationFn: (data) => usersApi.updateUser(id, data),
    onSuccess: () => {
      toast.success('User updated successfully!')
      queryClient.invalidateQueries({ queryKey: ['users'] })
      navigate('/users')
    },
    onError: (err) => {
      const responseData = err?.response?.data
      const msg =
        responseData?.message ??
        (responseData?.errors
          ? Object.values(responseData.errors).flat().join(' ')
          : null) ??
        'Failed to update user'
      toast.error(msg)
    },
  })

  function onSubmit(values) {
    mutation.mutate(values)
  }

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <LoadingSpinner size="lg" text="Loading user…" />
      </div>
    )
  }

  if (isError || !user) {
    return (
      <div className="text-center py-20">
        <p className="text-red-600 font-medium">Failed to load user.</p>
        <Button variant="secondary" className="mt-4" onClick={() => navigate('/users')}>
          Back to Users
        </Button>
      </div>
    )
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
            <Pencil className="w-6 h-6 text-blue-600" />
            Edit User
          </h1>
          <p className="mt-1 text-sm text-gray-500">Update user #{id}</p>
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

          <Select
            label="Role"
            name="role"
            options={ROLE_OPTIONS}
            placeholder="Select a role"
            error={errors.role?.message}
            className="sm:w-1/2"
            {...register('role')}
          />

          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => navigate('/users')}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" loading={mutation.isPending}>
              <Pencil className="w-4 h-4" />
              Save Changes
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
