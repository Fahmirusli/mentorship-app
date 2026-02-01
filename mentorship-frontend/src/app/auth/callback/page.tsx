'use client';

import { useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Loader } from 'lucide-react';

function AuthCallbackContent() {
    const router = useRouter();
    const searchParams = useSearchParams();

    useEffect(() => {
        const token = searchParams.get('token');
        const userStr = searchParams.get('user');
        const error = searchParams.get('error');

        if (error) {
            alert('OAuth authentication failed. Please try again.');
            router.push('/login');
            return;
        }

        if (token && userStr) {
            try {
                const user = JSON.parse(decodeURIComponent(userStr));

                // Store token and user data
                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(user));

                // Redirect based on role
                if (user.role === 'mentor') {
                    router.push('/mentor/dashboard');
                } else if (user.role === 'admin') {
                    router.push('/admin/dashboard');
                } else {
                    router.push('/mentee/dashboard');
                }
            } catch (err) {
                console.error('Error parsing user data:', err);
                router.push('/login');
            }
        } else {
            router.push('/login');
        }
    }, [searchParams, router]);

    return (
        <div className="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-50 to-pink-100 flex items-center justify-center">
            <div className="text-center">
                <Loader className="w-12 h-12 animate-spin text-indigo-600 mx-auto mb-4" />
                <p className="text-gray-700 font-medium">Completing authentication...</p>
            </div>
        </div>
    );
}

export default function AuthCallback() {
    return (
        <Suspense fallback={
            <div className="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-50 to-pink-100 flex items-center justify-center">
                <div className="text-center">
                    <Loader className="w-12 h-12 animate-spin text-indigo-600 mx-auto mb-4" />
                    <p className="text-gray-700 font-medium">Loading authentication...</p>
                </div>
            </div>
        }>
            <AuthCallbackContent />
        </Suspense>
    );
}
