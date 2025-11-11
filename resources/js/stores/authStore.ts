import { create } from 'zustand';
import { persist } from 'zustand/middleware';

import api from '@/lib/api';
import type { AuthStore, LoginCredentials, User } from '@/types/auth';

const STORAGE_KEY = 'auth-storage';

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      // State
      user: null,
      token: null,
      isLoading: false,
      isAuthenticated: false,

      // Actions
      setUser: (user: User | null) => {
        set({ user, isAuthenticated: !!user && !!get().token });
      },

      setToken: (token: string | null) => {
        set({ token, isAuthenticated: !!token && !!get().user });
      },

      setLoading: (isLoading: boolean) => {
        set({ isLoading });
      },

      login: async (credentials: LoginCredentials) => {
        try {
          set({ isLoading: true });
          const response = await api.post('/auth/login', credentials);

          if (response.data.status === 'success') {
            const { access_token, user } = response.data;
            set({
              token: access_token,
              user,
              isAuthenticated: true,
              isLoading: false,
            });
          } else {
            set({ isLoading: false });
            throw new Error('Login failed. Please try again.');
          }
        } catch (error: any) {
          set({ isLoading: false });
          const message = error.response?.data?.message || 'Login failed. Please try again.';
          throw new Error(message);
        }
      },

      logout: async () => {
        try {
          // Call backend to invalidate token
          const token = get().token;
          if (token) {
            try {
              await api.post('/auth/logout');
            } catch (error) {
              // Continue with logout even if backend call fails
              console.error('Logout API call failed:', error);
            }
          }
        } finally {
          // Clear state (persist middleware will handle localStorage)
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          });
        }
      },

      checkAuth: async () => {
        const token = get().token;
        if (!token) {
          set({ user: null, token: null, isAuthenticated: false });
          return;
        }

        try {
          set({ isLoading: true });
          const response = await api.get('/auth/me');

          if (response.data.status === 'success') {
            const user = response.data.user;
            set({
              user,
              token,
              isAuthenticated: true,
              isLoading: false,
            });
          } else {
            // Invalid response, clear state
            set({
              user: null,
              token: null,
              isAuthenticated: false,
              isLoading: false,
            });
          }
        } catch (error: any) {
          // Token is invalid, clear everything
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          });
        }
      },

      refreshToken: async () => {
        try {
          const response = await api.post('/auth/refresh');
          if (response.data.status === 'success') {
            const { access_token, user } = response.data;
            set({
              token: access_token,
              user,
              isAuthenticated: true,
            });
            return { access_token, user };
          }
          throw new Error('Refresh token failed');
        } catch (error) {
          // Refresh failed, clear state
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          });
          throw error;
        }
      },
    }),
    {
      name: STORAGE_KEY,
      partialize: (state) => ({
        token: state.token,
        user: state.user,
      }),
    }
  )
);
