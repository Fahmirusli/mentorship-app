'use client';

import { useState, useEffect } from 'react';
import {
  Users, Calendar, BookOpen, TrendingUp, Search,
  Bell, Settings, LogOut, Briefcase, Target, Clock
} from 'lucide-react';
import { api, authService } from '@/lib/api';

export default function MenteeDashboard() {
  const [user, setUser] = useState<any>(null);
  const [stats, setStats] = useState({
    mentorships: 0,
    hours: 0,
    skills: 0,
    jobs: 0,
    learning_progress: [] as { name: string; progress: number }[]
  });
  const [upcomingSessions, setUpcomingSessions] = useState<any[]>([]);
  const [recommendedJobs, setRecommendedJobs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const currentUser = authService.getUser();
    setUser(currentUser);
    fetchDashboardData();

    // Poll for updates every 30 seconds to keep data synchronized
    const interval = setInterval(fetchDashboardData, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [statsRes, sessionsRes, jobsRes] = await Promise.all([
        api.get('/mentee/stats').catch(() => ({
          mentorships: 0, hours: 0, skills: 0, jobs: 0, learning_progress: []
        })),
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
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-xl shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Active Mentorships</p>
                <p className="text-3xl font-bold text-gray-900 mt-1">{stats.mentorships}</p>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Users className="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Hours Mentored</p>
                <p className="text-3xl font-bold text-gray-900 mt-1">{stats.hours}</p>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <Clock className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Skills Learning</p>
                <p className="text-3xl font-bold text-gray-900 mt-1">{stats.skills}</p>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <Target className="w-6 h-6 text-purple-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Job Matches</p>
                <p className="text-3xl font-bold text-gray-900 mt-1">{stats.jobs}</p>
              </div>
              <div className="bg-orange-100 p-3 rounded-lg">
                <Briefcase className="w-6 h-6 text-orange-600" />
              </div>
            </div>
          </div>
        </div>

        <div className="grid lg:grid-cols-3 gap-8">
          {/* Upcoming Sessions */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-bold text-gray-900">Upcoming Sessions</h2>
                <button className="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                  View All
                </button>
              </div>

              <div className="space-y-4">
                {upcomingSessions.length === 0 ? (
                  <div className="text-center py-8">
                    <Calendar className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500">No upcoming sessions</p>
                    <button
                      onClick={() => window.location.href = '/mentee/mentors'}
                      className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                      Book a Session
                    </button>
                  </div>
                ) : (
                  upcomingSessions.map((session, idx) => (
                    <div key={idx} className="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                      <div className="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <Calendar className="w-8 h-8 text-indigo-600" />
                      </div>
                      <div className="ml-4 flex-1">
                        <h3 className="font-semibold text-gray-900">{session.title || 'Mentorship Session'}</h3>
                        <p className="text-sm text-gray-600">with {session.mentor_name || 'John Doe'}</p>
                        <p className="text-xs text-gray-500 mt-1">{session.topic || 'Career Development'}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-medium text-gray-900">{session.date || 'Tomorrow'}</p>
                        <p className="text-xs text-gray-500">{session.time || '2:00 PM'}</p>
                        <span className="inline-block mt-1 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                          Confirmed
                        </span>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Recommended Jobs */}
            <div className="bg-white rounded-xl shadow-sm p-6 mt-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-bold text-gray-900">Recommended Jobs</h2>
                <button
                  onClick={() => window.location.href = '/mentee/jobs'}
                  className="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                >
                  View All Jobs
                </button>
              </div>

              <div className="space-y-4">
                {recommendedJobs.length === 0 ? (
                  <div className="text-center py-8">
                    <Briefcase className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500">No job recommendations yet</p>
                    <p className="text-sm text-gray-400 mt-2">Complete your profile to get personalized recommendations</p>
                  </div>
                ) : (
                  recommendedJobs.map((item, idx) => {
                    const job = item.job || item;
                    const matchScore = item.match_score || 0;

                    return (
                      <div key={idx} className="p-4 border border-gray-200 rounded-lg hover:border-indigo-300 transition">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h3 className="font-semibold text-gray-900">{job.title}</h3>
                            <p className="text-sm text-gray-600">{job.company}</p>
                            <div className="flex items-center mt-2 space-x-2">
                              <span className="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold">
                                {matchScore}% Match
                              </span>
                              <span className="text-xs text-gray-500">{job.location}</span>
                            </div>
                          </div>
                          <button
                            onClick={() => window.location.href = `/jobs/${job.id}`}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
                          >
                            View
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
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Learning Progress</h2>
              <div className="space-y-4">
                {stats.learning_progress.length === 0 ? (
                  <p className="text-gray-500 text-sm">No active mentorships to track progress.</p>
                ) : (
                  stats.learning_progress.map((item, idx) => (
                    <div key={idx}>
                      <div className="flex justify-between text-sm mb-1">
                        <span className="text-gray-600">{item.name}</span>
                        <span className="font-medium">{Math.round(item.progress)}%</span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div
                          className={`h-2 rounded-full ${['bg-blue-600', 'bg-green-600', 'bg-purple-600', 'bg-orange-600'][idx % 4]}`}
                          style={{ width: `${item.progress}%` }}
                        ></div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Quick Actions */}
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
              <div className="space-y-3">
                <button
                  onClick={() => window.location.href = '/mentee/mentors'}
                  className="w-full px-4 py-3 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 text-left font-medium"
                >
                  <Search className="w-4 h-4 inline mr-2" />
                  Find a Mentor
                </button>
                <button
                  onClick={() => window.location.href = '/mentee/appointments'}
                  className="w-full px-4 py-3 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 text-left font-medium"
                >
                  <Calendar className="w-4 h-4 inline mr-2" />
                  Book Session
                </button>
                <button className="w-full px-4 py-3 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 text-left font-medium">
                  <BookOpen className="w-4 h-4 inline mr-2" />
                  Browse Resources
                </button>
                <button
                  onClick={() => window.location.href = '/mentee/jobs'}
                  className="w-full px-4 py-3 bg-orange-50 text-orange-600 rounded-lg hover:bg-orange-100 text-left font-medium"
                >
                  <Briefcase className="w-4 h-4 inline mr-2" />
                  Explore Jobs
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}