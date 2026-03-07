import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

const LOCAL_KEYS = {
  AUTH_TOKEN: 'auth_token',
  REFRESH_TOKEN: 'refresh_token',
  TENANT_ID: 'tenant_id',
}

const useAuthStore = create(
  persist(
    (set) => ({
      // ── State ──────────────────────────────────────────────────────────────
      user: null,
      token: null,
      refreshToken: null,
      tenantId: null,
      isAuthenticated: false,

      // ── Actions ───────────────────────────────────────────────────────────

      /**
       * Called after a successful login.
       * Stores credentials in Zustand state and mirrors them into localStorage
       * so the Axios interceptor can pick them up without importing the store.
       */
      login: (user, token, refreshToken, tenantId) => {
        localStorage.setItem(LOCAL_KEYS.AUTH_TOKEN, token)
        localStorage.setItem(LOCAL_KEYS.REFRESH_TOKEN, refreshToken)
        if (tenantId) {
          localStorage.setItem(LOCAL_KEYS.TENANT_ID, tenantId)
        }

        set({
          user,
          token,
          refreshToken,
          tenantId: tenantId ?? null,
          isAuthenticated: true,
        })
      },

      /**
       * Called on explicit logout or when the token refresh flow fails.
       * Clears all auth state from Zustand and localStorage.
       */
      logout: () => {
        localStorage.removeItem(LOCAL_KEYS.AUTH_TOKEN)
        localStorage.removeItem(LOCAL_KEYS.REFRESH_TOKEN)
        localStorage.removeItem(LOCAL_KEYS.TENANT_ID)

        set({
          user: null,
          token: null,
          refreshToken: null,
          tenantId: null,
          isAuthenticated: false,
        })
      },

      /**
       * Update user profile data in store (e.g. after a profile edit).
       * @param {Object} user
       */
      setUser: (user) => set({ user }),

      /**
       * Switch the active tenant.
       * @param {string} tenantId
       */
      setTenant: (tenantId) => {
        if (tenantId) {
          localStorage.setItem(LOCAL_KEYS.TENANT_ID, tenantId)
        } else {
          localStorage.removeItem(LOCAL_KEYS.TENANT_ID)
        }
        set({ tenantId })
      },

      /**
       * Silently refresh the access token in state (called by the Axios
       * interceptor after a successful /auth/refresh response).
       * @param {string} token
       * @param {string} [newRefreshToken]
       */
      updateToken: (token, newRefreshToken) => {
        localStorage.setItem(LOCAL_KEYS.AUTH_TOKEN, token)
        if (newRefreshToken) {
          localStorage.setItem(LOCAL_KEYS.REFRESH_TOKEN, newRefreshToken)
        }
        set((state) => ({
          token,
          refreshToken: newRefreshToken ?? state.refreshToken,
        }))
      },
    }),
    {
      name: 'auth_store',
      storage: createJSONStorage(() => localStorage),
      // Only persist what is necessary; sensitive tokens are also mirrored to
      // dedicated localStorage keys so the Axios interceptor can read them
      // without importing this store (avoids circular dependencies).
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        refreshToken: state.refreshToken,
        tenantId: state.tenantId,
        isAuthenticated: state.isAuthenticated,
      }),
    },
  ),
)

export default useAuthStore
