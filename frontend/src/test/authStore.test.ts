import { renderHook, act } from '@testing-library/react';
import { useAuthStore } from '../store/authStore';

// Minimal localStorage mock
const mockStorage: Record<string, string> = {};
Object.defineProperty(window, 'localStorage', {
  value: {
    getItem: (key: string) => mockStorage[key] ?? null,
    setItem: (key: string, value: string) => { mockStorage[key] = value; },
    removeItem: (key: string) => { delete mockStorage[key]; },
    clear: () => { Object.keys(mockStorage).forEach(k => delete mockStorage[k]); },
  },
  writable: true,
});

describe('authStore', () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it('starts unauthenticated when localStorage is empty', () => {
    const { result } = renderHook(() => useAuthStore());
    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.user).toBeNull();
  });

  it('sets auth state and persists to localStorage', () => {
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth(
        { id: '1', name: 'Alice', email: 'alice@test.com', tenantId: 'tenant-1' },
        'tok-abc'
      );
    });

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.token).toBe('tok-abc');
    expect(window.localStorage.getItem('auth_token')).toBe('tok-abc');
  });

  it('clears auth state and removes from localStorage', () => {
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth(
        { id: '1', name: 'Alice', email: 'alice@test.com', tenantId: 'tenant-1' },
        'tok-abc'
      );
    });

    act(() => { result.current.clearAuth(); });

    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.token).toBeNull();
    expect(window.localStorage.getItem('auth_token')).toBeNull();
  });
});
