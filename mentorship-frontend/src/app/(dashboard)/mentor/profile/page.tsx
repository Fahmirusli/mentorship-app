'use client';

import { useState, useEffect } from 'react';
import { User, Mail, Phone, Briefcase, DollarSign, Save, Loader, Award } from 'lucide-react';
import { api } from '@/lib/api';
import { authService } from '@/lib/auth';

export default function MentorProfile() {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploadingImage, setUploadingImage] = useState(false);
    const [image, setImage] = useState<string | null>(null);
    const [profile, setProfile] = useState({
        name: '',
        email: '',
        phone: '',
        bio: '',
        job_title: '',
        company: '',
        years_of_experience: 0,
        expertise_areas: [] as string[],
        industry: '',
        hourly_rate: 50,
        mentorship_approach: '',
        is_available: true
    });
    const [newExpertise, setNewExpertise] = useState('');

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        try {
            const response = await api.get('/user');

            setProfile({
                name: response.name || '',
                email: response.email || '',
                phone: response.phone || '',
                bio: response.bio || '',
                job_title: response.mentor_profile?.job_title || '',
                company: response.mentor_profile?.company || '',
                years_of_experience: response.mentor_profile?.years_of_experience || 0,
                expertise_areas: response.mentor_profile?.expertise_areas || [],
                industry: response.mentor_profile?.industry || '',
                hourly_rate: response.mentor_profile?.hourly_rate || 50,
                mentorship_approach: response.mentor_profile?.mentorship_approach || '',
                is_available: response.mentor_profile?.is_available ?? true
            });
            setImage(response.profile_image || null);
        } catch (error) {
            console.error('Error fetching profile:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            await api.put('/user/profile', {
                name: profile.name,
                phone: profile.phone,
                bio: profile.bio
            });

            await api.put('/mentors/profile', {
                job_title: profile.job_title,
                company: profile.company,
                years_of_experience: profile.years_of_experience,
                expertise_areas: profile.expertise_areas,
                industry: profile.industry,
                hourly_rate: profile.hourly_rate,
                mentorship_approach: profile.mentorship_approach,
                is_available: profile.is_available
            });

            alert('Profile updated successfully!');
        } catch (error) {
            console.error('Error saving profile:', error);
            alert('Failed to update profile');
        } finally {
            setSaving(false);
        }
    };

    const addExpertise = () => {
        if (newExpertise && !profile.expertise_areas.includes(newExpertise)) {
            setProfile({ ...profile, expertise_areas: [...profile.expertise_areas, newExpertise] });
            setNewExpertise('');
        }
    };

    const removeExpertise = (expertise: string) => {
        setProfile({ ...profile, expertise_areas: profile.expertise_areas.filter(e => e !== expertise) });
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <Loader className="w-8 h-8 animate-spin text-indigo-600" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-4xl mx-auto">
                <div className="bg-white rounded-xl shadow-sm p-8">
                    <h1 className="text-3xl font-bold text-gray-900 mb-8">Mentor Profile</h1>

                    {/* Profile Image */}
                    <div className="flex items-center space-x-6 mb-8 pb-8 border-b">
                        <div className="relative">
                            <div className="w-24 h-24 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center border-2 border-indigo-100">
                                {image ? (
                                    <img src={image} alt="Profile" className="w-full h-full object-cover" />
                                ) : (
                                    <User className="w-12 h-12 text-gray-400" />
                                )}
                            </div>
                            <label className="absolute bottom-0 right-0 bg-indigo-600 text-white p-2 rounded-full cursor-pointer hover:bg-indigo-700 shadow-md transition-all">
                                {uploadingImage ? (
                                    <Loader className="w-4 h-4 animate-spin" />
                                ) : (
                                    <div className="w-4 h-4 flex items-center justify-center">+</div>
                                )}
                                <input
                                    type="file"
                                    className="hidden"
                                    accept="image/*"
                                    onChange={async (e) => {
                                        const file = e.target.files?.[0];
                                        if (!file) return;

                                        // Preview immediately
                                        const previewUrl = URL.createObjectURL(file);
                                        setImage(previewUrl);

                                        setUploadingImage(true);
                                        const formData = new FormData();
                                        formData.append('image', file);
                                        try {
                                            const res = await api.post('/user/profile-image', formData);
                                            setImage(res.image_url);
                                        } catch (err) {
                                            console.error(err);
                                            alert('Failed to upload image');
                                        } finally {
                                            setUploadingImage(false);
                                        }
                                    }}
                                />
                            </label>
                        </div>
                        <div>
                            <h3 className="text-lg font-medium text-gray-900">Profile Photo</h3>
                            <p className="text-sm text-gray-500">Update your profile picture. JPG, PNG or GIF.</p>
                        </div>
                    </div>

                    {/* Basic Information */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Basic Information</h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <User className="w-4 h-4 inline mr-2" />
                                    Full Name
                                </label>
                                <input
                                    type="text"
                                    value={profile.name}
                                    onChange={(e) => setProfile({ ...profile, name: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Mail className="w-4 h-4 inline mr-2" />
                                    Email
                                </label>
                                <input
                                    type="email"
                                    value={profile.email}
                                    disabled
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Phone className="w-4 h-4 inline mr-2" />
                                    Phone
                                </label>
                                <input
                                    type="tel"
                                    value={profile.phone}
                                    onChange={(e) => setProfile({ ...profile, phone: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Briefcase className="w-4 h-4 inline mr-2" />
                                    Job Title
                                </label>
                                <input
                                    type="text"
                                    value={profile.job_title}
                                    onChange={(e) => setProfile({ ...profile, job_title: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    placeholder="e.g., Senior Software Engineer"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Company</label>
                                <input
                                    type="text"
                                    value={profile.company}
                                    onChange={(e) => setProfile({ ...profile, company: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    placeholder="e.g., Google"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                                <input
                                    type="text"
                                    value={profile.industry}
                                    onChange={(e) => setProfile({ ...profile, industry: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    placeholder="e.g., Technology"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Award className="w-4 h-4 inline mr-2" />
                                    Years of Experience
                                </label>
                                <input
                                    type="number"
                                    value={profile.years_of_experience}
                                    onChange={(e) => setProfile({ ...profile, years_of_experience: parseInt(e.target.value) || 0 })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    min="0"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <DollarSign className="w-4 h-4 inline mr-2" />
                                    Hourly Rate (RM)
                                </label>
                                <input
                                    type="number"
                                    value={profile.hourly_rate}
                                    onChange={(e) => setProfile({ ...profile, hourly_rate: parseInt(e.target.value) || 0 })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    min="0"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                            <textarea
                                value={profile.bio}
                                onChange={(e) => setProfile({ ...profile, bio: e.target.value })}
                                rows={4}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Tell mentees about yourself..."
                            />
                        </div>
                    </div>

                    {/* Expertise Areas */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Expertise Areas</h2>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={newExpertise}
                                onChange={(e) => setNewExpertise(e.target.value)}
                                onKeyPress={(e) => e.key === 'Enter' && addExpertise()}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Add expertise (e.g., React, Leadership, Data Science)"
                            />
                            <button
                                onClick={addExpertise}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                            >
                                Add
                            </button>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {profile.expertise_areas.map((expertise, index) => (
                                <span
                                    key={index}
                                    className="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm flex items-center gap-2"
                                >
                                    {expertise}
                                    <button
                                        onClick={() => removeExpertise(expertise)}
                                        className="text-indigo-500 hover:text-indigo-700"
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Mentorship Approach */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Mentorship Approach</h2>
                        <textarea
                            value={profile.mentorship_approach}
                            onChange={(e) => setProfile({ ...profile, mentorship_approach: e.target.value })}
                            rows={4}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                            placeholder="Describe your mentorship style and approach..."
                        />
                    </div>

                    {/* Availability */}
                    <div className="space-y-6 mb-8">
                        <h2 className="text-xl font-semibold text-gray-900">Availability</h2>
                        <label className="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={profile.is_available}
                                onChange={(e) => setProfile({ ...profile, is_available: e.target.checked })}
                                className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            />
                            <span className="text-gray-700">I am currently available for new mentees</span>
                        </label>
                    </div>

                    {/* Save Button */}
                    <button
                        onClick={handleSave}
                        disabled={saving}
                        className="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        {saving ? (
                            <>
                                <Loader className="w-5 h-5 animate-spin" />
                                Saving...
                            </>
                        ) : (
                            <>
                                <Save className="w-5 h-5" />
                                Save Profile
                            </>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}
