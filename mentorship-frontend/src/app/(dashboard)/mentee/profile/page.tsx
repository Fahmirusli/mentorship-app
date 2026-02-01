'use client';

import { useState, useEffect } from 'react';
import { User, Mail, Phone, MapPin, Briefcase, Save, Loader } from 'lucide-react';
import { api } from '@/lib/api';
import { authService } from '@/lib/auth';

export default function MenteeProfile() {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploadingImage, setUploadingImage] = useState(false);
    const [image, setImage] = useState<string | null>(null);
    const [profile, setProfile] = useState({
        name: '',
        email: '',
        phone: '',
        bio: '',
        location: '',
        current_skills: [] as string[],
        skills_to_learn: [] as string[],
        career_goals: '',
        education_level: '',
        field_of_study: ''
    });
    const [newSkill, setNewSkill] = useState('');
    const [newSkillToLearn, setNewSkillToLearn] = useState('');

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        try {
            const user = authService.getCurrentUser();
            const response = await api.get('/user');

            setProfile({
                name: response.name || '',
                email: response.email || '',
                phone: response.phone || '',
                bio: response.bio || '',
                location: '',
                current_skills: response.mentee_profile?.current_skills || [],
                skills_to_learn: response.mentee_profile?.skills_to_learn || [],
                career_goals: response.mentee_profile?.career_goals || '',
                education_level: response.mentee_profile?.education_level || '',
                field_of_study: response.mentee_profile?.field_of_study || ''
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

            await api.put('/mentees/profile', {
                current_skills: profile.current_skills,
                skills_to_learn: profile.skills_to_learn,
                career_goals: profile.career_goals,
                education_level: profile.education_level,
                field_of_study: profile.field_of_study
            });

            alert('Profile updated successfully!');
        } catch (error) {
            console.error('Error saving profile:', error);
            alert('Failed to update profile');
        } finally {
            setSaving(false);
        }
    };

    const addSkill = () => {
        if (newSkill && !profile.current_skills.includes(newSkill)) {
            setProfile({ ...profile, current_skills: [...profile.current_skills, newSkill] });
            setNewSkill('');
        }
    };

    const removeSkill = (skill: string) => {
        setProfile({ ...profile, current_skills: profile.current_skills.filter(s => s !== skill) });
    };

    const addSkillToLearn = () => {
        if (newSkillToLearn && !profile.skills_to_learn.includes(newSkillToLearn)) {
            setProfile({ ...profile, skills_to_learn: [...profile.skills_to_learn, newSkillToLearn] });
            setNewSkillToLearn('');
        }
    };

    const removeSkillToLearn = (skill: string) => {
        setProfile({ ...profile, skills_to_learn: profile.skills_to_learn.filter(s => s !== skill) });
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
                    <h1 className="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>

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
                    <div className="space-y-6 mb-8">
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
                                    <MapPin className="w-4 h-4 inline mr-2" />
                                    Location
                                </label>
                                <input
                                    type="text"
                                    value={profile.location}
                                    onChange={(e) => setProfile({ ...profile, location: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    placeholder="City, Country"
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
                                placeholder="Tell us about yourself..."
                            />
                        </div>
                    </div>

                    {/* Education */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Education</h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Education Level</label>
                                <select
                                    value={profile.education_level}
                                    onChange={(e) => setProfile({ ...profile, education_level: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                >
                                    <option value="">Select level</option>
                                    <option value="High School">High School</option>
                                    <option value="Bachelor's">Bachelor's Degree</option>
                                    <option value="Master's">Master's Degree</option>
                                    <option value="PhD">PhD</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Field of Study</label>
                                <input
                                    type="text"
                                    value={profile.field_of_study}
                                    onChange={(e) => setProfile({ ...profile, field_of_study: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    placeholder="e.g., Computer Science"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Skills */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Current Skills</h2>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={newSkill}
                                onChange={(e) => setNewSkill(e.target.value)}
                                onKeyPress={(e) => e.key === 'Enter' && addSkill()}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Add a skill (e.g., React, Python)"
                            />
                            <button
                                onClick={addSkill}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                            >
                                Add
                            </button>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {profile.current_skills.map((skill, index) => (
                                <span
                                    key={index}
                                    className="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm flex items-center gap-2"
                                >
                                    {skill}
                                    <button
                                        onClick={() => removeSkill(skill)}
                                        className="text-indigo-500 hover:text-indigo-700"
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Skills to Learn */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Skills to Learn</h2>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={newSkillToLearn}
                                onChange={(e) => setNewSkillToLearn(e.target.value)}
                                onKeyPress={(e) => e.key === 'Enter' && addSkillToLearn()}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Add a skill you want to learn"
                            />
                            <button
                                onClick={addSkillToLearn}
                                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                            >
                                Add
                            </button>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {profile.skills_to_learn.map((skill, index) => (
                                <span
                                    key={index}
                                    className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm flex items-center gap-2"
                                >
                                    {skill}
                                    <button
                                        onClick={() => removeSkillToLearn(skill)}
                                        className="text-green-500 hover:text-green-700"
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Career Goals */}
                    <div className="space-y-6 mb-8">
                        <h2 className="text-xl font-semibold text-gray-900">Career Goals</h2>
                        <textarea
                            value={profile.career_goals}
                            onChange={(e) => setProfile({ ...profile, career_goals: e.target.value })}
                            rows={4}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                            placeholder="What are your career goals?"
                        />
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
