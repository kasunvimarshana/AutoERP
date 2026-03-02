import { create } from 'zustand'
import type { User } from '@/types/auth'

interface AuthState {
  user: User | null
  isAuthenticated: boolean
  isLoading: boolean
  setUser: (user: User | null) => void
  setLoading: (loading: boolean) => void
  logout: () => void
}

/**
 * Global auth store — holds the currently authenticated user.
 *
 * Populated after a successful /auth/me call; cleared on logout.
 * Uses zustand for lightweight, outside-React-tree accessibility.
 */
export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: false,
  isLoading: true,

  setUser: (user) => set({ user, isAuthenticated: user !== null, isLoading: false }),

  setLoading: (loading) => set({ isLoading: loading }),

  logout: () => {
    localStorage.removeItem('access_token')
    localStorage.removeItem('tenant_slug')
    set({ user: null, isAuthenticated: false, isLoading: false })
  },
}))
