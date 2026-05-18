'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Loader, CheckCircle, XCircle } from 'lucide-react';
import { Suspense } from 'react';

function CallbackContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('Processing login...');

  useEffect(() => {
    const token = searchParams.get('token');
    const userStr = searchParams.get('user');
    const error = searchParams.get('error');

    if (error) {
      setStatus('error');
      setMessage(error === 'oauth_not_configured' ? 'OAuth is not configured.' : 'Login failed. Please try again.');
      setTimeout(() => router.push('/login'), 3000);
      return;
    }

    if (token && userStr) {
      try {
        const user = JSON.parse(decodeURIComponent(userStr));
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        
        setStatus('success');
        setMessage('Login successful! Redirecting...');

        setTimeout(() => {
          if (user.role === 'mentor') {
            router.push('/mentor/dashboard');
          } else if (user.role === 'admin') {
            router.push('/admin/dashboard');
          } else {
            router.push('/mentee/dashboard');
          }
        }, 1500);
      } catch (e) {
        setStatus('error');
        setMessage('Failed to process login data.');
        setTimeout(() => router.push('/login'), 3000);
      }
    } else {
      setStatus('error');
      setMessage('Invalid callback data.');
      setTimeout(() => router.push('/login'), 3000);
    }
  }, [router, searchParams]);

  return (
    <div className="min-h-screen bg-[#0a0a0f] flex items-center justify-center">
      <div className="text-center animate-fade-in-up">
        <div className="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-xl shadow-indigo-500/20">
          {status === 'loading' && <Loader className="w-8 h-8 text-white animate-spin" />}
          {status === 'success' && <CheckCircle className="w-8 h-8 text-white" />}
          {status === 'error' && <XCircle className="w-8 h-8 text-white" />}
        </div>
        <p className="text-white text-lg font-semibold">{message}</p>
        <p className="text-gray-500 text-sm mt-2">Please wait...</p>
      </div>
    </div>
  );
}

export default function AuthCallback() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-[#0a0a0f] flex items-center justify-center">
        <Loader className="w-8 h-8 text-indigo-500 animate-spin" />
      </div>
    }>
      <CallbackContent />
    </Suspense>
  );
}
