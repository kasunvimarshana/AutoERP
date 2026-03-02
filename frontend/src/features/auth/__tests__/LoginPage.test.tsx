import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { useAuthStore } from '@/store/authStore'
import LoginPage from '../LoginPage'

// Mock the authApi module so no real HTTP requests are made
vi.mock('@/api/auth', () => ({
  default: {
    login: vi.fn(),
    me: vi.fn(),
    logout: vi.fn(),
    refresh: vi.fn(),
  },
}))

import authApi from '@/api/auth'

describe('LoginPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    useAuthStore.setState({ user: null, isAuthenticated: false, isLoading: false })
    localStorage.clear()
  })

  it('renders the login form with all required fields', () => {
    render(<LoginPage />)
    expect(screen.getByLabelText(/Organisation/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Email/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Password/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Sign in/i })).toBeInTheDocument()
  })

  it('shows error message on failed login', async () => {
    vi.mocked(authApi.login).mockRejectedValueOnce(new Error('Unauthorized'))

    render(<LoginPage />)

    fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'user@test.com' } })
    fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'wrongpassword' } })
    fireEvent.click(screen.getByRole('button', { name: /Sign in/i }))

    await waitFor(() => {
      expect(screen.getByRole('alert')).toBeInTheDocument()
    })
    expect(screen.getByRole('alert')).toHaveTextContent(/Invalid credentials/i)
  })

  it('stores access_token on successful login and calls /me', async () => {
    vi.mocked(authApi.login).mockResolvedValueOnce({
      data: { success: true, message: 'OK', data: { access_token: 'test-jwt', token_type: 'bearer', expires_in: 3600 } },
    } as never)
    vi.mocked(authApi.me).mockResolvedValueOnce({
      data: {
        success: true, message: 'OK',
        data: { id: 1, tenant_id: 1, name: 'Test User', email: 'user@test.com', roles: ['admin'], permissions: [] },
      },
    } as never)

    // Suppress window.location.href assignment in jsdom
    const originalLocation = window.location
    Object.defineProperty(window, 'location', { value: { href: '' }, writable: true })

    render(<LoginPage />)

    fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'user@test.com' } })
    fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'secret123' } })
    fireEvent.click(screen.getByRole('button', { name: /Sign in/i }))

    await waitFor(() => expect(authApi.me).toHaveBeenCalledTimes(1))
    expect(localStorage.getItem('access_token')).toBe('test-jwt')

    // Restore
    Object.defineProperty(window, 'location', { value: originalLocation, writable: true })
  })

  it('disables the submit button while a request is in flight', async () => {
    // Never resolves — keeps the button in "submitting" state
    vi.mocked(authApi.login).mockReturnValueOnce(new Promise(() => {}))

    render(<LoginPage />)

    fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'u@e.com' } })
    fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'pass' } })
    fireEvent.click(screen.getByRole('button', { name: /Sign in/i }))

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Signing in/i })).toBeDisabled()
    })
  })
})
