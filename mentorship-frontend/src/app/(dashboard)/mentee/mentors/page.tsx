'use client';

import { useState, useEffect } from 'react';
import {
  Search, Filter, Star, MapPin, Briefcase, Calendar,
  DollarSign, Award, Users, Heart, X, ArrowLeft
} from 'lucide-react';
import { api } from '@/lib/api';

export default function FindMentors() {
  const [mentors, setMentors] = useState<any[]>([]);
  const [filteredMentors, setFilteredMentors] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedExpertise, setSelectedExpertise] = useState<string[]>([]);
  const [priceRange, setPriceRange] = useState([0, 500]);
  const [minRating, setMinRating] = useState(0);
  const [showFilters, setShowFilters] = useState(false);

  const expertiseOptions = [
    'React', 'Node.js', 'Python', 'Machine Learning', 'Data Science',
    'AWS', 'DevOps', 'UI/UX Design', 'Product Management', 'Mobile Development'
  ];

  useEffect(() => {
    fetchMentors();
  }, [searchQuery, selectedExpertise, priceRange, minRating]);

  const fetchMentors = async () => {
    setLoading(true);
    try {
      // Build query parameters for backend filtering
      const params = new URLSearchParams();
      
      if (searchQuery) {
        params.append('search', searchQuery);
      }
      
      if (selectedExpertise.length > 0) {
        selectedExpertise.forEach(exp => {
          params.append('skills[]', exp);
        });
      }
      
      if (priceRange[0] > 0) {
        params.append('min_price', priceRange[0].toString());
      }
      if (priceRange[1] < 500) {
        params.append('max_price', priceRange[1].toString());
      }
      
      if (minRating > 0) {
        params.append('rating', minRating.toString());
      }
      
      params.append('per_page', '50');
      
      const queryString = params.toString();
      const response = await api.get(`/mentors${queryString ? '?' + queryString : ''}`);
      const mentorsData = response.data || [];
      setMentors(mentorsData);
      setFilteredMentors(mentorsData);
    } catch (error) {
      console.error('Error fetching mentors:', error);
    } finally {
      setLoading(false);
    }
  };

  const toggleExpertise = (exp: string) => {
    if (selectedExpertise.includes(exp)) {
      setSelectedExpertise(selectedExpertise.filter(e => e !== exp));
    } else {
      setSelectedExpertise([...selectedExpertise, exp]);
    }
  };

  const handleBookSession = (mentorId: number) => {
    window.location.href = `/mentee/book/${mentorId}`;
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Finding mentors...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
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
              <h1 className="text-3xl font-bold text-gray-900">Find Your Mentor</h1>
              <p className="text-gray-600 mt-1">Connect with experienced professionals</p>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-xl shadow-sm p-4 mb-6">
          <div className="flex items-center space-x-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search by name, expertise, or keywords..."
                className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
            <button
              onClick={() => setShowFilters(!showFilters)}
              className="flex items-center space-x-2 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              <Filter className="w-5 h-5" />
              <span>Filters</span>
              {(selectedExpertise.length > 0 || minRating > 0) && (
                <span className="bg-indigo-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                  {selectedExpertise.length + (minRating > 0 ? 1 : 0)}
                </span>
              )}
            </button>
          </div>

          {showFilters && (
            <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Expertise</label>
                <div className="flex flex-wrap gap-2">
                  {expertiseOptions.map(exp => (
                    <button
                      key={exp}
                      onClick={() => toggleExpertise(exp)}
                      className={`px-3 py-1 rounded-full text-sm font-medium transition ${selectedExpertise.includes(exp)
                          ? 'bg-indigo-600 text-white'
                          : 'bg-white text-gray-700 border border-gray-300 hover:border-indigo-600'
                        }`}
                    >
                      {exp}
                      {selectedExpertise.includes(exp) && <X className="inline w-3 h-3 ml-1" />}
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Price Range: RM{priceRange[0]} - RM{priceRange[1]}/hour
                </label>
                <input
                  type="range"
                  min="0"
                  max="500"
                  value={priceRange[1]}
                  onChange={(e) => setPriceRange([0, parseInt(e.target.value)])}
                  className="w-full"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Minimum Rating</label>
                <div className="flex space-x-2">
                  {[0, 3, 4, 4.5].map(rating => (
                    <button
                      key={rating}
                      onClick={() => setMinRating(rating)}
                      className={`px-4 py-2 rounded-lg text-sm font-medium transition ${minRating === rating
                          ? 'bg-indigo-600 text-white'
                          : 'bg-white text-gray-700 border border-gray-300 hover:border-indigo-600'
                        }`}
                    >
                      {rating === 0 ? 'Any' : `${rating}+`} ⭐
                    </button>
                  ))}
                </div>
              </div>

              <button
                onClick={() => {
                  setSelectedExpertise([]);
                  setPriceRange([0, 500]);
                  setMinRating(0);
                }}
                className="text-sm text-indigo-600 hover:text-indigo-700"
              >
                Clear all filters
              </button>
            </div>
          )}
        </div>

        <div className="mb-4 flex items-center justify-between">
          <p className="text-gray-600">
            Found <span className="font-semibold text-gray-900">{filteredMentors.length}</span> mentors
          </p>
          <select className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            <option>Sort by: Recommended</option>
            <option>Highest Rated</option>
            <option>Most Experienced</option>
            <option>Price: Low to High</option>
            <option>Price: High to Low</option>
          </select>
        </div>

        {filteredMentors.length === 0 ? (
          <div className="bg-white rounded-xl shadow-sm p-12 text-center">
            <Users className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">No mentors found</h3>
            <p className="text-gray-600">Try adjusting your search or filters</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredMentors.map((mentor, idx) => (
              <div key={idx} className="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden">
                <div className="p-6 bg-gradient-to-br from-indigo-500 to-purple-600 text-white">
                  <div className="flex items-start justify-between mb-4">
                    <div className="w-16 h-16 bg-white rounded-full flex items-center justify-center text-indigo-600 font-bold text-2xl">
                      {mentor.name?.charAt(0) || 'M'}
                    </div>
                    <button className="p-2 bg-white/20 rounded-full hover:bg-white/30">
                      <Heart className="w-5 h-5" />
                    </button>
                  </div>
                  <h3 className="text-xl font-bold mb-1">{mentor.name || 'Unknown Mentor'}</h3>
                  <p className="text-indigo-100 text-sm">{mentor.title || 'Professional Mentor'}</p>
                </div>

                <div className="p-6">
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center">
                      <Star className="w-4 h-4 text-yellow-400 fill-yellow-400 mr-1" />
                      <span className="font-semibold">{mentor.rating || 4.8}</span>
                      <span className="text-gray-500 text-sm ml-1">({mentor.reviews || 24})</span>
                    </div>
                    <div className="flex items-center text-gray-600 text-sm">
                      <Award className="w-4 h-4 mr-1" />
                      {mentor.sessions || 50}+ sessions
                    </div>
                  </div>

                  <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                    {mentor.bio || 'Experienced professional helping others grow in their careers.'}
                  </p>

                  <div className="flex flex-wrap gap-2 mb-4">
                    {(mentor.expertise || ['React', 'Node.js']).slice(0, 3).map((skill: string, i: number) => (
                      <span key={i} className="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-full">
                        {skill}
                      </span>
                    ))}
                    {mentor.expertise?.length > 3 && (
                      <span className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                        +{mentor.expertise.length - 3} more
                      </span>
                    )}
                  </div>

                  <div className="flex items-center justify-between text-sm mb-4">
                    <div className="flex items-center text-gray-600">
                      <MapPin className="w-4 h-4 mr-1" />
                      {mentor.location || 'Malaysia'}
                    </div>
                    <div className="flex items-center font-semibold text-gray-900">
                      RM {mentor.hourly_rate || 50}/hr
                    </div>
                  </div>

                  <div className="flex space-x-2">
                    <button
                      onClick={() => window.location.href = `/mentor/${mentor.id}`}
                      className="flex-1 px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 font-medium"
                    >
                      View Profile
                    </button>
                    <button
                      onClick={() => handleBookSession(mentor.id)}
                      className="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium flex items-center justify-center"
                    >
                      <Calendar className="w-4 h-4 mr-1" />
                      Book
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}