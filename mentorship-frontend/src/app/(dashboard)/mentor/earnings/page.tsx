'use client';

import { useState, useEffect } from 'react';
import { DollarSign, Download, Calendar, TrendingUp, History, CreditCard, ArrowUpRight } from 'lucide-react';
import { api } from '@/lib/api';

export default function MentorEarnings() {
    const [stats, setStats] = useState({
        total_earnings: 0,
        recent_transactions: [] as any[],
        withdrawals: [] as any[],
    });
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('1');
    const [showWithdrawModal, setShowWithdrawModal] = useState(false);
    const [withdrawAmount, setWithdrawAmount] = useState('');
    const [bankDetails, setBankDetails] = useState({
        bank_name: '',
        account_number: '',
        account_name: ''
    });
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [statsRes, walletRes] = await Promise.all([
                api.get('/mentor/stats'),
                api.get('/wallet')
            ]);
            
            setStats(prev => ({ 
                ...prev, 
                ...statsRes,
                total_earnings: walletRes?.balance || statsRes?.total_earnings || 0,
                withdrawals: walletRes?.withdrawals || []
            }));
        } catch (error) {
            console.error('Error fetching earnings stats:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleWithdraw = async () => {
        if (!withdrawAmount || parseFloat(withdrawAmount) < 50) {
            alert('Minimum withdrawal is RM50');
            return;
        }
        if (!bankDetails.bank_name || !bankDetails.account_number || !bankDetails.account_name) {
            alert('Please fill in all bank details');
            return;
        }

        setSubmitting(true);
        try {
            await api.post('/wallet/withdraw', {
                amount: parseFloat(withdrawAmount),
                ...bankDetails
            });
            alert('Withdrawal request submitted successfully!');
            setShowWithdrawModal(false);
            setWithdrawAmount('');
            setBankDetails({ bank_name: '', account_number: '', account_name: '' });
            fetchData();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to submit withdrawal request');
        } finally {
            setSubmitting(false);
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
                    <div className="md:col-span-2 rounded-2xl p-8 bg-gradient-to-br from-emerald-600 to-teal-800 text-white shadow-lg relative overflow-hidden border border-emerald-700">
                        <div className="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                        <div className="absolute right-20 -bottom-10 w-32 h-32 bg-emerald-400 opacity-20 rounded-full blur-xl"></div>
                        
                        <div className="relative z-10">
                            <p className="text-emerald-100 font-medium mb-1">Available Balance</p>
                            <h2 className="text-5xl font-bold mb-6">RM {Number(stats.total_earnings || 0).toFixed(2)}</h2>
                            
                            <div className="flex gap-4">
                                <button 
                                    onClick={() => setShowWithdrawModal(true)}
                                    className="px-6 py-2.5 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition shadow-sm flex items-center gap-2"
                                >
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
                                <p className="text-xl font-bold text-gray-900">
                                    RM {stats.withdrawals.filter((w: any) => w.status === 'pending').reduce((sum, w: any) => sum + parseFloat(w.amount), 0).toFixed(2)}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Transaction History Placeholder */}
                <div className="glass-panel rounded-2xl p-8 animate-fade-in-up animation-delay-1000">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <History className="w-5 h-5 text-gray-500" />
                            Recent Transactions & Withdrawals
                        </h2>
                    </div>

                    {stats.withdrawals.length > 0 || (stats.recent_transactions && stats.recent_transactions.length > 0) ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="border-b border-gray-100 text-sm text-gray-500">
                                        <th className="pb-4 font-medium">Description</th>
                                        <th className="pb-4 font-medium">Date</th>
                                        <th className="pb-4 font-medium">Status</th>
                                        <th className="pb-4 font-medium text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {stats.withdrawals.map((w: any) => (
                                        <tr key={`w-${w.id}`} className="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                                            <td className="py-4 font-medium text-gray-900">Withdrawal to {w.bank_name}</td>
                                            <td className="py-4 text-gray-500">{new Date(w.created_at).toLocaleDateString()}</td>
                                            <td className="py-4">
                                                <span className={`px-2 py-1 rounded text-xs font-bold ${w.status === 'pending' ? 'bg-amber-100 text-amber-700' : w.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                                                    {w.status.toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="py-4 font-bold text-red-600 text-right">- RM {parseFloat(w.amount).toFixed(2)}</td>
                                        </tr>
                                    ))}
                                    {stats.recent_transactions
                                        .map((tx: any, idx: number) => (
                                        <tr key={`tx-${idx}`} className="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                                            <td className="py-4 font-medium text-gray-900">Payment from {tx.mentee_name}</td>
                                            <td className="py-4 text-gray-500">{new Date(tx.date).toLocaleDateString()}</td>
                                            <td className="py-4">
                                                <span className="px-2 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-700">COMPLETED</span>
                                            </td>
                                            <td className="py-4 font-bold text-emerald-600 text-right">+ RM {parseFloat(tx.amount).toFixed(2)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <Calendar className="w-8 h-8 text-gray-400" />
                            </div>
                            <p className="text-gray-500 font-medium">No recent transactions</p>
                            <p className="text-gray-400 text-sm mt-1">Your payouts and earnings history will appear here once you complete sessions.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Withdraw Modal */}
            {showWithdrawModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                        <div className="p-6 border-b border-gray-100">
                            <h3 className="text-xl font-bold text-gray-900">Withdraw Funds</h3>
                            <p className="text-sm text-gray-500 mt-1">Available balance: RM {Number(stats.total_earnings || 0).toFixed(2)}</p>
                        </div>
                        <div className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Amount to withdraw (RM)</label>
                                <input 
                                    type="number" 
                                    value={withdrawAmount}
                                    onChange={(e) => setWithdrawAmount(e.target.value)}
                                    placeholder="Min 50.00"
                                    className="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                                <input 
                                    type="text" 
                                    value={bankDetails.bank_name}
                                    onChange={(e) => setBankDetails({...bankDetails, bank_name: e.target.value})}
                                    placeholder="Maybank"
                                    className="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                                <input 
                                    type="text" 
                                    value={bankDetails.account_number}
                                    onChange={(e) => setBankDetails({...bankDetails, account_number: e.target.value})}
                                    placeholder="1122334455"
                                    className="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Account Holder Name</label>
                                <input 
                                    type="text" 
                                    value={bankDetails.account_name}
                                    onChange={(e) => setBankDetails({...bankDetails, account_name: e.target.value})}
                                    placeholder="John Doe"
                                    className="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none"
                                />
                            </div>
                            
                            <div className="flex gap-3 pt-4">
                                <button 
                                    onClick={() => setShowWithdrawModal(false)}
                                    disabled={submitting}
                                    className="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition"
                                >
                                    Cancel
                                </button>
                                <button 
                                    onClick={handleWithdraw}
                                    disabled={submitting}
                                    className="flex-1 px-4 py-2.5 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition shadow-sm disabled:opacity-50"
                                >
                                    {submitting ? 'Processing...' : 'Confirm'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
