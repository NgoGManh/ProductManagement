import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

const AUTH_STORAGE_KEY = 'auth-storage';

// Helper function to get token from Zustand persist storage
function getTokenFromStorage(): string | null {
  try {
    const stored = localStorage.getItem(AUTH_STORAGE_KEY);
    if (stored) {
      const parsed = JSON.parse(stored);
      return parsed.state?.token || null;
    }
  } catch {
    // Invalid storage format, return null
  }
  return null;
}

// Helper function to clear auth storage
function clearAuthStorage(): void {
  localStorage.removeItem(AUTH_STORAGE_KEY);
}

// Helper function to update token in Zustand persist storage
// This ensures the store state is updated properly
function updateTokenInStorage(token: string, user: any): void {
  try {
    const stored = localStorage.getItem(AUTH_STORAGE_KEY);
    const currentState = stored ? JSON.parse(stored) : { state: {}, version: 0 };
    currentState.state = {
      ...currentState.state,
      token,
      user,
      isAuthenticated: true,
    };
    localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(currentState));
  } catch (error) {
    console.error('Error updating auth storage:', error);
  }
}

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 10000,
});

// Request interceptor: Add token to headers
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = getTokenFromStorage();
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor: Handle errors and token expiration
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & {
      _retry?: boolean;
    };

    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      const isLoginPage = window.location.pathname === '/login';

      // Don't redirect if we're already on the login page
      if (isLoginPage) {
        return Promise.reject(error);
      }

      // Check if this is a token refresh attempt that failed
      if (originalRequest.url?.includes('/auth/refresh')) {
        // Refresh failed, clear storage and redirect
        clearAuthStorage();
        window.location.href = '/login';
        return Promise.reject(error);
      }

      // Try to refresh token if this is the first retry
      if (!originalRequest._retry) {
        originalRequest._retry = true;

        try {
          // Try to refresh token
          const refreshResponse = await api.post('/auth/refresh');
          if (refreshResponse.data.status === 'success') {
            const { access_token, user } = refreshResponse.data;
            // Update Zustand persist storage
            updateTokenInStorage(access_token, user);

            // Retry the original request with new token
            if (originalRequest.headers) {
              originalRequest.headers.Authorization = `Bearer ${access_token}`;
            }
            return api(originalRequest);
          }
        } catch (refreshError) {
          // Refresh failed, clear storage and redirect
          clearAuthStorage();
          window.location.href = '/login';
          return Promise.reject(refreshError);
        }
      } else {
        // Already retried, clear storage and redirect
        clearAuthStorage();
        window.location.href = '/login';
        return Promise.reject(error);
      }
    }

    return Promise.reject(error);
  }
);

export default api;
