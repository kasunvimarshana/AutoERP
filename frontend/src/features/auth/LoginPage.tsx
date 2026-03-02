import { useState, type FormEvent } from 'react'
import authApi from '@/api/auth'
import { useAuthStore } from '@/store/authStore'

/**
 * LoginPage — ERP tenant login form.
 *
 * Collects tenant_slug, email, and password.
 * On success stores the JWT and redirects to the dashboard.
 * Business logic is kept minimal: credential submission → token storage → user hydration.
 */
export default function LoginPage() {
  const setUser = useAuthStore((s) => s.setUser)
  const [tenantSlug, setTenantSlug] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    setIsSubmitting(true)

    try {
      const loginRes = await authApi.login({ email, password, tenant_slug: tenantSlug })
      const { access_token } = loginRes.data.data
      localStorage.setItem('access_token', access_token)
      if (tenantSlug) localStorage.setItem('tenant_slug', tenantSlug)

      const meRes = await authApi.me()
      setUser(meRes.data.data)

      window.location.href = '/'
    } catch {
      setError('Invalid credentials. Please try again.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="login-page">
      <form onSubmit={handleSubmit} aria-label="Login form">
        <h1>KV Enterprise ERP/CRM</h1>

        <label htmlFor="tenantSlug">Organisation</label>
        <input
          id="tenantSlug"
          type="text"
          value={tenantSlug}
          onChange={(e) => setTenantSlug(e.target.value)}
          placeholder="your-organisation"
          autoComplete="organization"
        />

        <label htmlFor="email">Email</label>
        <input
          id="email"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          autoComplete="email"
        />

        <label htmlFor="password">Password</label>
        <input
          id="password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          autoComplete="current-password"
        />

        {error && <p role="alert" className="error">{error}</p>}

        <button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Signing in…' : 'Sign in'}
        </button>
      </form>
    </div>
  )
}
