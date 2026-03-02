import { useEffect, type ReactNode } from 'react'
import { useAuthStore } from '@/store/authStore'
import authApi from '@/api/auth'

interface AuthGuardProps {
  children: ReactNode
}

/**
 * AuthGuard — wraps protected routes.
 *
 * On mount it attempts to rehydrate the authenticated user from the stored JWT.
 * If no valid token is found, it redirects to /login.
 */
export default function AuthGuard({ children }: AuthGuardProps) {
  const { isAuthenticated, isLoading, setUser, setLoading } = useAuthStore()

  useEffect(() => {
    const token = localStorage.getItem('access_token')
    if (!token) {
      setLoading(false)
      window.location.href = '/login'
      return
    }

    authApi
      .me()
      .then((res) => setUser(res.data.data))
      .catch(() => {
        localStorage.removeItem('access_token')
        localStorage.removeItem('tenant_slug')
        window.location.href = '/login'
      })
  }, [setUser, setLoading])

  if (isLoading) return <div aria-busy="true">Loading…</div>
  if (!isAuthenticated) return null

  return <>{children}</>
}
