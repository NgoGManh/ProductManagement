import { useEffect, useState } from 'react';

import { useAuthStore } from '@/stores/authStore';

interface UseAuthGuardOptions {
  requiredRole?: string;
  redirectTo?: string;
  redirectToUnauthorized?: string;
}

export function useAuthGuard(options: UseAuthGuardOptions = {}) {
  const { requiredRole, redirectTo = '/login', redirectToUnauthorized = '/unauthorized' } = options;

  const { user, isAuthenticated, isLoading, checkAuth } = useAuthStore();
  const [isChecking, setIsChecking] = useState(true);

  useEffect(() => {
    const verifyAuth = async () => {
      setIsChecking(true);
      await checkAuth();
      setIsChecking(false);
    };

    verifyAuth();
  }, [checkAuth]);

  useEffect(() => {
    if (isChecking || isLoading) {
      return;
    }

    // Redirect to login if not authenticated
    if (!isAuthenticated) {
      window.location.href = redirectTo;
      return;
    }

    // Check role if required
    if (requiredRole && user) {
      const hasRole = user.roles.some((role) => role.name === requiredRole);
      if (!hasRole) {
        window.location.href = redirectToUnauthorized;
        return;
      }
    }
  }, [
    isAuthenticated,
    isLoading,
    isChecking,
    user,
    requiredRole,
    redirectTo,
    redirectToUnauthorized,
  ]);

  return {
    user,
    isAuthenticated,
    isLoading: isLoading || isChecking,
    hasRequiredRole: requiredRole
      ? (user?.roles.some((role) => role.name === requiredRole) ?? false)
      : true,
  };
}
