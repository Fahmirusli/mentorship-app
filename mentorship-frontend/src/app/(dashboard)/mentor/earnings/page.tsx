'use client';

import { useState, useEffect } from 'react';
import { DollarSign, Download, Calendar, TrendingUp, History, CreditCard, ArrowUpRight } from 'lucide-react';
import { api } from '@/lib/api';

export default function MentorEarnings() {
    const [stats, setStats] = useState({
        total_earnings: 0,
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const res = await api.get('/mentor/stats');
            if (res) setStats(prev => ({ ...prev, ...res }));
        } catch (error) {
            console.error('Error fetching earnings stats:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="relative w-16 h-16 mx-auto mb-4">
                    <div className="absolute inset-0 rounded-full border-4 border-emerald-100"></div>
                    <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-emerald-600 animate-spin"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 p-8 page-enter">
            <div className="max-w-5xl mx-auto">
                <div className="mb-8 animate-fade-in-up">
                    <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                        <DollarSign className="w-8 h-8 text-emerald-600" />
                        My Earnings & Wallet
                    </h1>
                    <p className="text-gray-600 mt-2">Manage your funds and track your recent transactions.</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 stagger-children">
                    {/* Wallet Card */}
                    <div className="md:col-span-2 glass-panel rounded-2xl p-8 bg-gradient-to-br from-emerald-600 to-teal-800 text-white shadow-lg relative overflow-hidden">
                        <div className="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                        <div className="absolute right-20 -bottom-10 w-32 h-32 bg-emerald-400 opacity-20 rounded-full blur-xl"></div>
                        
                        <div className="relative z-10">
                            <p className="text-emerald-100 font-medium mb-1">Available Balance</p>
                            <h2 className="text-5xl font-bold mb-6">RM {stats.total_earnings || 0}</h2>
                            
                            <div className="flex gap-4">
                                <button className="px-6 py-2.5 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition shadow-sm flex items-center gap-2">
                                    <ArrowUpRight className="w-5 h-5" />
                                    Withdraw Funds
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Quick Stats */}
                    <div className="glass-panel rounded-2xl p-6 flex flex-col justify-center">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="p-3 bg-blue-100 text-blue-600 rounded-xl">
                                <TrendingUp className="w-6 h-6" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 font-medium">This Month</p>
                                <p className="text-xl font-bold text-gray-900">RM {stats.total_earnings || 0}</p>
                            </div>
                        </div>
                        <div className="h-px w-full bg-gray-100 my-2"></div>
                        <div className="flex items-center gap-3 mt-4">
                            <div className="p-3 bg-violet-100 text-violet-600 rounded-xl">
                                <CreditCard className="w-6 h-6" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 font-medium">Pending Clearance</p>
                                <p className="text-xl font-bold text-gray-900">RM 0</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Transaction History Placeholder */}
                <div className="glass-panel rounded-2xl p-8 animate-fade-in-up animation-delay-1000">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <History className="w-5 h-5 text-gray-500" />
                            Recent Transactions
                        </h2>
                        <button className="text-emerald-600 hover:text-emerald-700 font-medium text-sm flex items-center gap-1">
                            <Download className="w-4 h-4" /> Export CSV
                        </button>
                    </div>

                    <div className="text-center py-12">
                        <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <Calendar className="w-8 h-8 text-gray-400" />
                        </div>
                        <p className="text-gray-500 font-medium">No recent transactions</p>
                        <p className="text-gray-400 text-sm mt-1">Your payouts and earnings history will appear here once you complete sessions.</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
