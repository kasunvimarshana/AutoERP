import { create } from 'zustand';

export const useAuthStore = create((set) => ({
  user: null,
  roles: [],
  setUser:  (user)  => set({ user }),
  setRoles: (roles) => set({ roles }),
  clear:    ()      => set({ user: null, roles: [] }),
}));
