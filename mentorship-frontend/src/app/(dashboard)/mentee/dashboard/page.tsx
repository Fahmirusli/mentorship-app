'use client';

import { useState, useEffect } from 'react';
import {
  Users, Calendar, BookOpen, TrendingUp, Search,
  Briefcase, Target, Clock, ArrowUpRight, Sparkles, ChevronRight
} from 'lucide-react';
import { api, authService } from '@/lib/api';

export default function MenteeDashboard() {
  const [user, setUser] = useState<any>(null);
  const [stats, setStats] = useState({
    mentorships: 0, hours: 0, skills: 0, jobs: 0,
    learning_progress: [] as { name: string; progress: number }[]
  });
  const [upcomingSessions, setUpcomingSessions] = useState<any[]>([]);
  const [recommendedJobs, setRecommendedJobs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const currentUser = authService.getUser();
    setUser(currentUser);
    fetchDashboardData();
    const interval = setInterval(fetchDashboardData, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [statsRes, sessionsRes, jobsRes] = await Promise.all([
        api.get('/mentee/stats').catch(() => ({ mentorships: 0, hours: 0, skills: 0, jobs: 0, learning_progress: [] })),
        api.get('/appointments?status=upcoming').catch(() => ({ data: [] })),
        api.get('/jobs/recommendations').catch(() => ({ recommendations: [] }))
      ]);
      setStats(statsRes);
      setUpcomingSessions(Array.isArray(sessionsRes) ? sessionsRes : sessionsRes.data || []);
      const jobs = jobsRes.recommendations || [];
      setRecommendedJobs(jobs.slice(0, 3));
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="relative w-16 h-16 mx-auto mb-4">
            <div className="absolute inset-0 rounded-full border-4 border-indigo-100"></div>
            <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-indigo-600 animate-spin"></div>
          </div>
          <p className="text-gray-500 font-medium">Loading your dashboard...</p>
        </div>
      </div>
    );
  }

  const statCards = [
    { label: 'Active Mentorships', value: stats.mentorships, icon: Users, gradient: 'from-blue-500 to-indigo-600', bg: 'bg-blue-50' },
    { label: 'Hours Mentored', value: stats.hours, icon: Clock, gradient: 'from-emerald-500 to-teal-600', bg: 'bg-emerald-50' },
    { label: 'Skills Learning', value: stats.skills, icon: Target, gradient: 'from-violet-500 to-purple-600', bg: 'bg-violet-50' },
    { label: 'Job Matches', value: stats.jobs, icon: Briefcase, gradient: 'from-amber-500 to-orange-600', bg: 'bg-amber-50' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 page-enter">
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Welcome Header */}
        <div className="mb-8 animate-fade-in-up">
          <div className="flex items-center gap-2 mb-1">
            <Sparkles className="w-5 h-5 text-indigo-500" />
            <span className="text-sm font-medium text-indigo-600">Dashboard</span>
          </div>
          <h1 className="text-2xl font-bold text-gray-900">
            Welcome back, <span className="gradient-text">{user?.name || 'Mentee'}</span>
          </h1>
          <p className="text-gray-500 mt-1">Here&apos;s what&apos;s happening with your mentorship journey.</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8 stagger-children">
          {statCards.map((stat, i) => (
            <div key={i} className="group bg-white rounded-2xl shadow-sm p-6 border border-gray-100 card-hover cursor-default">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-sm font-medium">{stat.label}</p>
                  <p className="text-3xl font-bold text-gray-900 mt-2">{stat.value}</p>
                </div>
                <div className={`${stat.bg} p-3.5 rounded-xl group-hover:scale-110 transition-transform`}>
                  <stat.icon className={`w-6 h-6 bg-gradient-to-br ${stat.gradient} bg-clip-text`} style={{color: 'inherit'}} />
                </div>
              </div>
              <div className="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div className={`h-full bg-gradient-to-r ${stat.gradient} rounded-full transition-all duration-1000`} style={{width: `${Math.min((stat.value || 0) * 10, 100)}%`}} />
              </div>
            </div>
          ))}
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          {/* Upcoming Sessions */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <Calendar className="w-5 h-5 text-indigo-500" />
                  Upcoming Sessions
                </h2>
                <button onClick={() => window.location.href = '/mentee/schedule'} className="text-indigo-600 hover:text-indigo-700 text-sm font-semibold flex items-center gap-1 hover:gap-2 transition-all">
                  View All <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              <div className="space-y-3">
                {upcomingSessions.length === 0 ? (
                  <div className="text-center py-10">
                    <div className="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                      <Calendar className="w-8 h-8 text-indigo-300" />
                    </div>
                    <p className="text-gray-500 font-medium">No upcoming sessions</p>
                    <p className="text-gray-400 text-sm mt-1">Book a session with a mentor to get started</p>
                    <button onClick={() => window.location.href = '/mentee/mentors'} className="mt-4 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-500 hover:to-purple-500 transition-all text-sm font-semibold shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30 hover:scale-105 transform">
                      Find a Mentor
                    </button>
                  </div>
                ) : (
                  upcomingSessions.map((session: any, idx: number) => (
                    <div key={idx} className="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-indigo-50/50 transition-all cursor-pointer group border border-transparent hover:border-indigo-100">
                      <div className="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <Calendar className="w-6 h-6 text-white" />
                      </div>
                      <div className="ml-4 flex-1">
                        <h3 className="font-semibold text-gray-900">{session.title || 'Mentorship Session'}</h3>
                        <p className="text-sm text-gray-500">with {session.mentor_name || 'Mentor'}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-gray-900">{session.date || 'TBD'}</p>
                        <p className="text-xs text-gray-500">{session.time || 'TBD'}</p>
                        <span className="inline-block mt-1 px-2.5 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-semibold">Confirmed</span>
                      </div>
                      <ArrowUpRight className="w-4 h-4 text-gray-300 ml-3 group-hover:text-indigo-500 transition-colors" />
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Recommended Jobs */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up animation-delay-1000">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <Briefcase className="w-5 h-5 text-amber-500" />
                  Job Recommendations
                </h2>
                <button onClick={() => window.location.href = '/mentee/jobs'} className="text-indigo-600 hover:text-indigo-700 text-sm font-semibold flex items-center gap-1 hover:gap-2 transition-all">
                  All Jobs <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              <div className="space-y-3">
                {recommendedJobs.length === 0 ? (
                  <div className="text-center py-10">
                    <div className="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                      <Briefcase className="w-8 h-8 text-amber-300" />
                    </div>
                    <p className="text-gray-500 font-medium">No recommendations yet</p>
                    <p className="text-gray-400 text-sm mt-1">Complete your profile for personalized matches</p>
                  </div>
                ) : (
                  recommendedJobs.map((item: any, idx: number) => {
                    const job = item.job || item;
                    const matchScore = item.match_score || 0;
                    return (
                      <div key={idx} className="p-4 border border-gray-100 rounded-xl hover:border-indigo-200 hover:shadow-md transition-all group cursor-pointer card-hover">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h3 className="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{job.title}</h3>
                            <p className="text-sm text-gray-500">{job.company}</p>
                            <div className="flex items-center mt-2 gap-2 flex-wrap">
                              <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs rounded-full font-bold border border-emerald-100">{matchScore}% Match</span>
                              <span className="text-xs text-gray-400">{job.location}</span>
                              {job.source && <span className="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">{job.source}</span>}
                            </div>
                          </div>
                          <button onClick={() => window.location.href = `/jobs/${job.id}`} className="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm rounded-xl hover:from-indigo-500 hover:to-purple-500 font-semibold shadow-sm hover:shadow-md transition-all flex items-center gap-1">
                            View <ArrowUpRight className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Learning Progress */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up animation-delay-1000">
              <h2 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <TrendingUp className="w-5 h-5 text-violet-500" />
                Learning Progress
              </h2>
              <div className="space-y-4">
                {stats.learning_progress.length === 0 ? (
                  <p className="text-gray-400 text-sm py-4 text-center">No active mentorships to track.</p>
                ) : (
                  stats.learning_progress.map((item, idx) => (
                    <div key={idx}>
                      <div className="flex justify-between text-sm mb-1.5">
                        <span className="text-gray-600 font-medium">{item.name}</span>
                        <span className="font-bold text-gray-900">{Math.round(item.progress)}%</span>
                      </div>
                      <div className="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                        <div className={`h-full rounded-full transition-all duration-1000 bg-gradient-to-r ${['from-blue-500 to-indigo-500', 'from-emerald-500 to-teal-500', 'from-violet-500 to-purple-500', 'from-amber-500 to-orange-500'][idx % 4]}`} style={{ width: `${item.progress}%` }} />
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Quick Actions */}
            <div className="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 animate-fade-in-up animation-delay-2000">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
              <div className="space-y-2.5">
                {[
                  { label: 'Find a Mentor', icon: Search, href: '/mentee/mentors', color: 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100' },
                  { label: 'Book Session', icon: Calendar, href: '/mentee/schedule', color: 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100' },
                  { label: 'Browse Resources', icon: BookOpen, href: '#', color: 'text-violet-600 bg-violet-50 hover:bg-violet-100' },
                  { label: 'Explore Jobs', icon: Briefcase, href: '/mentee/jobs', color: 'text-amber-600 bg-amber-50 hover:bg-amber-100' },
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