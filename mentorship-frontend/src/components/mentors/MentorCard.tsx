import React from 'react';
import { Star, MapPin, DollarSign, Award, Calendar, Heart } from 'lucide-react';
import { User, MentorProfile } from '@/types';

interface MentorCardProps {
  mentor: User & {
    mentor_profile?: MentorProfile;
    rating?: number;
    reviews?: number;
    sessions?: number;
    hourly_rate?: number;
    location?: string;
    expertise?: string[];
    title?: string;
    bio?: string;
  };
  onViewProfile?: (mentorId: number) => void;
  onBookSession?: (mentorId: number) => void;
  onToggleFavorite?: (mentorId: number) => void;
  isFavorite?: boolean;
}

export default function MentorCard({
  mentor,
  onViewProfile,
  onBookSession,
  onToggleFavorite,
  isFavorite = false
}: MentorCardProps) {
  const mentorProfile = mentor.mentor_profile;
  const displayName = mentor.name || 'Unknown Mentor';
  const displayTitle = mentor.title || mentorProfile?.job_title || 'Professional Mentor';
  const displayRating = mentor.rating || mentorProfile?.rating || 4.8;
  const displayExpertise = mentor.expertise || mentorProfile?.expertise_areas || ['React', 'Node.js'];
  const displayBio = mentor.bio || 'Experienced professional helping others grow in their careers.';
  const displayLocation = mentor.location || 'Malaysia';
  const displayRate = mentor.hourly_rate || 50;
  const displaySessions = mentor.sessions || mentorProfile?.total_mentees || 50;
  const displayReviews = mentor.reviews || 24;

  const handleViewProfile = () => {
    if (onViewProfile) {
      onViewProfile(mentor.id);
    } else {
      window.location.href = `/mentor/${mentor.id}`;
    }
  };

  const handleBookSession = () => {
    if (onBookSession) {
      onBookSession(mentor.id);
    } else {
      window.location.href = `/mentee/book/${mentor.id}`;
    }
  };

  const handleToggleFavorite = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onToggleFavorite) {
      onToggleFavorite(mentor.id);
    }
  };

  return (
    <div className="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden">
      <div className="p-6 bg-gradient-to-br from-indigo-500 to-purple-600 text-white">
        <div className="flex items-start justify-between mb-4">
          <div className="w-16 h-16 rounded-full flex items-center justify-center text-indigo-600 font-bold text-2xl shadow-sm overflow-hidden bg-white border-2 border-white/20">
            {mentor.profile_image ? (
              <img
                src={mentor.profile_image}
                alt={displayName}
                className="w-full h-full object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center bg-white">
                {displayName.charAt(0)}
              </div>
            )}
          </div>
          <button
            onClick={handleToggleFavorite}
            className="p-2 bg-white/20 rounded-full hover:bg-white/30 transition"
          >
            <Heart
              className={`w-5 h-5 ${isFavorite ? 'fill-white' : ''}`}
            />
          </button>
        </div>
        <h3 className="text-xl font-bold mb-1">{displayName}</h3>
        <p className="text-indigo-100 text-sm">{displayTitle}</p>
      </div>

      <div className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center">
            <Star className="w-4 h-4 text-yellow-400 fill-yellow-400 mr-1" />
            <span className="font-semibold">{displayRating}</span>
            <span className="text-gray-500 text-sm ml-1">({displayReviews})</span>
          </div>
          <div className="flex items-center text-gray-600 text-sm">
            <Award className="w-4 h-4 mr-1" />
            {displaySessions}+ sessions
          </div>
        </div>

        <p className="text-gray-600 text-sm mb-4 line-clamp-2">
          {displayBio}
        </p>

        <div className="flex flex-wrap gap-2 mb-4">
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
        </div>

        <div className="flex items-center justify-between text-sm mb-4">
          <div className="flex items-center text-gray-600">
            <MapPin className="w-4 h-4 mr-1" />
            {displayLocation}
          </div>
          <div className="flex items-center font-semibold text-gray-900">
            <DollarSign className="w-4 h-4" />
            RM{displayRate}/hr
          </div>
        </div>

        <div className="flex space-x-2">
          <button
            onClick={handleViewProfile}
            className="flex-1 px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 font-medium transition"
          >
            View Profile
          </button>
          <button
            onClick={handleBookSession}
            className="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium flex items-center justify-center transition"
          >
            <Calendar className="w-4 h-4 mr-1" />
            Book
          </button>
        </div>
      </div>
    </div>
  );
}
