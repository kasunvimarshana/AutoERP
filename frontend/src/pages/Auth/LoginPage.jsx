import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'
import { Package2, LogIn } from 'lucide-react'

import { authApi } from '../../api/authApi'
import useAuthStore from '../../store/authStore'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'

const schema = z.object({
  email: z.string().min(1, 'Email is required').email('Invalid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
  tenant_id: z.string().min(1, 'Tenant ID is required'),
})

export default function LoginPage() {
  const navigate = useNavigate()
  const authLogin = useAuthStore((s) => s.login)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', tenant_id: '' },
  })

  async function onSubmit(values) {
    try {
      const res = await authApi.login({
        email: values.email,
        password: values.password,
        tenant_id: values.tenant_id,
      })
      const { token, access_token, user, refresh_token } = res.data
      const accessToken = token ?? access_token
      authLogin(user, accessToken, refresh_token, values.tenant_id)
      toast.success('Welcome back!')
      navigate('/dashboard')
    } catch (err) {
      const msg =
        err?.response?.data?.message ??
        err?.response?.data?.error ??
        'Login failed. Please check your credentials.'
      toast.error(msg)
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Logo / App name */}
        <div className="flex flex-col items-center mb-8">
          <div className="bg-blue-600 rounded-2xl p-3 shadow-lg mb-4">
            <Package2 className="w-10 h-10 text-white" />
          </div>
          <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">InventoryOS</h1>
          <p className="mt-1 text-sm text-gray-500">Multi-tenant Inventory Management</p>
        </div>

        {/* Card */}
        <div className="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
          <h2 className="text-xl font-semibold text-gray-800 mb-6">Sign in to your account</h2>

          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
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
              placeholder="••••••••"
              autoComplete="current-password"
              error={errors.password?.message}
              {...register('password')}
            />

            <Input
              label="Tenant ID"
              name="tenant_id"
              type="text"
              placeholder="your-tenant-id"
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
              <LogIn className="w-4 h-4" />
              Sign In
            </Button>
          </form>

          <p className="mt-6 text-center text-sm text-gray-500">
            Don&apos;t have an account?{' '}
            <Link
              to="/register"
              className="font-medium text-blue-600 hover:text-blue-700 hover:underline"
            >
              Create one
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}
