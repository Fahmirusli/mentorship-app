'use client';
import React, { useEffect, useState } from 'react';
import { Sidebar } from '@/components/dashboard/Sidebar';
import { authService } from '@/lib/api';

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  // Determine role dynamically from auth state
  const [userRole, setUserRole] = useState<'mentee' | 'mentor'>('mentee');
  const [isClient, setIsClient] = useState(false); // To avoid hydration mismatch

  useEffect(() => {
    setIsClient(true);
    const user = authService.getUser();
    if (user?.role) {
      setUserRole(user.role);
    }
  }, []);

  if (!isClient) return null; // or a loading spinner

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar role={userRole} />
      <main className="pt-16">
        {children}
      </main>
    </div>
  );
}