'use client';

import { useState, useEffect } from 'react';
import {
  Users, Calendar, Clock, DollarSign, Star, TrendingUp,
  Bell, Settings, LogOut, Award, MessageSquare, BookOpen
} from 'lucide-react';
import { api, authService } from '@/lib/api';

export default function MentorDashboard() {
  const [user, setUser] = useState<any>(null);
  const [stats, setStats] = useState({
    totalMentees: 0,
    hoursProvided: 0,
    earnings: 0,
    rating: 0,
    upcomingSessions: 0
  });
  const [upcomingSessions, setUpcomingSessions] = useState<any[]>([]);
  const [recentFeedback, setRecentFeedback] = useState<any[]>([]);
  const [mentees, setMentees] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const currentUser = authService.getUser();
    setUser(currentUser);
    fetchDashboardData();

    // Poll for updates every 30 seconds
    const interval = setInterval(fetchDashboardData, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [statsRes, sessionsRes, feedbackRes, menteesRes] = await Promise.all([
        api.get('/mentor/stats').catch(() => ({
          totalMentees: 0, hoursProvided: 0, earnings: 0, rating: 0, upcomingSessions: 0
        })),
        api.get('/appointments?role=mentor&status=upcoming').catch(() => ({ data: [] })),
        api.get('/feedback?mentor=true&recent=true').catch(() => ({ data: [] })),
        api.get('/mentorships').catch(() => ({ data: [] }))
      ]);

      setStats(statsRes);
      setUpcomingSessions(Array.isArray(sessionsRes) ? sessionsRes : sessionsRes.data || []);
      setRecentFeedback(Array.isArray(feedbackRes) ? feedbackRes : feedbackRes.data || []);
      setMentees(Array.isArray(menteesRes) ? menteesRes : menteesRes.data || []);
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    authService.logout();
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
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
        <div className="mb-8">
          <h1 className="text-2xl font-bold text-gray-900">Mentor Dashboard</h1>
          <p className="text-sm text-gray-500">Welcome back, {user?.name || 'Mentor'}!</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-blue-100 text-sm">Total Mentees</p>
                <p className="text-3xl font-bold mt-1">{stats.totalMentees}</p>
              </div>
              <Users className="w-10 h-10 text-blue-200" />
            </div>
          </div>

          <div className="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-green-100 text-sm">Hours Provided</p>
                <p className="text-3xl font-bold mt-1">{stats.hoursProvided}</p>
              </div>
              <Clock className="w-10 h-10 text-green-200" />
            </div>
          </div>

          <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-purple-100 text-sm">Earnings</p>
                <p className="text-3xl font-bold mt-1">RM{stats.earnings}</p>
              </div>
              <DollarSign className="w-10 h-10 text-purple-200" />
            </div>
          </div>

          <div className="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-yellow-100 text-sm">Rating</p>
                <p className="text-3xl font-bold mt-1">{stats.rating}/5</p>
              </div>
              <Star className="w-10 h-10 text-yellow-200" />
            </div>
          </div>
        </div>

        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
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
                      onClick={() => window.location.href = '/mentor/schedule'}
                      className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                      Set Availability
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
                        <p className="text-sm text-gray-600">with {session.mentee_name || 'John Doe'}</p>
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

            <div className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-bold text-gray-900">My Mentees</h2>
                <button className="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                  View All
                </button>
              </div>

              <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                {mentees.length === 0 ? (
                  <div className="col-span-3 text-center py-8">
                    <Users className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500">No mentees yet</p>
                  </div>
                ) : (
                  mentees.map((mentee, idx) => (
                    <div key={idx} className="p-4 border border-gray-200 rounded-lg hover:border-indigo-300 transition text-center">
                      <div className="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full mx-auto mb-2 flex items-center justify-center text-white font-bold text-xl">
                        {mentee.name?.charAt(0) || 'M'}
                      </div>
                      <h3 className="font-semibold text-gray-900 text-sm">{mentee.name || 'Mentee Name'}</h3>
                      <p className="text-xs text-gray-500">{mentee.goal || 'Learning React'}</p>
                      <div className="mt-2">
                        <span className="text-xs text-indigo-600">{mentee.sessions || 5} sessions</span>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>

          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Recent Feedback</h2>
              <div className="space-y-4">
                {recentFeedback.length === 0 ? (
                  <div className="text-center py-6">
                    <MessageSquare className="w-10 h-10 text-gray-300 mx-auto mb-2" />
                    <p className="text-sm text-gray-500">No feedback yet</p>
                  </div>
                ) : (
                  recentFeedback.map((feedback, idx) => (
                    <div key={idx} className="p-3 bg-gray-50 rounded-lg">
                      <div className="flex items-center justify-between mb-2">
                        <span className="font-medium text-sm text-gray-900">{feedback.mentee_name || 'Anonymous'}</span>
                        <div className="flex">
                          {[...Array(5)].map((_, i) => (
                            <Star
                              key={i}
                              className={`w-4 h-4 ${i < (feedback.rating || 5) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'}`}
                            />
                          ))}
                        </div>
                      </div>
                      <p className="text-xs text-gray-600">{feedback.comment || 'Great session! Very helpful and insightful.'}</p>
                      <p className="text-xs text-gray-400 mt-1">{feedback.date || '2 days ago'}</p>
                    </div>
                  ))
                )}
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">This Month</h2>
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">Sessions Completed</span>
                  <span className="font-semibold text-gray-900">24</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div className="bg-green-600 h-2 rounded-full" style={{ width: '80%' }}></div>
                </div>

                <div className="flex items-center justify-between mt-4">
                  <span className="text-sm text-gray-600">Response Rate</span>
                  <span className="font-semibold text-gray-900">98%</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div className="bg-blue-600 h-2 rounded-full" style={{ width: '98%' }}></div>
                </div>

                <div className="flex items-center justify-between mt-4">
                  <span className="text-sm text-gray-600">Satisfaction</span>
                  <span className="font-semibold text-gray-900">4.9/5</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div className="bg-yellow-600 h-2 rounded-full" style={{ width: '98%' }}></div>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
              <div className="space-y-3">
                <button
                  onClick={() => window.location.href = '/mentor/schedule'}
                  className="w-full px-4 py-3 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 text-left font-medium flex items-center"
                >
                  <Calendar className="w-4 h-4 mr-2" />
                  Update Availability
                </button>
                <button className="w-full px-4 py-3 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 text-left font-medium flex items-center">
                  <BookOpen className="w-4 h-4 mr-2" />
                  Upload Resource
                </button>
                <button className="w-full px-4 py-3 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 text-left font-medium flex items-center">
                  <MessageSquare className="w-4 h-4 mr-2" />
                  View Messages
                </button>
                <button className="w-full px-4 py-3 bg-orange-50 text-orange-600 rounded-lg hover:bg-orange-100 text-left font-medium flex items-center">
                  <TrendingUp className="w-4 h-4 mr-2" />
                  View Analytics
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}