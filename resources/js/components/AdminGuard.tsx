import React, { useEffect, useState } from 'react';

import { useAuthStore } from '@/stores/authStore';

interface AdminGuardProps {
  children: React.ReactNode;
  fallback?: React.ReactNode;
  unauthorizedFallback?: React.ReactNode;
}

export function AdminGuard({ children, fallback, unauthorizedFallback }: AdminGuardProps) {
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
    if (!isChecking && !isLoading) {
      if (!isAuthenticated) {
        window.location.href = '/login';
        return;
      }

      // Check if user has admin role
      const hasAdminRole = user?.roles.some((role) => role.name === 'admin') ?? false;
      if (!hasAdminRole) {
        window.location.href = '/unauthorized';
        return;
      }
    }
  }, [isAuthenticated, isLoading, isChecking, user]);

  if (isChecking || isLoading) {
    return (
      fallback || (
        <div className="flex items-center justify-center min-h-screen">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
            <p className="mt-4 text-muted-foreground">Loading...</p>
          </div>
        </div>
      )
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  const hasAdminRole = user?.roles.some((role) => role.name === 'admin') ?? false;
  if (!hasAdminRole) {
    return (
      unauthorizedFallback || (
        <div className="flex items-center justify-center min-h-screen">
          <div className="text-center">
            <h1 className="text-2xl font-bold text-destructive">Access Denied</h1>
            <p className="mt-2 text-muted-foreground">
              You don't have permission to access this page.
            </p>
          </div>
        </div>
      )
    );
  }

  return <>{children}</>;
}
