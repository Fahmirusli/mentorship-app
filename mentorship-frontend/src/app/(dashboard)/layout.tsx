'use client';
import React, { useEffect, useState } from 'react';
import { Sidebar } from '@/components/dashboard/Sidebar';
import { api } from '@/lib/api';
import { authService } from '@/lib/auth';

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  // Determine role dynamically from auth state
  const [userRole, setUserRole] = useState<'mentee' | 'mentor'>('mentee');
  const [isClient, setIsClient] = useState(false); // To avoid hydration mismatch

  useEffect(() => {
    setIsClient(true);
    
    // 1. Setup User Role
    const user = authService.getCurrentUser();
    if (user?.role === 'mentor' || user?.role === 'mentee') {
      setUserRole(user.role);
    }

    // 2. Background Location Update (New Feature)
    if (authService.getToken() && navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          try {
            await api.post('/user/location', {
              latitude: position.coords.latitude,
              longitude: position.coords.longitude,
            });
            console.log("Background location updated silently.");
          } catch (error) {
            console.error("Failed to update location silently", error);
          }
        },
        (error) => {
          console.log("User denied location permission on web.");
        }
      );
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