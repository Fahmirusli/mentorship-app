'use client';

import { useState, useEffect } from 'react';
import {
  Search, Filter, MapPin, Briefcase, Clock, ExternalLink,
  TrendingUp, Target, RefreshCw, Sparkles, ArrowLeft, Heart
} from 'lucide-react';
import { api } from '@/lib/api';
import { toast } from 'react-hot-toast';

export default function JobListings() {
  const [jobs, setJobs] = useState<any[]>([]);
  const [recommendations, setRecommendations] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedSource, setSelectedSource] = useState('all');
  const [showRecommended, setShowRecommended] = useState(true);

  useEffect(() => {
    fetchJobs();
    fetchRecommendations();
  }, []);

  const fetchJobs = async () => {
    try {
      const response = await api.get('/jobs');
      // If response is paginated it might be response.data, otherwise just response
      // Check if 'data' property exists and is an array (pagination structure)
      const jobsData = response.data && Array.isArray(response.data) ? response.data :
        (Array.isArray(response) ? response : []);

      setJobs(jobsData);
    } catch (error) {
      console.error('Error fetching jobs:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchRecommendations = async () => {
    try {
      const response = await api.get('/jobs/recommendations');
      setRecommendations(response.recommendations || []);
    } catch (error) {
      console.error('Error fetching recommendations:', error);
    }
  };

  const handleRefresh = async () => {
    setLoading(true);
    await Promise.all([fetchJobs(), fetchRecommendations()]);
    setLoading(false);
  };

  const filteredJobs = jobs.filter(job => {
    const matchesSearch = job.title?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      job.company?.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesSource = selectedSource === 'all' || job.source === selectedSource;
    return matchesSearch && matchesSource;
  });

  const getMatchColor = (score: number) => {
    if (score >= 80) return 'bg-green-100 text-green-700 border-green-300';
    if (score >= 60) return 'bg-yellow-100 text-yellow-700 border-yellow-300';
    return 'bg-gray-100 text-gray-700 border-gray-300';
  };

  const displayJobs = showRecommended && recommendations.length > 0 ? recommendations : filteredJobs;

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading jobs...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center justify-between">
            <div>
              <button
                onClick={() => window.location.href = '/mentee/dashboard'}
                className="flex items-center text-gray-600 hover:text-gray-900 mb-2"
              >
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back to Dashboard
              </button>
              <h1 className="text-3xl font-bold text-gray-900">Job Opportunities</h1>
              <p className="text-gray-600 mt-1">
                {jobs.length} jobs from JobStreet, LinkedIn & Hiredly
              </p>
            </div>
            <div className="flex items-center space-x-3">
              <button
                onClick={handleRefresh}
                className="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
              >
                <RefreshCw className="w-4 h-4" />
                <span>Refresh</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Toggle Recommendations */}
        {recommendations.length > 0 && (
          <div className="bg-gradient-to-r from-purple-500 to-purple-700 rounded-xl p-6 mb-6 text-white">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <Sparkles className="w-6 h-6" />
                <div>
                  <h2 className="text-xl font-bold">Personalized Recommendations</h2>
                  <p className="text-purple-100 text-sm">
                    {recommendations.length} jobs matched to your skills
                  </p>
                </div>
              </div>
              <button
                onClick={() => setShowRecommended(!showRecommended)}
                className={`px-6 py-3 rounded-lg font-medium transition ${showRecommended
                    ? 'bg-white text-purple-600'
                    : 'bg-white/20 text-white hover:bg-white/30'
                  }`}
              >
                {showRecommended ? 'Show All Jobs' : 'Show Recommendations'}
              </button>
            </div>
          </div>
        )}

        {/* Search & Filters */}
        <div className="bg-white rounded-xl shadow-sm p-4 mb-6">
          <div className="flex items-center space-x-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search jobs by title, company, or skills..."
                className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
            <select
              value={selectedSource}
              onChange={(e) => setSelectedSource(e.target.value)}
              className="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option value="all">All Sources</option>
              <option value="JobStreet">JobStreet</option>
              <option value="LinkedIn">LinkedIn</option>
              <option value="Hiredly">Hiredly</option>
            </select>
          </div>
        </div>

        {/* Jobs List */}
        <div className="space-y-4">
          {displayJobs.length === 0 ? (
            <div className="bg-white rounded-xl shadow-sm p-12 text-center">
              <Briefcase className="w-16 h-16 text-gray-300 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-900 mb-2">No jobs found</h3>
              <p className="text-gray-600">Try adjusting your search filters</p>
            </div>
          ) : (
            displayJobs.map((item, idx) => {
              const job = item.job || item;
              const matchScore = item.match_score;
              const skillGap = item.skill_gap;
              const missingSkills = Array.isArray(item.missing_skills)
                ? item.missing_skills
                : (typeof item.missing_skills === 'string'
                    ? (() => {
                        try {
                          return JSON.parse(item.missing_skills || '[]');
                        } catch {
                          return [];
                        }
                      })()
                    : []);
              const requirements = Array.isArray(job.requirements)
                ? job.requirements
                : (typeof job.requirements === 'string'
                    ? (() => {
                        try {
                          return JSON.parse(job.requirements || '[]');
                        } catch {
                          return [];
                        }
                      })()
                    : []);

              return (
                <div key={idx} className="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
                  <div className="p-6">
                    <div className="flex items-start justify-between mb-4">
                      <div className="flex-1">
                        <div className="flex items-center space-x-3 mb-2">
                          <h3 className="text-xl font-bold text-gray-900">{job.title}</h3>
                          {matchScore !== undefined && (
                            <span className={`px-3 py-1 text-sm font-semibold rounded-full border-2 ${getMatchColor(matchScore)}`}>
                              {Math.round(matchScore)}% Match
                            </span>
                          )}
                        </div>
                        <p className="text-gray-700 font-medium mb-1">{job.company}</p>
                        <div className="flex items-center space-x-4 text-sm text-gray-500">
                          <div className="flex items-center">
                            <MapPin className="w-4 h-4 mr-1" />
                            {job.location || 'Remote'}
                          </div>
                          <div className="flex items-center">
                            <Clock className="w-4 h-4 mr-1" />
                            {new Date(job.posted_date || job.created_at).toLocaleDateString()}
                          </div>
                          <div className="flex items-center">
                            <Briefcase className="w-4 h-4 mr-1" />
                            {job.source}
                          </div>
                        </div>
                      </div>
                      {job.salary && (
                        <div className="text-right">
                          <p className="text-lg font-bold text-gray-900">{job.salary}</p>
                          <p className="text-xs text-gray-500">per month</p>
                        </div>
                      )}
                    </div>

                    {/* Job Description */}
                    <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                      {job.description}
                    </p>

                    {/* Required Skills */}
                    {requirements.length > 0 && (
                      <div className="mb-4">
                        <p className="text-xs font-semibold text-gray-700 mb-2">Required Skills:</p>
                        <div className="flex flex-wrap gap-2">
                          {requirements.slice(0, 6).map((skill: string, i: number) => {
                            const isMissing = missingSkills.includes(skill);
                            return (
                              <span
                                key={i}
                                className={`px-2 py-1 text-xs rounded-full ${isMissing
                                    ? 'bg-red-50 text-red-700 border border-red-200'
                                    : 'bg-green-50 text-green-700 border border-green-200'
                                  }`}
                              >
                                {skill}
                                {isMissing && ' ⚠️'}
                              </span>
                            );
                          })}
                        </div>
                      </div>
                    )}

                    {/* Skill Gap Warning */}
                    {missingSkills.length > 0 && (
                      <div className="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div className="flex items-start">
                          <Target className="w-5 h-5 text-yellow-600 mr-2 mt-0.5" />
                          <div>
                            <p className="text-sm font-semibold text-yellow-800">
                              You're missing {missingSkills.length} skill{missingSkills.length > 1 ? 's' : ''} for this role
                            </p>
                            <p className="text-xs text-yellow-700 mt-1">
                              Missing: {missingSkills.slice(0, 3).join(', ')}
                              {missingSkills.length > 3 && ` +${missingSkills.length - 3} more`}
                            </p>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Actions */}
                    <div className="flex items-center space-x-3">
                      <button
                        onClick={() => {
                          // TODO: Call API to favorite job
                          toast.success('Job saved to favorites!');
                        }}
                        className="p-2 border border-gray-300 text-gray-500 rounded-lg hover:bg-gray-50 hover:text-red-500 transition-colors"
                        title="Save Job"
                      >
                        <Heart className="w-5 h-5" />
                      </button>
                      <button
                        onClick={() => window.location.href = `/jobs/${job.id}`}
                        className="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium flex items-center justify-center"
                      >
                        <TrendingUp className="w-4 h-4 mr-2" />
                        View Match Analysis
                      </button>
                      <button
                        onClick={() => window.open(job.external_url, '_blank')}
                        className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium flex items-center"
                      >
                        Apply
                        <ExternalLink className="w-4 h-4 ml-2" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </main>
    </div>
  );
}