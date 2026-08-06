'use client';

import { useState, useEffect, useCallback } from 'react';
import {
  Search, Filter, Star, MapPin, Calendar,
  Award, Users, Heart, X, ArrowLeft, Navigation
} from 'lucide-react';
import { api } from '@/lib/api';

interface Mentor {
  id: number;
  name?: string;
  title?: string;
  rating?: number;
  reviews?: number;
  total_reviews?: number;
  sessions?: number;
  bio?: string;
  expertise?: string[];
  location?: string;
  hourly_rate?: number;
  available_slots_count?: number;
  next_available_slot?: {
    date: string;
    start_time: string;
    end_time: string;
    fee: number;
  } | null;
  mentor_profile?: { hourly_rate?: number };
  mentorProfile?: { hourly_rate?: number };
}

interface NearbyMentor {
  id: number;
  name: string;
  title?: string;
  distance_km?: number;
  latitude?: number;
  longitude?: number;
  address?: string;
  fake_address?: string;
}

const DEFAULT_LOCATION = { lat: 3.139, lng: 101.6869 };

export default function FindMentors() {
  const [filteredMentors, setFilteredMentors] = useState<Mentor[]>([]);
  const [loading, setLoading] = useState(true);
  const [isFetching, setIsFetching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [selectedExpertise, setSelectedExpertise] = useState<string[]>([]);
  const [priceRange, setPriceRange] = useState([0, 500]);
  const [minRating, setMinRating] = useState(0);
  const [sortBy, setSortBy] = useState('recommended');
  const [showFilters, setShowFilters] = useState(false);
  const [availabilityFilter, setAvailabilityFilter] = useState<'all' | 'available' | 'unavailable'>('all');
  const [locating, setLocating] = useState(true);
  const [nearbyError, setNearbyError] = useState('');
  const [nearbyMentors, setNearbyMentors] = useState<NearbyMentor[]>([]);
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [radiusKm, setRadiusKm] = useState(30);

  const [expertiseOptions, setExpertiseOptions] = useState<string[]>([]);
  const [favoriteIds, setFavoriteIds] = useState<Set<number>>(new Set());

  // Fetch favorite mentors
  useEffect(() => {
    const fetchFavorites = async () => {
      try {
        const res = await api.get('/favorites');
        const favoritesData = res.data;
        if (Array.isArray(favoritesData)) {
          const ids = new Set<number>(favoritesData.map((m: any) => m.id));
          setFavoriteIds(ids);
        }
      } catch (err) {
        console.error('Failed to fetch favorites:', err);
      }
    };
    fetchFavorites();
  }, []);

  const handleToggleFavorite = async (e: React.MouseEvent, mentorId: number | string) => {
    e.preventDefault();
    e.stopPropagation();
    const id = Number(mentorId);
    try {
      const res = await api.post('/favorites/toggle', { mentor_id: id });
      const { is_favorited } = res.data;
      setFavoriteIds(prev => {
        const newSet = new Set(prev);
        if (is_favorited) newSet.add(id);
        else newSet.delete(id);
        return newSet;
      });
    } catch (err) {
      console.error('Failed to toggle favorite:', err);
    }
  };

  // Fetch all available skills from the API
  useEffect(() => {
    const fetchSkills = async () => {
      try {
        const res = await api.get('/mentors/all-skills');
        if (Array.isArray(res.data)) {
          setExpertiseOptions(res.data);
        } else if (Array.isArray(res)) {
          setExpertiseOptions(res);
        }
      } catch (err) {
        console.error('Failed to fetch skills:', err);
      }
    };
    fetchSkills();
  }, []);

  // Debounce the search input
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchQuery]);

  const fetchMentors = useCallback(async () => {
    setIsFetching(true);
    try {
      // Build query parameters for backend filtering
      const params = new URLSearchParams();

      if (debouncedSearch) {
        params.append('search', debouncedSearch);
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

      if (sortBy && sortBy !== 'recommended') {
        params.append('sort_by', sortBy);
      }

      params.append('per_page', '50');

      const queryString = params.toString();
      const response = await api.get(`/mentors${queryString ? '?' + queryString : ''}`);
      const mentorsData: Mentor[] = Array.isArray(response)
        ? response
        : Array.isArray(response.data)
          ? response.data
          : [];

      // Prioritize available mentors at the top, preserving the backend sorting order within the groups
      mentorsData.sort((a, b) => {
        const aAvail = (a.available_slots_count ?? 0) > 0 ? 1 : 0;
        const bAvail = (b.available_slots_count ?? 0) > 0 ? 1 : 0;
        if (aAvail !== bAvail) {
          return bAvail - aAvail;
        }
        return 0;
      });

      setFilteredMentors(mentorsData);
    } catch (error) {
      console.error('Error fetching mentors:', error);
    } finally {
      setLoading(false);
      setIsFetching(false);
    }
  }, [debouncedSearch, selectedExpertise, priceRange, minRating, sortBy]);

  useEffect(() => {
    fetchMentors();
  }, [fetchMentors]);

  useEffect(() => {
    if (!navigator.geolocation) {
      setNearbyError('Geolocation is not supported in your browser. Showing default nearby mentors.');
      setUserLocation(DEFAULT_LOCATION);
      fetchNearbyMentors(DEFAULT_LOCATION.lat, DEFAULT_LOCATION.lng);
      setLocating(false);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const currentLocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };
        setUserLocation(currentLocation);
        await fetchNearbyMentors(currentLocation.lat, currentLocation.lng, 30);
        setLocating(false);
      },
      () => {
        setNearbyError('Location access denied. Showing default nearby mentors.');
        setUserLocation(DEFAULT_LOCATION);
        fetchNearbyMentors(DEFAULT_LOCATION.lat, DEFAULT_LOCATION.lng, 30);
        setLocating(false);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, []);

  const fetchNearbyMentors = async (lat: number, lng: number, radius: number = radiusKm) => {
    try {
      const response = await api.get(`/mentors/nearby?lat=${lat}&lng=${lng}&radius_km=${radius}`);
      const nearbyData = Array.isArray(response)
        ? response
        : Array.isArray(response?.data)
          ? response.data
          : [];
      setNearbyMentors(nearbyData);
      setNearbyError(''); // Clear error if successful
    } catch (error) {
      console.error('Error fetching nearby mentors:', error);
      setNearbyError('Unable to load nearby mentors right now.');
    }
  };

  // Re-fetch nearby mentors when radius changes
  useEffect(() => {
    if (userLocation) {
      fetchNearbyMentors(userLocation.lat, userLocation.lng, radiusKm);
    }
  }, [radiusKm]);

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

  const getMapsDirectionsUrl = (lat?: number, lng?: number) => {
    if (typeof lat !== 'number' || typeof lng !== 'number') {
      return 'https://maps.google.com';
    }
    return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
  };

  const nearbyMap = new Map<number, NearbyMentor>();
  nearbyMentors.forEach((mentor) => {
    nearbyMap.set(mentor.id, mentor);
  });

  const visibleMentorsBase = [...filteredMentors].sort((a, b) => {
    const aAvail = (a.available_slots_count ?? 0) > 0 ? 1 : 0;
    const bAvail = (b.available_slots_count ?? 0) > 0 ? 1 : 0;
    if (aAvail !== bAvail) return bAvail - aAvail;
    if (sortBy === 'recommended') {
      const distA = nearbyMap.get(a.id)?.distance_km ?? 9999;
      const distB = nearbyMap.get(b.id)?.distance_km ?? 9999;
      return distA - distB;
    }
    return 0;
  });

  const visibleMentors = visibleMentorsBase.filter(mentor => {
    if (availabilityFilter === 'available') return (mentor.available_slots_count ?? 0) > 0;
    if (availabilityFilter === 'unavailable') return (mentor.available_slots_count ?? 0) === 0;
    return true;
  });

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
      <header className="bg-slate-900 shadow-md relative overflow-hidden">
        {/* Subtle decorative background blur */}
        <div className="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
        <div className="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-indigo-900/20 blur-3xl"></div>
        
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative z-10">
          <div className="flex items-center justify-between">
            <div>
              <button
                onClick={() => window.location.href = '/mentee/dashboard'}
                className="inline-flex items-center px-3 py-1.5 rounded-full bg-white/10 text-indigo-50 hover:bg-white/20 hover:text-white mb-4 text-sm font-medium transition backdrop-blur-sm border border-white/10"
              >
                <ArrowLeft className="w-4 h-4 mr-1.5" />
                Back to Dashboard
              </button>
              <h1 className="text-4xl font-extrabold text-white tracking-tight">Find Your Mentor</h1>
              <p className="text-indigo-100 mt-2 text-lg max-w-2xl">Connect with experienced professionals and supercharge your career growth.</p>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-6 border border-gray-100">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
              <MapPin className="w-5 h-5 text-indigo-600" />
              Nearby Mentors
            </h2>
            {userLocation && (
              <a
                href={`https://www.google.com/maps/search/?api=1&query=${userLocation.lat},${userLocation.lng}`}
                target="_blank"
                rel="noopener noreferrer"
                className="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
              >
                Open My Location
              </a>
            )}
          </div>
          
          <div className="mb-4">
            <div className="flex justify-between items-center mb-2">
              <label className="text-sm font-medium text-gray-700">Search Radius</label>
              <span className="text-sm font-semibold text-indigo-600">{radiusKm} km</span>
            </div>
            <input
              type="range"
              min="5"
              max="1000"
              step="5"
              value={radiusKm}
              onChange={(e) => setRadiusKm(parseInt(e.target.value))}
              className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span>5 km</span>
              <span>1000 km</span>
            </div>
          </div>

          {locating && (
            <div className="flex items-center text-sm text-gray-500 bg-gray-50 p-4 rounded-xl border border-gray-100">
              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-400 mr-2"></div>
              Detecting your location...
            </div>
          )}

          {!locating && nearbyError && (
            <div className="mb-4 bg-amber-50/50 border border-amber-200/60 rounded-xl p-4 flex items-start">
              <div className="flex-1">
                <p className="text-sm font-medium text-amber-800">{nearbyError}</p>
              </div>
            </div>
          )}

          {!locating && userLocation && (
            <>
              <div className="rounded-xl overflow-hidden border border-gray-200 mb-5 shadow-sm ring-1 ring-black/5">
                <iframe
                  title="Nearby mentors map"
                  src={`https://maps.google.com/maps?q=${userLocation.lat},${userLocation.lng}&z=12&output=embed`}
                  className="w-full h-72"
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                />
              </div>

              {nearbyMentors.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {nearbyMentors.slice(0, 6).map((mentor) => (
                    <div key={mentor.id} className="rounded-xl border border-gray-200 p-4 bg-white shadow-sm hover:shadow-md transition">
                      <p className="font-bold text-gray-900 text-base">{mentor.name}</p>
                      <p className="text-xs text-indigo-600 font-medium mb-2">{mentor.title || 'Mentor'}</p>
                      <div className="flex items-start gap-2 mt-2">
                        <MapPin className="w-3.5 h-3.5 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                          <p className="text-xs text-gray-600 line-clamp-1">{mentor.address || mentor.fake_address || 'Kuala Lumpur'}</p>
                          <p className="text-xs text-gray-900 font-bold mt-0.5">{mentor.distance_km?.toFixed(1) || '0.0'} km away</p>
                        </div>
                      </div>
                      <a
                        href={getMapsDirectionsUrl(mentor.latitude, mentor.longitude)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 bg-indigo-50 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 rounded-lg transition w-full justify-center"
                      >
                        <Navigation className="w-3.5 h-3.5" />
                        Navigate
                      </a>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 bg-gray-50 rounded-xl border border-gray-100 border-dashed">
                  <MapPin className="w-8 h-8 text-gray-300 mx-auto mb-2" />
                  <p className="text-sm text-gray-500 font-medium">No mentors found within {radiusKm}km.</p>
                </div>
              )}
            </>
          )}
        </div>

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

        <div className="mb-4 flex items-center justify-between flex-wrap gap-3">
          <div className="flex items-center gap-3">
            <p className="text-gray-600">
              Found <span className="font-semibold text-gray-900">{visibleMentors.length}</span> mentors nearby
              {isFetching && <span className="ml-2 text-xs text-indigo-500 animate-pulse">Updating...</span>}
            </p>
            {/* Availability Filter Toggle Buttons */}
            <div className="flex items-center gap-1.5 bg-gray-100 p-1 rounded-lg">
              <button
                onClick={() => setAvailabilityFilter('all')}
                className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                  availabilityFilter === 'all'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                All
              </button>
              <button
                onClick={() => setAvailabilityFilter('available')}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                  availabilityFilter === 'available'
                    ? 'bg-green-500 text-white shadow-sm'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                <span className={`w-1.5 h-1.5 rounded-full ${
                  availabilityFilter === 'available' ? 'bg-white' : 'bg-green-400'
                }`} />
                Available
              </button>
              <button
                onClick={() => setAvailabilityFilter('unavailable')}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                  availabilityFilter === 'unavailable'
                    ? 'bg-gray-500 text-white shadow-sm'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                <span className={`w-1.5 h-1.5 rounded-full ${
                  availabilityFilter === 'unavailable' ? 'bg-white/60' : 'bg-gray-400'
                }`} />
                Unavailable
              </button>
            </div>
          </div>
          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
          >
            <option value="recommended">Sort by: Recommended</option>
            <option value="rating">Highest Rated</option>
            <option value="experience">Most Experienced</option>
            <option value="price_low">Price: Low to High</option>
            <option value="price_high">Price: High to Low</option>
          </select>
        </div>

        {visibleMentors.length === 0 ? (
          <div className="bg-white rounded-xl shadow-sm p-12 text-center">
            <Users className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">No mentors found</h3>
            <p className="text-gray-600">Try adjusting your search or filters</p>
          </div>
        ) : (
          <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-200 ${isFetching ? 'opacity-60' : 'opacity-100'}`}>
            {visibleMentors.map((mentor, idx) => (
              <div key={idx} className="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group">
                <div className="p-6 bg-slate-900 text-white relative">
                  <div className="flex items-start justify-between mb-4">
                    <div className="w-16 h-16 bg-white rounded-full flex items-center justify-center text-indigo-600 font-bold text-2xl shadow-lg">
                      {mentor.name?.charAt(0) || 'M'}
                    </div>
                    <div className="flex flex-col items-end gap-2">
                      {/* Availability Badge — prominent, on the gradient */}
                      {(mentor.available_slots_count ?? 0) > 0 ? (
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/30 text-white shadow-sm">
                          <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-300 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                          </span>
                          Available
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-black/20 backdrop-blur-sm border border-white/10 text-white/60">
                          <span className="w-2 h-2 rounded-full bg-white/40 inline-block"></span>
                          Unavailable
                        </span>
                      )}
                      <button 
                        type="button"
                        onClick={(e) => handleToggleFavorite(e, mentor.id)}
                        className="p-2 bg-white/20 rounded-full hover:bg-white/30 transition relative z-20"
                        title="Toggle Favorite"
                      >
                        <Heart 
                          className="w-5 h-5 transition-colors" 
                          fill={favoriteIds.has(Number(mentor.id)) ? 'currentColor' : 'transparent'}
                          color={favoriteIds.has(Number(mentor.id)) ? '#ef4444' : 'white'}
                        />
                      </button>
                    </div>
                  </div>
                  <h3 className="text-xl font-bold mb-1">{mentor.name || 'Unknown Mentor'}</h3>
                  <p className="text-indigo-100 text-sm">{mentor.title || 'Professional Mentor'}</p>
                </div>

                <div className="p-6">
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center">
                      <Star className="w-4 h-4 text-yellow-400 fill-yellow-400 mr-1" />
                      <span className="font-semibold">{mentor.rating || 0}</span>
                      <span className="text-gray-500 text-sm ml-1">({mentor.total_reviews ?? mentor.reviews ?? 0})</span>
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
                    {(() => {
                      let parsedSkills = mentor.skills;
                      if (typeof parsedSkills === 'string') {
                          try { parsedSkills = JSON.parse(parsedSkills); } catch(e) { parsedSkills = []; }
                      }
                      const displayExpertise = parsedSkills || mentor.expertise || mentor.mentor_profile?.expertise_areas || [];
                      return (
                        <>
                          {displayExpertise.slice(0, 3).map((skill: string, i: number) => (
                            <span key={i} className="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-full">
                              {skill}
                            </span>
                          ))}
                          {displayExpertise.length > 3 && (
                            <span className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                              +{displayExpertise.length - 3} more
                            </span>
                          )}
                        </>
                      );
                    })()}
                  </div>

                  <div className="flex items-center justify-between text-sm mb-3">
                    <div className="flex items-center text-gray-500">
                      <MapPin className="w-4 h-4 mr-1" />
                      <span className="truncate max-w-[140px]">
                        {nearbyMap.get(mentor.id)?.distance_km
                          ? `${nearbyMap.get(mentor.id)?.distance_km?.toFixed(2)} km away`
                          : (nearbyMap.get(mentor.id)?.address || mentor.location || 'Malaysia')}
                      </span>
                    </div>
                    <span className="text-xs text-gray-400 font-medium">
                      {(mentor.available_slots_count ?? 0) > 0
                        ? `${mentor.available_slots_count} slot${mentor.available_slots_count !== 1 ? 's' : ''} open`
                        : 'No slots'}
                    </span>
                  </div>

                  {/* Next available slot info */}
                  {mentor.next_available_slot && (
                    <div className="mb-3 px-3 py-2 bg-green-50 border border-green-100 rounded-lg">
                      <p className="text-xs text-green-700 font-medium">
                        Next: {new Date(mentor.next_available_slot.date).toLocaleDateString('en-MY', { weekday: 'short', day: 'numeric', month: 'short' })}
                        {' · '}{mentor.next_available_slot.start_time.slice(0, 5)} – {mentor.next_available_slot.end_time.slice(0, 5)}
                      </p>
                      <p className="text-xs text-green-600 font-semibold mt-0.5">RM {Number(mentor.next_available_slot.fee).toFixed(2)} per session</p>
                    </div>
                  )}

                  <div className="flex space-x-2">
                    <button
                      onClick={() => window.location.href = `/mentee/mentors/${mentor.id}`}
                      className="flex-1 px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 font-medium transition text-sm"
                    >
                      View Profile
                    </button>
                    {(mentor.available_slots_count ?? 0) > 0 ? (
                      <button
                        onClick={() => handleBookSession(mentor.id)}
                        className="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium flex items-center justify-center transition text-sm"
                      >
                        <Calendar className="w-4 h-4 mr-1" />
                        Book
                      </button>
                    ) : (
                      <button
                        disabled
                        className="flex-1 px-4 py-2 bg-gray-100 text-gray-400 rounded-lg font-medium flex items-center justify-center text-sm cursor-not-allowed"
                        title="No available slots"
                      >
                        <Calendar className="w-4 h-4 mr-1 opacity-50" />
                        No Slots
                      </button>
                    )}
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