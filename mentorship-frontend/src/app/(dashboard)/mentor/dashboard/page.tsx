'use client';

import { useState, useEffect } from 'react';
import {
  Users, Calendar, Clock, TrendingUp,
  Star, DollarSign, ChevronRight, Sparkles, Video, ArrowUpRight, BarChart3, Activity
} from 'lucide-react';
import { api, authService } from '@/lib/api';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const mockChartData = [
  { name: 'Jan', earnings: 120 },
  { name: 'Feb', earnings: 250 },
  { name: 'Mar', earnings: 180 },
  { name: 'Apr', earnings: 300 },
  { name: 'May', earnings: 450 },
  { name: 'Jun', earnings: 380 },
];

export default function MentorDashboard() {
  const [user, setUser] = useState<any>(null);
  const [stats, setStats] = useState({
    total_mentees: 0, upcoming_sessions: 0, hours_taught: 0,
    rating: 0, total_earnings: 0, pending_requests: 0
  });
  const [upcomingSessions, setUpcomingSessions] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const currentUser = authService.getUser();
    setUser(currentUser);
    fetchData();
    const interval = setInterval(fetchData, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchData = async () => {
    try {
      const [statsRes, sessionsRes] = await Promise.all([
        api.get('/mentor/stats').catch(() => ({})),
        api.get('/appointments?status=upcoming').catch(() => ({ data: [] })),
      ]);
      if (statsRes) setStats(prev => ({ ...prev, ...statsRes }));
      setUpcomingSessions(Array.isArray(sessionsRes) ? sessionsRes : sessionsRes.data || []);
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="relative w-16 h-16 mx-auto mb-4">
            <div className="absolute inset-0 rounded-full border-4 border-emerald-100"></div>
            <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-emerald-600 animate-spin"></div>
          </div>
          <p className="text-gray-500 font-medium">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  const statCards = [
    { label: 'Total Mentees', value: stats.total_mentees, icon: Users, color: 'text-blue-600', bg: 'bg-blue-100', border: 'border-blue-200' },
    { label: 'Hours Taught', value: stats.hours_taught, icon: Clock, color: 'text-violet-600', bg: 'bg-violet-100', border: 'border-violet-200' },
    { label: 'Avg. Rating', value: stats.rating > 0 ? `${stats.rating}/5` : '0.0/5', icon: Star, color: 'text-amber-600', bg: 'bg-amber-100', border: 'border-amber-200' },
    { label: 'Pending Requests', value: stats.pending_requests, icon: Activity, color: 'text-rose-600', bg: 'bg-rose-100', border: 'border-rose-200' },
  ];

  return (
    <div className="min-h-screen bg-gray-50/50 page-enter">
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {/* Aesthetic Hero Section */}
        <div className="mb-8 relative overflow-hidden rounded-[2.5rem] bg-slate-900 p-8 md:p-10 shadow-2xl animate-fade-in-up">
          <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-emerald-500 rounded-full mix-blend-multiply filter blur-[80px] opacity-60 animate-blob"></div>
          <div className="absolute bottom-0 left-0 -mb-10 -ml-10 w-64 h-64 bg-teal-500 rounded-full mix-blend-multiply filter blur-[80px] opacity-60 animate-blob animation-delay-2000"></div>
          
          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="flex items-center gap-2 mb-3">
                <span className="px-3 py-1 bg-white/10 backdrop-blur-md text-emerald-300 text-xs font-semibold rounded-full border border-emerald-500/30">
                  {new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}
                </span>
              </div>
              <h1 className="text-3xl md:text-4xl font-bold text-white mb-2 tracking-tight">
                Welcome back, <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">{user?.name || 'Mentor'}</span>
              </h1>
              <p className="text-slate-300 text-sm md:text-base max-w-xl">
                Your expertise is shaping futures. You have <strong className="text-white">{stats.upcoming_sessions}</strong> upcoming sessions and <strong className="text-white">{stats.pending_requests}</strong> pending requests.
              </p>
            </div>
            
            <div className="hidden lg:flex items-center gap-4 bg-white/10 backdrop-blur-md p-5 rounded-3xl border border-white/10 shadow-inner">
              <div className="p-4 bg-emerald-500/20 rounded-2xl">
                <Sparkles className="w-8 h-8 text-emerald-300" />
              </div>
              <div>
                <p className="text-sm font-medium text-emerald-200">Total Earnings</p>
                <p className="text-3xl font-bold text-white">RM {stats.total_earnings || 0}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Bento Grid Stats */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 stagger-children">
          {statCards.map((stat, i) => (
            <div key={i} className="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all group relative overflow-hidden">
              <div className={`absolute -right-6 -top-6 w-24 h-24 ${stat.bg} rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out`} />
              <div className="relative z-10 flex flex-col h-full justify-between">
                <div className="flex justify-between items-start mb-4">
                  <div className={`p-3 rounded-2xl ${stat.bg} ${stat.color}`}>
                    <stat.icon className="w-6 h-6" />
                  </div>
                </div>
                <div>
                  <h3 className="text-3xl font-black text-gray-900 tracking-tight">{stat.value}</h3>
                  <p className="text-sm font-semibold text-gray-500 mt-1">{stat.label}</p>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 space-y-6">
            
            {/* Earnings Chart (Recharts) */}
            <div className="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 animate-fade-in-up">
              <div className="flex justify-between items-center mb-6">
                <div>
                  <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <BarChart3 className="w-6 h-6 text-emerald-500" />
                    Earnings Overview
                  </h2>
                  <p className="text-sm text-gray-500 mt-1">Your revenue performance over the last 6 months</p>
                </div>
                <button onClick={() => window.location.href = '/mentor/earnings'} className="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold text-sm rounded-xl transition-all flex items-center gap-2">
                  Wallet <ArrowUpRight className="w-4 h-4" />
                </button>
              </div>
              <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={mockChartData} margin={{ top: 10, right: 0, left: -20, bottom: 0 }}>
                    <defs>
                      <linearGradient id="colorEarnings" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
                        <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                      </linearGradient>
                    </defs>
                    <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                    <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                    <Tooltip 
                      contentStyle={{ borderRadius: '16px', border: 'none', boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1)' }}
                      itemStyle={{ color: '#10b981', fontWeight: 'bold' }}
                      formatter={(value) => [`RM ${value}`, 'Earnings']}
                    />
                    <Area type="monotone" dataKey="earnings" stroke="#10b981" strokeWidth={3} fillOpacity={1} fill="url(#colorEarnings)" />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </div>

            {/* Today's Sessions */}
            <div className="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 animate-fade-in-up">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <Calendar className="w-6 h-6 text-indigo-500" />
                  Upcoming Sessions
                </h2>
                <button onClick={() => window.location.href = '/mentor/schedule'} className="text-indigo-600 hover:text-indigo-700 text-sm font-semibold flex items-center gap-1 hover:gap-2 transition-all">
                  View Schedule <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              
              <div className="space-y-4">
                {upcomingSessions.length === 0 ? (
                  <div className="text-center py-12 px-4 rounded-2xl bg-gray-50 border border-dashed border-gray-200">
                    <div className="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
                      <Calendar className="w-8 h-8 text-gray-300" />
                    </div>
                    <p className="text-gray-900 font-bold">No sessions scheduled</p>
                    <p className="text-gray-500 text-sm mt-1">Your upcoming mentorship sessions will appear here.</p>
                  </div>
                ) : (
                  upcomingSessions.slice(0, 4).map((session: any, idx: number) => (
                    <div key={idx} className="flex flex-col sm:flex-row sm:items-center p-4 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:shadow-md transition-all group">
                      <div className="flex items-center gap-4 flex-1">
                        <div className="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center font-bold text-lg group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                          {new Date(session.scheduled_at || session.date).getDate() || <Clock className="w-6 h-6" />}
                        </div>
                        <div>
                          <h3 className="font-bold text-gray-900">{session.title || 'Mentorship Session'}</h3>
                          <p className="text-sm text-gray-500 font-medium">with {session.mentee_name || 'Mentee'}</p>
                        </div>
                      </div>
                      <div className="mt-4 sm:mt-0 sm:text-right flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2">
                        <p className="text-sm font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">{session.time || 'TBD'}</p>
                        <button 
                            onClick={(e) => { e.stopPropagation(); window.location.href = `/meeting/mentorship-app-${session.id}`; }}
                            className="flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-xl hover:bg-indigo-600 transition-colors"
                        >
                          <Video className="w-4 h-4" />
                          Join Call
                        </button>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            
            {/* My Mentees Quick Link */}
            <div className="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-3xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden animate-fade-in-up animation-delay-1000">
              <div className="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl transform translate-x-10 -translate-y-10"></div>
              <h2 className="text-xl font-bold mb-2 relative z-10">Mentee Management</h2>
              <p className="text-blue-100 text-sm mb-6 relative z-10">Track progress, answer messages, and review your mentees' growth.</p>
              
              <button onClick={() => window.location.href = '/mentor/mentees'} className="w-full p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-2xl border border-white/20 transition-all group flex items-center justify-between relative z-10">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 bg-white/20 rounded-xl">
                    <Users className="w-5 h-5 text-white" />
                  </div>
                  <div className="text-left">
                    <p className="font-bold text-sm">My Mentees</p>
                    <p className="text-xs text-blue-200">{stats.total_mentees} Active</p>
                  </div>
                </div>
                <ChevronRight className="w-5 h-5 text-blue-200 group-hover:translate-x-1 transition-transform" />
              </button>
            </div>

            {/* Quick Actions Menu */}
            <div className="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 animate-fade-in-up animation-delay-2000">
              <h2 className="text-xl font-bold text-gray-900 mb-6">Quick Actions</h2>
              <div className="space-y-3">
                {[
                  { label: 'Manage Schedule', icon: Calendar, href: '/mentor/schedule', color: 'text-emerald-600', bg: 'bg-emerald-50' },
                  { label: 'Upload Resources', icon: TrendingUp, href: '/mentor/resources', color: 'text-violet-600', bg: 'bg-violet-50' },
                  { label: 'View Profile', icon: Star, href: '/mentor/profile', color: 'text-amber-600', bg: 'bg-amber-50' },
                ].map((action, i) => (
                  <button key={i} onClick={() => window.location.href = action.href} className="w-full p-3 rounded-2xl hover:bg-gray-50 border border-transparent hover:border-gray-100 transition-all group flex items-center gap-4 text-left">
                    <div className={`p-3 rounded-xl ${action.bg} ${action.color} group-hover:scale-110 transition-transform`}>
                      <action.icon className="w-5 h-5" />
                    </div>
                    <span className="font-bold text-gray-700 text-sm">{action.label}</span>
                    <ChevronRight className="w-4 h-4 text-gray-400 ml-auto group-hover:translate-x-1 transition-transform" />
                  </button>
                ))}
              </div>
            </div>
            
          </div>
        </div>
      </main>
    </div>
  );
}