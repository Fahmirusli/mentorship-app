'use client';

import { useState, useEffect } from 'react';
import { User, Mail, Phone, MapPin, Briefcase, Save, Loader, Sparkles } from 'lucide-react';
import { api } from '@/lib/api';
import { GamificationCard } from '@/components/GamificationCard';

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
        skills: [] as string[],
        current_skills: [] as string[],
        skills_to_learn: [] as string[],
        career_goals: '',
        education_level: '',
        field_of_study: ''
    });
    const [newSkill, setNewSkill] = useState('');
    const [newCurrentSkill, setNewCurrentSkill] = useState('');
    const [newSkillToLearn, setNewSkillToLearn] = useState('');
    const [uploadingResume, setUploadingResume] = useState(false);
    const [parsingResume, setParsingResume] = useState(false);
    const [resumeUrl, setResumeUrl] = useState<string | null>(null);
    const [gamification, setGamification] = useState<any>(null);

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        try {
            const response = await api.get('/user');
            const menteeProfile = response.mentee_profile || response.menteeProfile || {};
            const sharedSkills = Array.isArray(response.skills) ? response.skills : [];

            setProfile({
                name: response.name || '',
                email: response.email || '',
                phone: response.phone || '',
                bio: response.bio || '',
                location: response.address || '',
                skills: sharedSkills,
                current_skills: menteeProfile.current_skills || [],
                skills_to_learn: menteeProfile.skills_to_learn || [],
                career_goals: menteeProfile.career_goals || '',
                education_level: menteeProfile.education_level || '',
                field_of_study: menteeProfile.field_of_study || ''
            });
            setImage(response.profile_image || null);
            if (response.resume_path) {
                const resumeValue = String(response.resume_path);
                const appBase = (process.env.NEXT_PUBLIC_API_BASE_URL || '').replace('/api', '');
                setResumeUrl(resumeValue.startsWith('data:') ? resumeValue : `${appBase}/storage/${resumeValue}`);
            } else {
                setResumeUrl(null);
            }

            try {
                const gamificationRes = await api.get('/gamification');
                if (gamificationRes) {
                    setGamification(gamificationRes);
                }
            } catch (err) {
                console.error('Gamification fetch error', err);
            }
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
                address: profile.location,
                bio: profile.bio,
                skills: profile.skills
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

    const handleParseResume = async () => {
        const fileInput = document.getElementById('resume-upload') as HTMLInputElement;
        const file = fileInput?.files?.[0];
        
        if (!file) {
            alert('Please upload a resume first to parse it.');
            return;
        }

        setParsingResume(true);
        const formData = new FormData();
        formData.append('resume', file);

        try {
            const res = await api.post('/user/parse-resume', formData);
            if (res.skills && Array.isArray(res.skills)) {
                // Merge new skills, avoiding duplicates
                const newSkills = res.skills.filter((s: string) => !profile.current_skills.includes(s));
                if (newSkills.length > 0) {
                    setProfile({ ...profile, current_skills: [...profile.current_skills, ...newSkills] });
                    alert(`AI successfully extracted ${newSkills.length} new skills! Please review and click Save Profile.`);
                } else {
                    alert('AI analyzed your resume but found no new skills that you do not already have.');
                }
            }
        } catch (err: any) {
            console.error('Error parsing resume:', err);
            alert(err.response?.data?.message || 'Failed to parse resume with AI.');
        } finally {
            setParsingResume(false);
        }
    };

    const addSkill = () => {
        if (newCurrentSkill && !profile.current_skills.includes(newCurrentSkill)) {
            setProfile({ ...profile, current_skills: [...profile.current_skills, newCurrentSkill] });
            setNewCurrentSkill('');
        }
    };

    const removeSkill = (skill: string) => {
        setProfile({ ...profile, current_skills: profile.current_skills.filter(s => s !== skill) });
    };

    const addSharedSkill = () => {
        if (newSkill && !profile.skills.includes(newSkill)) {
            setProfile({ ...profile, skills: [...profile.skills, newSkill] });
            setNewSkill('');
        }
    };

    const removeSharedSkill = (skill: string) => {
        setProfile({ ...profile, skills: profile.skills.filter(s => s !== skill) });
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
                {gamification && <GamificationCard data={gamification} />}
                
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
                                            if (typeof window !== 'undefined') {
                                                window.dispatchEvent(new CustomEvent('profile-image-updated', {
                                                    detail: { imageUrl: res.image_url }
                                                }));
                                            }
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
                        <h2 className="text-xl font-semibold text-gray-900">Skills</h2>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={newSkill}
                                onChange={(e) => setNewSkill(e.target.value)}
                                onKeyPress={(e) => e.key === 'Enter' && addSharedSkill()}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Add a skill (e.g., React, Python)"
                            />
                            <button
                                onClick={addSharedSkill}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                            >
                                Add
                            </button>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {profile.skills.map((skill, index) => (
                                <span
                                    key={index}
                                    className="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm flex items-center gap-2"
                                >
                                    {skill}
                                    <button
                                        onClick={() => removeSharedSkill(skill)}
                                        className="text-indigo-500 hover:text-indigo-700"
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-4 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Current Skills Detail</h2>
                        <p className="text-sm text-gray-500">Use this section to add technical skills you already practice for matching.</p>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={newCurrentSkill}
                                onChange={(e) => setNewCurrentSkill(e.target.value)}
                                onKeyPress={(e) => e.key === 'Enter' && addSkill()}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Add current technical skill"
                            />
                            <button
                                onClick={addSkill}
                                className="px-6 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700"
                            >
                                Add
                            </button>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {profile.current_skills.map((skill, index) => (
                                <span key={index} className="px-3 py-1 bg-violet-100 text-violet-700 rounded-full text-sm flex items-center gap-2">
                                    {skill}
                                    <button onClick={() => removeSkill(skill)} className="text-violet-500 hover:text-violet-700">×</button>
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

                    <div className="space-y-4 mb-8 pb-8 border-b">
                        <h2 className="text-xl font-semibold text-gray-900">Resume</h2>
                        <div className="flex flex-col gap-4">
                            <div className="flex items-center gap-4">
                                <label className="px-4 py-2 bg-gray-900 text-white rounded-lg cursor-pointer hover:bg-black">
                                    {uploadingResume ? 'Uploading...' : 'Upload Resume'}
                                    <input
                                        id="resume-upload"
                                        type="file"
                                        className="hidden"
                                        accept=".pdf"
                                        onChange={async (e) => {
                                            const file = e.target.files?.[0];
                                            if (!file) return;
                                            setUploadingResume(true);
                                            const formData = new FormData();
                                            formData.append('resume', file);
                                            try {
                                                const res = await api.post('/upload/resume', formData);
                                                setResumeUrl(res.resume_url || null);
                                            } catch (err) {
                                                console.error(err);
                                                alert('Failed to upload resume');
                                            } finally {
                                                setUploadingResume(false);
                                            }
                                        }}
                                    />
                                </label>
                                {resumeUrl && (
                                    <a href={resumeUrl} target="_blank" rel="noreferrer" className="text-indigo-600 hover:text-indigo-800 underline">
                                        View current resume
                                    </a>
                                )}
                            </div>
                            
                            <div className="bg-purple-50 rounded-xl p-4 border border-purple-100 mt-2">
                                <h3 className="font-semibold text-purple-900 mb-1 flex items-center gap-2">
                                    <Sparkles className="w-4 h-4 text-purple-600" />
                                    AI Resume Parsing
                                </h3>
                                <p className="text-sm text-purple-700 mb-3">
                                    Upload a PDF resume and let our AI automatically extract your skills.
                                </p>
                                <button
                                    onClick={handleParseResume}
                                    disabled={parsingResume}
                                    className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center justify-center gap-2 text-sm transition"
                                >
                                    {parsingResume ? (
                                        <>
                                            <Loader className="w-4 h-4 animate-spin" />
                                            Analyzing...
                                        </>
                                    ) : (
                                        <>
                                            <Sparkles className="w-4 h-4" />
                                            Extract Skills with AI
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
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
