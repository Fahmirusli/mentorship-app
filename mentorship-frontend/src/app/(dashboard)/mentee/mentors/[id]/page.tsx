'use client';

import { useState, useEffect, use } from 'react';
import {
  ArrowLeft, Star, MapPin, Briefcase, Calendar, Award, Clock,
  DollarSign, Mail, Heart, CheckCircle, Users
} from 'lucide-react';
import { api } from '@/lib/api';

export default function MentorProfile({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params);
  const [mentor, setMentor] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('about');

  useEffect(() => {
    fetchMentorProfile();
  }, [id]);

  const fetchMentorProfile = async () => {
    try {
      const response = await api.get(`/mentors/${id}`);
      setMentor(response);
      console.log('Mentor data:', response);
    } catch (error) {
      console.error('Error fetching mentor:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleBookSession = () => {
    window.location.href = `/mentee/book/${id}`;
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading profile...</p>
        </div>
      </div>
    );
  }

  if (!mentor) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Mentor not found</h2>
          <button
            onClick={() => window.location.href = '/mentee/mentors'}
            className="text-indigo-600 hover:text-indigo-700"
          >
            Back to mentors
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <button
            onClick={() => window.location.href = '/mentee/mentors'}
            className="flex items-center text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft className="w-5 h-5 mr-2" />
            Back to mentors
          </button>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column - Profile Card */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-xl shadow-sm overflow-hidden sticky top-4">
              <div className="p-6 bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-center">
                <div className="w-24 h-24 bg-white rounded-full flex items-center justify-center text-indigo-600 font-bold text-4xl mx-auto mb-4">
                  {mentor.name?.charAt(0) || 'M'}
                </div>
                <h1 className="text-2xl font-bold mb-1">{mentor.name}</h1>
                <p className="text-indigo-100">
                  {mentor.mentor_profile?.job_title || 'Professional Mentor'}
                </p>
                {mentor.mentor_profile?.company && (
                  <p className="text-indigo-200 text-sm mt-1">
                    @ {mentor.mentor_profile.company}
                  </p>
                )}
              </div>

              <div className="p-6 space-y-4">
                <div className="flex items-center justify-between pb-4 border-b">
                  <div className="text-center flex-1">
                    <div className="flex items-center justify-center mb-1">
                      <Star className="w-5 h-5 text-yellow-400 fill-yellow-400" />
                      <span className="font-bold text-2xl ml-1">
                        {mentor.mentor_profile?.rating || '4.8'}
                      </span>
                    </div>
                    <p className="text-xs text-gray-600">Rating</p>
                  </div>
                  <div className="w-px h-12 bg-gray-200"></div>
                  <div className="text-center flex-1">
                    <div className="font-bold text-2xl text-gray-900">
                      {mentor.mentor_profile?.total_mentees || 0}
                    </div>
                    <p className="text-xs text-gray-600">Mentees</p>
                  </div>
                  <div className="w-px h-12 bg-gray-200"></div>
                  <div className="text-center flex-1">
                    <div className="font-bold text-2xl text-gray-900">
                      {mentor.total_available_slots || 0}
                    </div>
                    <p className="text-xs text-gray-600">Slots</p>
                  </div>
                </div>

                <div className="space-y-3">
                  <div className="flex items-center text-gray-700">
                    <Briefcase className="w-5 h-5 mr-3 text-gray-400" />
                    <span className="text-sm">
                      {mentor.mentor_profile?.years_of_experience || 0} years experience
                    </span>
                  </div>
                  <div className="flex items-center text-gray-700">
                    <MapPin className="w-5 h-5 mr-3 text-gray-400" />
                    <span className="text-sm">
                      {mentor.mentor_profile?.industry || 'Technology'}
                    </span>
                  </div>
                  <div className="flex items-center text-gray-700">
                    <DollarSign className="w-5 h-5 mr-3 text-gray-400" />
                    <span className="text-sm font-semibold">
                      RM {mentor.mentor_profile?.hourly_rate || '50'}/hour
                    </span>
                  </div>
                </div>

                <button
                  onClick={handleBookSession}
                  className="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium flex items-center justify-center"
                >
                  <Calendar className="w-5 h-5 mr-2" />
                  Book a Session
                </button>
              </div>
            </div>
          </div>

          {/* Right Column - Details */}
          <div className="lg:col-span-2 space-y-6">
            {/* Tabs */}
            <div className="bg-white rounded-xl shadow-sm overflow-hidden">
              <div className="border-b border-gray-200">
                <div className="flex">
                  {['about', 'expertise', 'reviews'].map((tab) => (
                    <button
                      key={tab}
                      onClick={() => setActiveTab(tab)}
                      className={`flex-1 px-6 py-4 text-sm font-medium capitalize ${
                        activeTab === tab
                          ? 'border-b-2 border-indigo-600 text-indigo-600'
                          : 'text-gray-600 hover:text-gray-900'
                      }`}
                    >
                      {tab}
                    </button>
                  ))}
                </div>
              </div>

              <div className="p-6">
                {activeTab === 'about' && (
                  <div className="space-y-6">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-3">About Me</h3>
                      <p className="text-gray-700 leading-relaxed">
                        {mentor.bio || 'Experienced professional dedicated to helping others grow in their careers.'}
                      </p>
                    </div>

                    {mentor.mentor_profile?.mentorship_approach && (
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Mentorship Approach</h3>
                        <p className="text-gray-700 leading-relaxed">
                          {mentor.mentor_profile.mentorship_approach}
                        </p>
                      </div>
                    )}

                    {mentor.skills && mentor.skills.length > 0 && (
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Skills</h3>
                        <div className="flex flex-wrap gap-2">
                          {mentor.skills.map((skill: string, i: number) => (
                            <span
                              key={i}
                              className="px-3 py-1 bg-indigo-50 text-indigo-700 text-sm rounded-full"
                            >
                              {skill}
                            </span>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {activeTab === 'expertise' && (
                  <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 mb-3">Areas of Expertise</h3>
                    {mentor.mentor_profile?.expertise_areas?.map((area: string, i: number) => (
                      <div key={i} className="flex items-start">
                        <CheckCircle className="w-5 h-5 text-green-500 mr-3 mt-0.5" />
                        <div>
                          <p className="font-medium text-gray-900">{area}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {activeTab === 'reviews' && (
                  <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 mb-3">
                      Reviews ({mentor.feedback_received?.length || 0})
                    </h3>
                    {mentor.feedback_received && mentor.feedback_received.length > 0 ? (
                      mentor.feedback_received.map((review: any, i: number) => (
                        <div key={i} className="border-b border-gray-200 pb-4 last:border-0">
                          <div className="flex items-center justify-between mb-2">
                            <div className="flex items-center">
                              <div className="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-semibold mr-3">
                                {review.from_user?.name?.charAt(0) || 'U'}
                              </div>
                              <div>
                                <p className="font-medium text-gray-900">
                                  {review.from_user?.name || 'Anonymous'}
                                </p>
                                <div className="flex items-center">
                                  {[...Array(5)].map((_, idx) => (
                                    <Star
                                      key={idx}
                                      className={`w-4 h-4 ${
                                        idx < review.rating
                                          ? 'text-yellow-400 fill-yellow-400'
                                          : 'text-gray-300'
                                      }`}
                                    />
                                  ))}
                                </div>
                              </div>
                            </div>
                            <span className="text-sm text-gray-500">
                              {new Date(review.created_at).toLocaleDateString()}
                            </span>
                          </div>
                          <p className="text-gray-700">{review.comment}</p>
                        </div>
                      ))
                    ) : (
                      <p className="text-gray-600 text-center py-8">No reviews yet</p>
                    )}
                  </div>
                )}
              </div>
            </div>

            {/* Available Schedule Preview */}
            {mentor.available_schedules && Object.keys(mentor.available_schedules).length > 0 && (
              <div className="bg-white rounded-xl shadow-sm p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                  Available Schedule
                </h3>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                  {Object.keys(mentor.available_schedules).slice(0, 6).map((date) => {
                    const slots = mentor.available_schedules[date];
                    return (
                      <div
                        key={date}
                        className="p-3 border border-gray-200 rounded-lg hover:border-indigo-300 transition"
                      >
                        <p className="font-medium text-sm text-gray-900 mb-1">
                          {new Date(date).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                          })}
                        </p>
                        <p className="text-xs text-gray-600">
                          {slots.length} slot{slots.length !== 1 ? 's' : ''} available
                        </p>
                      </div>
                    );
                  })}
                </div>
                <button
                  onClick={handleBookSession}
                  className="mt-4 w-full py-2 text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 font-medium"
                >
                  View Full Schedule
                </button>
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
