'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Mail, Lock, Loader, Chrome, Github } from 'lucide-react';
import Link from 'next/link';
import { Logo } from '@/components/Logo';
import { API_BASE_URL } from '@/lib/api';

export default function Login() {
    const router = useRouter();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await fetch(`${API_BASE_URL}/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok) {
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));

                if (data.profile_incomplete) {
                    router.push('/profile/complete');
                    return;
                }

                // Redirect based on role
                if (data.user.role === 'mentor') {
                    router.push('/mentor/dashboard');
                } else if (data.user.role === 'admin') {
                    router.push('/admin/dashboard');
                } else {
                    router.push('/mentee/dashboard');
                }
            } else {
                setError(data.message || 'Login failed');
            }
        } catch (err: any) {
            console.error('Login Error Details:', err);
            setError(`Network error: ${err.message || 'Unknown error'}`);
        } finally {
            setLoading(false);
        }
    };

    const handleGoogleLogin = () => {
        // Redirect to backend Google OAuth
        window.location.href = `${API_BASE_URL}/auth/google`;
    };

    return (
        <div className="flex min-h-screen bg-gray-900 text-white">
            {/* Left Side: Hero Image/Gradient */}
            <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gray-900">
                {/* Background Image */}
                <img
                    src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&q=80&w=2070"
                    className="absolute inset-0 w-full h-full object-cover opacity-40 mix-blend-overlay"
                    alt="Background"
                />

                {/* Gradient Overlay */}
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-900/90 via-purple-900/80 to-gray-900/90"></div>

                {/* Content */}
                <div className="relative z-10 w-full flex flex-col justify-center px-12 lg:px-20 text-white">
                    <div className="mb-8">
                        <div className="h-12 w-12 bg-indigo-500 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/30">
                            <Logo size="md" collapsed={true} className="text-white" />
                        </div>
                        <h1 className="text-5xl font-bold mb-6 leading-tight">
                            Unlock Your Full <br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Potential</span>
                        </h1>
                        <p className="text-xl text-gray-300 max-w-md leading-relaxed">
                            Connect with expert mentors, finding your dream job, and accelerate your career growth with our AI-powered platform.
                        </p>
                    </div>
                </div>

                {/* Shapes/Decorations */}
                <div className="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                <div className="absolute top-0 -right-4 w-72 h-72 bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            </div>

            {/* Right Side: Login Form */}
            <div className="w-full lg:w-1/2 bg-[#0f111a] flex flex-col justify-center px-8 lg:px-24 py-12 relative">
                <div className="max-w-md w-full mx-auto">
                    <div className="text-center lg:text-left mb-10">
                        <h2 className="text-3xl font-bold text-white mb-2">Welcome Back</h2>
                        <p className="text-gray-400">Please enter your details to sign in.</p>
                    </div>

                    {error && (
                        <div className="mb-6 bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center">
                            {error}
                        </div>
                    )}

                    <form onSubmit={handleLogin} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Mail className="h-5 w-5 text-gray-500" />
                                </div>
                                <input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    className="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-[#1a1c23] text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-150 sm:text-sm"
                                    placeholder="name@company.com"
                                />
                            </div>
                        </div>

                        <div>
                            <div className="flex justify-between items-center mb-2">
                                <label className="block text-sm font-medium text-gray-300">Password</label>
                                <Link href="/forgot-password" className="text-sm font-medium text-indigo-400 hover:text-indigo-300 transition">Forgot password?</Link>
                            </div>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Lock className="h-5 w-5 text-gray-500" />
                                </div>
                                <input
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    className="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-[#1a1c23] text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-150 sm:text-sm"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input id="remember" type="checkbox" className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-600 rounded bg-[#1a1c23]" />
                            <label htmlFor="remember" className="ml-2 block text-sm text-gray-400">Remember me</label>
                        </div>

                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-gray-900 transition-all duration-200 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {loading ? <Loader className="w-5 h-5 animate-spin" /> : 'Sign in to account'}
                        </button>
                    </form>

                    <div className="mt-6">
                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-700"></div>
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="px-2 bg-[#0f111a] text-gray-500">Or continue with</span>
                            </div>
                        </div>

                        <div className="mt-6 grid grid-cols-2 gap-3">
                            <button
                                onClick={handleGoogleLogin}
                                className="w-full inline-flex justify-center py-2.5 px-4 border border-gray-700 rounded-xl shadow-sm bg-[#1a1c23] text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200"
                            >
                                <svg className="h-5 w-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                </svg>
                            </button>
                            <button
                                onClick={() => window.location.href = `${API_BASE_URL}/auth/github`}
                                className="w-full inline-flex justify-center py-2.5 px-4 border border-gray-700 rounded-xl shadow-sm bg-[#1a1c23] text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200"
                            >
                                <Github className="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <p className="mt-8 text-center text-sm text-gray-500">
                        Don't have an account? <Link href="/register" className="font-medium text-indigo-400 hover:text-indigo-300">Sign up</Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
