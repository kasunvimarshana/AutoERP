import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from '@/store/authStore'

describe('useAuthStore', () => {
  beforeEach(() => {
    useAuthStore.setState({ user: null, isAuthenticated: false, isLoading: true })
    localStorage.clear()
  })

  it('starts with no user and loading=true', () => {
    const state = useAuthStore.getState()
    expect(state.user).toBeNull()
    expect(state.isAuthenticated).toBe(false)
    expect(state.isLoading).toBe(true)
  })

  it('setUser sets user and marks authenticated', () => {
    const user = { id: 1, tenant_id: 1, name: 'Alice', email: 'alice@acme.com', roles: ['admin'], permissions: [] }
    useAuthStore.getState().setUser(user)
    const state = useAuthStore.getState()
    expect(state.user).toEqual(user)
    expect(state.isAuthenticated).toBe(true)
    expect(state.isLoading).toBe(false)
  })

  it('setUser(null) clears authentication', () => {
    useAuthStore.getState().setUser(null)
    const state = useAuthStore.getState()
    expect(state.user).toBeNull()
    expect(state.isAuthenticated).toBe(false)
  })

  it('logout clears user, isAuthenticated, and localStorage tokens', () => {
    localStorage.setItem('access_token', 'some-token')
    localStorage.setItem('tenant_slug', 'acme')
    const user = { id: 1, tenant_id: 1, name: 'Bob', email: 'bob@acme.com', roles: [], permissions: [] }
    useAuthStore.setState({ user, isAuthenticated: true, isLoading: false })

    useAuthStore.getState().logout()

    expect(useAuthStore.getState().user).toBeNull()
    expect(useAuthStore.getState().isAuthenticated).toBe(false)
    expect(localStorage.getItem('access_token')).toBeNull()
    expect(localStorage.getItem('tenant_slug')).toBeNull()
  })

  it('setLoading updates the loading flag', () => {
    useAuthStore.getState().setLoading(false)
    expect(useAuthStore.getState().isLoading).toBe(false)
    useAuthStore.getState().setLoading(true)
    expect(useAuthStore.getState().isLoading).toBe(true)
  })
})
