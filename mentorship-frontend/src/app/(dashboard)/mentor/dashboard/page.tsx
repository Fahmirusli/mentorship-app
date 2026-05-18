'use client';

import { useState, useEffect } from 'react';
import {
  Users, Calendar, Clock, TrendingUp, MessageSquare,
  Star, DollarSign, ChevronRight, Sparkles, ArrowUpRight, BarChart3
} from 'lucide-react';
import { api, authService } from '@/lib/api';

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
    { label: 'Total Mentees', value: stats.total_mentees, icon: Users, gradient: 'from-blue-500 to-indigo-600', bg: 'bg-blue-50' },
    { label: 'Upcoming Sessions', value: stats.upcoming_sessions, icon: Calendar, gradient: 'from-emerald-500 to-teal-600', bg: 'bg-emerald-50' },
    { label: 'Hours Taught', value: stats.hours_taught, icon: Clock, gradient: 'from-violet-500 to-purple-600', bg: 'bg-violet-50' },
    { label: 'Avg. Rating', value: stats.rating ? `${stats.rating}/5` : '—', icon: Star, gradient: 'from-amber-500 to-orange-600', bg: 'bg-amber-50' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 page-enter">
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Welcome Header */}
        <div className="mb-8 animate-fade-in-up">
          <div className="flex items-center gap-2 mb-1">
            <Sparkles className="w-5 h-5 text-emerald-500" />
            <span className="text-sm font-medium text-emerald-600">Mentor Dashboard</span>
          </div>
          <h1 className="text-2xl font-bold text-gray-900">
            Welcome back, <span className="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">{user?.name || 'Mentor'}</span>
          </h1>
          <p className="text-gray-500 mt-1">Here&apos;s your mentorship overview for today.</p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8 stagger-children">
          {statCards.map((stat, i) => (
            <div key={i} className="group bg-white rounded-2xl shadow-sm p-6 border border-gray-100 card-hover cursor-default">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-sm font-medium">{stat.label}</p>
                  <p className="text-3xl font-bold text-gray-900 mt-2">{stat.value}</p>
                </div>
                <div className={`${stat.bg} p-3.5 rounded-xl group-hover:scale-110 transition-transform`}>
                  <stat.icon className="w-6 h-6" />
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 space-y-6">
            {/* Today's Sessions */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <Calendar className="w-5 h-5 text-emerald-500" />
                  Today&apos;s Sessions
                </h2>
                <button onClick={() => window.location.href = '/mentor/schedule'} className="text-emerald-600 hover:text-emerald-700 text-sm font-semibold flex items-center gap-1 hover:gap-2 transition-all">
                  View All <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              <div className="space-y-3">
                {upcomingSessions.length === 0 ? (
                  <div className="text-center py-10">
                    <div className="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                      <Calendar className="w-8 h-8 text-emerald-300" />
                    </div>
                    <p className="text-gray-500 font-medium">No sessions today</p>
                    <p className="text-gray-400 text-sm mt-1">Your upcoming sessions will appear here</p>
                  </div>
                ) : (
                  upcomingSessions.slice(0, 5).map((session: any, idx: number) => (
                    <div key={idx} className="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-emerald-50/50 transition-all cursor-pointer group border border-transparent hover:border-emerald-100">
                      <div className="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <Clock className="w-6 h-6 text-white" />
                      </div>
                      <div className="ml-4 flex-1">
                        <h3 className="font-semibold text-gray-900">{session.title || 'Mentorship Session'}</h3>
                        <p className="text-sm text-gray-500">with {session.mentee_name || 'Mentee'}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-gray-900">{session.time || 'TBD'}</p>
                        <span className="inline-block mt-1 px-2.5 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-semibold">
                          {session.status === 'scheduled' ? 'Upcoming' : session.status || 'Scheduled'}
                        </span>
                      </div>
                      <ArrowUpRight className="w-4 h-4 text-gray-300 ml-3 group-hover:text-emerald-500 transition-colors" />
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Recent Activity */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up animation-delay-1000">
              <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2 mb-6">
                <BarChart3 className="w-5 h-5 text-violet-500" />
                Quick Overview
              </h2>
              <div className="grid grid-cols-2 gap-4">
                <div className="p-4 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl border border-emerald-100">
                  <DollarSign className="w-8 h-8 text-emerald-600 mb-2" />
                  <p className="text-2xl font-bold text-gray-900">RM {stats.total_earnings || 0}</p>
                  <p className="text-sm text-gray-500">Total Earnings</p>
                </div>
                <div className="p-4 bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl border border-amber-100">
                  <TrendingUp className="w-8 h-8 text-amber-600 mb-2" />
                  <p className="text-2xl font-bold text-gray-900">{stats.pending_requests || 0}</p>
                  <p className="text-sm text-gray-500">Pending Requests</p>
                </div>
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Messages */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up animation-delay-1000">
              <h2 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <MessageSquare className="w-5 h-5 text-blue-500" />
                Messages
              </h2>
              <button onClick={() => window.location.href = '/mentor/mentees'} className="w-full p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-100 hover:shadow-md transition-all group cursor-pointer text-left">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                    <MessageSquare className="w-5 h-5 text-white" />
                  </div>
                  <div className="flex-1">
                    <p className="font-semibold text-gray-900 text-sm">Chat with mentees</p>
                    <p className="text-xs text-gray-500">View conversations</p>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" />
                </div>
              </button>
            </div>

            {/* Quick Actions */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up animation-delay-2000">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
              <div className="space-y-2.5">
                {[
                  { label: 'View Mentees', icon: Users, href: '/mentor/mentees', color: 'text-blue-600 bg-blue-50 hover:bg-blue-100' },
                  { label: 'Manage Schedule', icon: Calendar, href: '/mentor/schedule', color: 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100' },
                  { label: 'Upload Resources', icon: TrendingUp, href: '/mentor/resources', color: 'text-violet-600 bg-violet-50 hover:bg-violet-100' },
                  { label: 'View Profile', icon: Star, href: '/mentor/profile', color: 'text-amber-600 bg-amber-50 hover:bg-amber-100' },
                ].map((action, i) => (
                  <button key={i} onClick={() => window.location.href = action.href} className={`w-full px-4 py-3 ${action.color} rounded-xl text-left font-medium text-sm flex items-center gap-3 transition-all group hover:scale-[1.02] transform`}>
                    <action.icon className="w-4 h-4" />
                    {action.label}
                    <ChevronRight className="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" />
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