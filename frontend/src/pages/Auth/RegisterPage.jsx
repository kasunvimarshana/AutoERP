import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { Package2, UserPlus } from 'lucide-react'

import { authApi } from '../../api/authApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'

const schema = z
  .object({
    name: z.string().min(1, 'Name is required'),
    email: z.string().min(1, 'Email is required').email('Invalid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
    tenant_id: z
      .string()
      .min(1, 'Tenant ID is required')
      .refine((v) => !isNaN(Number(v)) && Number(v) > 0, 'Tenant ID must be a positive number'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

export default function RegisterPage() {
  const navigate = useNavigate()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      tenant_id: '',
    },
  })

  async function onSubmit(values) {
    try {
      await authApi.register({
        name: values.name,
        email: values.email,
        password: values.password,
        password_confirmation: values.password_confirmation,
        tenant_id: Number(values.tenant_id),
      })
      toast.success('Account created! Please sign in.')
      navigate('/login')
    } catch (err) {
      const data = err?.response?.data
      const msg =
        data?.message ??
        (data?.errors ? Object.values(data.errors).flat().join(' ') : null) ??
        'Registration failed. Please try again.'
      toast.error(msg)
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="bg-blue-600 rounded-2xl p-3 shadow-lg mb-4">
            <Package2 className="w-10 h-10 text-white" />
          </div>
          <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">InventoryOS</h1>
          <p className="mt-1 text-sm text-gray-500">Multi-tenant Inventory Management</p>
        </div>

        {/* Card */}
        <div className="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
          <h2 className="text-xl font-semibold text-gray-800 mb-6">Create your account</h2>

          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
            <Input
              label="Full Name"
              name="name"
              type="text"
              placeholder="Jane Smith"
              autoComplete="name"
              error={errors.name?.message}
              {...register('name')}
            />

            <Input
              label="Email Address"
              name="email"
              type="email"
              placeholder="you@company.com"
              autoComplete="email"
              error={errors.email?.message}
              {...register('email')}
            />

            <Input
              label="Password"
              name="password"
              type="password"
              placeholder="Minimum 8 characters"
              autoComplete="new-password"
              error={errors.password?.message}
              {...register('password')}
            />

            <Input
              label="Confirm Password"
              name="password_confirmation"
              type="password"
              placeholder="Re-enter your password"
              autoComplete="new-password"
              error={errors.password_confirmation?.message}
              {...register('password_confirmation')}
            />

            <Input
              label="Tenant ID"
              name="tenant_id"
              type="text"
              placeholder="e.g. 1"
              autoComplete="off"
              error={errors.tenant_id?.message}
              {...register('tenant_id')}
            />

            <Button
              type="submit"
              variant="primary"
              size="lg"
              loading={isSubmitting}
              className="w-full"
            >
              <UserPlus className="w-4 h-4" />
              Create Account
            </Button>
          </form>

          <p className="mt-6 text-center text-sm text-gray-500">
            Already have an account?{' '}
            <Link
              to="/login"
              className="font-medium text-blue-600 hover:text-blue-700 hover:underline"
            >
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}
