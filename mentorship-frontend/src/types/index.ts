// User Types
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'mentor' | 'mentee';
  phone?: string;
  bio?: string;
  skills?: string[];
  interests?: string[];
  profile_image?: string;
  is_active: boolean;
  created_at: string;
  mentor_profile?: MentorProfile;
  mentee_profile?: MenteeProfile;
}

// Mentor Profile
export interface MentorProfile {
  id: number;
  user_id: number;
  expertise_areas: string[];
  industry: string;
  job_title: string;
  company: string;
  years_of_experience: number;
  mentorship_approach?: string;
  is_available: boolean;
  rating: number;
  total_mentees: number;
}

// Mentee Profile
export interface MenteeProfile {
  id: number;
  user_id: number;
  current_skills: string[];
  skills_to_learn: string[];
  career_goals: string;
  education_level: string;
  field_of_study: string;
}

// Job
export interface Job {
  id: number;
  title: string;
  company: string;
  description: string;
  required_skills: string[];
  location?: string;
  job_type?: string;
  experience_level?: string;
  salary_range?: string;
  source_platform: string;
  source_url: string;
  posted_date?: string;
  is_active: boolean;
}

// Job Recommendation
export interface JobRecommendation {
    id: number;
    title: string;
    company: string;
    source: 'JobStreet' | 'LinkedIn' | 'Hiredly'; // Scraped sources [cite: 1958]
    match_score: number; // NLP Cosine Similarity score (0-100) [cite: 1977]
    missing_skills: string[]; // Skill Gap Analysis [cite: 1979]
    url: string;
    description: string;
}

// Mentorship
export interface Mentorship {
  id: number;
  mentor_id: number;
  mentee_id: number;
  status: 'pending' | 'active' | 'completed' | 'cancelled';
  goals?: string;
  start_date?: string;
  end_date?: string;
  created_at: string;
  mentor?: User;
  mentee?: User;
}

// Appointment
export interface Appointment {
  id: number;
  mentorship_id: number;
  scheduled_at: string;
  duration_minutes: number;
  status: 'scheduled' | 'completed' | 'cancelled' | 'rescheduled';
  meeting_link?: string;
  notes?: string;
  mentorship?: Mentorship;
}

// Feedback
export interface Feedback {
  id: number;
  mentorship_id: number;
  from_user_id: number;
  to_user_id: number;
  rating: number;
  comment?: string;
  created_at: string;
  from_user?: User;
  to_user?: User;
}

// Auth
export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: 'mentor' | 'mentee';
  phone?: string;
}

export interface AuthResponse {
  user: User;
  token: string;
  message: string;
}