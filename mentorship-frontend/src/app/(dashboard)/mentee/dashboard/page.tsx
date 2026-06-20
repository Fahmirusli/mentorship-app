'use client';

import { useState, useEffect } from 'react';
import {
  Users, Calendar, BookOpen, TrendingUp, Search,
  Briefcase, Target, Clock, ArrowUpRight, Sparkles, ChevronRight, Video
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
    { label: 'Active Mentorships', value: stats.mentorships, icon: Users, color: 'text-purple-600', bg: 'bg-purple-100', border: 'border-purple-200' },
    { label: 'Hours Mentored', value: stats.hours, icon: Clock, color: 'text-fuchsia-600', bg: 'bg-fuchsia-100', border: 'border-fuchsia-200' },
    { label: 'Skills Learning', value: stats.skills, icon: Target, color: 'text-violet-600', bg: 'bg-violet-100', border: 'border-violet-200' },
    { label: 'Job Matches', value: stats.jobs, icon: Briefcase, color: 'text-indigo-600', bg: 'bg-indigo-100', border: 'border-indigo-200' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 page-enter">
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Welcome Header */}
        <div className="mb-8 animate-fade-in-up relative overflow-hidden rounded-3xl bg-gradient-to-r from-purple-900 via-purple-800 to-indigo-900 p-8 shadow-xl shadow-purple-900/20">
          <div className="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
          <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-fuchsia-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
          
          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <span className="px-3 py-1 bg-white/10 backdrop-blur-md text-purple-100 text-xs font-semibold rounded-full border border-white/20">
                  {new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}
                </span>
              </div>
              <h1 className="text-3xl font-bold text-white mb-2">
                Welcome back, <span className="text-purple-200">{user?.name || 'Mentee'}</span> 👋
              </h1>
              <p className="text-purple-100 text-sm md:text-base max-w-xl">
                Ready to take your skills to the next level? You have {stats.mentorships} active mentorships and {stats.jobs} new job matches waiting for you. Let's make today count!
              </p>
            </div>
            
            <div className="hidden lg:flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/10">
              <div className="p-3 bg-purple-500/20 rounded-xl">
                <Sparkles className="w-6 h-6 text-purple-200" />
              </div>
              <div>
                <p className="text-sm font-medium text-purple-200">Learning Streak</p>
                <p className="text-xl font-bold text-white">{Math.max(1, stats.mentorships * 2)} Days</p>
              </div>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8 stagger-children">
          {statCards.map((stat, i) => (
            <div key={i} className="group glass-panel rounded-2xl p-6 card-hover cursor-default">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-500 text-sm font-medium">{stat.label}</p>
                  <p className="text-3xl font-bold text-gray-900 mt-2">{stat.value}</p>
                </div>
                <div className={`${stat.bg} ${stat.border} border p-3.5 rounded-xl group-hover:scale-110 transition-transform`}>
                  <stat.icon className={`w-6 h-6 ${stat.color}`} />
                </div>
              </div>
              <div className="mt-3 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                <div className={`h-full ${stat.bg.replace('100', '500')} rounded-full transition-all duration-1000`} style={{width: `${Math.min((stat.value || 0) * 10, 100)}%`}} />
              </div>
            </div>
          ))}
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          {/* Upcoming Sessions */}
          <div className="lg:col-span-2 space-y-6">
            <div className="glass-panel rounded-2xl p-6 animate-fade-in-up">
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
                      <div className="text-right flex flex-col items-end gap-2">
                        <div>
                          <p className="text-sm font-semibold text-gray-900">{session.date || 'TBD'}</p>
                          <p className="text-xs text-gray-500">{session.time || 'TBD'}</p>
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="inline-block px-2.5 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-semibold">Confirmed</span>
                          <button 
                            onClick={(e) => { e.stopPropagation(); window.location.href = `/meeting/mentorship-app-${session.id}`; }}
                            className="flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs font-semibold rounded-lg hover:from-blue-600 hover:to-indigo-700 shadow-md transition-all group-hover:scale-105"
                          >
                            <Video className="w-3.5 h-3.5" />
                            Join
                          </button>
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Recommended Jobs */}
            <div className="glass-panel rounded-2xl p-6 animate-fade-in-up animation-delay-1000">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <Briefcase className="w-5 h-5 text-purple-500" />
                  Job Recommendations
                </h2>
                <button onClick={() => window.location.href = '/mentee/jobs'} className="text-purple-600 hover:text-purple-700 text-sm font-semibold flex items-center gap-1 hover:gap-2 transition-all">
                  All Jobs <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              <div className="space-y-3">
                {recommendedJobs.length === 0 ? (
                  <div className="text-center py-10">
                    <div className="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                      <Briefcase className="w-8 h-8 text-purple-300" />
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
                                <h3 className="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">{job.title}</h3>
                            <p className="text-sm text-gray-500">{job.company}</p>
                            <div className="flex items-center mt-2 gap-2 flex-wrap">
                              <span className="px-2.5 py-1 bg-purple-50 text-purple-700 text-xs rounded-full font-bold border border-purple-100">{Math.round(matchScore)}% Match</span>
                              <span className="text-xs text-gray-400">{job.location}</span>
                              {job.source && <span className="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">{job.source}</span>}
                            </div>
                          </div>
                          <button onClick={() => window.location.href = `/jobs/${job.id}`} className="px-4 py-2 solid-primary-btn text-sm rounded-xl font-semibold flex items-center gap-1">
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
            <div className="glass-panel rounded-2xl p-6 animate-fade-in-up animation-delay-1000">
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
                      <div className="w-full bg-purple-100 rounded-full h-2.5 overflow-hidden">
                        <div className={`h-full rounded-full transition-all duration-1000 bg-purple-600`} style={{ width: `${item.progress}%` }} />
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Quick Actions */}
            <div className="glass-panel rounded-2xl p-6 animate-fade-in-up animation-delay-2000">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
              <div className="space-y-2.5">
                {[
                  { label: 'Find a Mentor', icon: Search, href: '/mentee/mentors', color: 'text-purple-600 bg-purple-50 hover:bg-purple-100 border border-purple-100' },
                  { label: 'Book Session', icon: Calendar, href: '/mentee/schedule', color: 'text-fuchsia-600 bg-fuchsia-50 hover:bg-fuchsia-100 border border-fuchsia-100' },
                  { label: 'Browse Resources', icon: BookOpen, href: '#', color: 'text-violet-600 bg-violet-50 hover:bg-violet-100 border border-violet-100' },
                  { label: 'Explore Jobs', icon: Briefcase, href: '/mentee/jobs', color: 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100' },
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