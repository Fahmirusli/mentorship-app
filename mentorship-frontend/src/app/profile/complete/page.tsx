'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Loader, Upload } from 'lucide-react';
import { api } from '@/lib/api';

type Role = 'mentor' | 'mentee' | 'admin';

export default function CompleteProfilePage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploadingResume, setUploadingResume] = useState(false);
  const [newSkill, setNewSkill] = useState('');
  const [role, setRole] = useState<Role>('mentee');
  const [resumeUrl, setResumeUrl] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
    state: '',
    postcode: '',
    bio: '',
    skills: [] as string[],
  });

  useEffect(() => {
    const stored = typeof window !== 'undefined' ? localStorage.getItem('user') : null;
    if (stored) {
      try {
        const parsed = JSON.parse(stored);
        if (parsed.role) {
          setRole(parsed.role);
        }
      } catch {
        // ignore malformed local storage user
      }
    }

    fetchUser();
  }, []);

  const fetchUser = async () => {
    try {
      const user = await api.get('/user');
      setForm({
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
        address: user.address || '',
        state: user.state || '',
        postcode: user.postcode || '',
        bio: user.bio || '',
        skills: Array.isArray(user.skills) ? user.skills : [],
      });
      if (user.resume_path) {
        const resumeValue = String(user.resume_path);
        const appBase = (process.env.NEXT_PUBLIC_API_BASE_URL || '').replace('/api', '');
        setResumeUrl(resumeValue.startsWith('data:') ? resumeValue : `${appBase}/storage/${resumeValue}`);
      }
      if (user.profile_complete) {
        goDashboard(user.role || role);
      }
    } catch (error) {
      console.error('Failed to load user profile', error);
    } finally {
      setLoading(false);
    }
  };

  const goDashboard = (userRole: Role) => {
    if (userRole === 'mentor') {
      router.push('/mentor/dashboard');
    } else if (userRole === 'admin') {
      router.push('/admin/dashboard');
    } else {
      router.push('/mentee/dashboard');
    }
  };

  const addSkill = () => {
    const normalized = newSkill.trim();
    if (!normalized || form.skills.includes(normalized)) {
      return;
    }

    setForm((prev) => ({ ...prev, skills: [...prev.skills, normalized] }));
    setNewSkill('');
  };

  const removeSkill = (skill: string) => {
    setForm((prev) => ({ ...prev, skills: prev.skills.filter((s) => s !== skill) }));
  };

  const saveProfile = async () => {
    if (!form.name || !form.email || !form.phone || !form.address || !form.state || !form.postcode || !form.bio || form.skills.length === 0) {
      alert('Please fill all required fields and add at least one skill.');
      return;
    }

    setSaving(true);
    try {
      const response = await api.post('/profile/complete', {
        ...form,
        current_skills: role === 'mentee' ? form.skills : undefined,
      });

      const existing = typeof window !== 'undefined' ? localStorage.getItem('user') : null;
      if (existing) {
        try {
          const parsed = JSON.parse(existing);
          localStorage.setItem('user', JSON.stringify({ ...parsed, ...response.user }));
        } catch {
          // ignore malformed local storage user
        }
      }

      goDashboard((response.user?.role as Role) || role);
    } catch (error: any) {
      console.error(error);
      alert(error?.message || 'Failed to complete profile');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader className="w-8 h-8 animate-spin text-indigo-600" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4">
      <div className="max-w-2xl mx-auto bg-white rounded-xl shadow-sm p-8 space-y-6">
        <h1 className="text-2xl font-bold text-gray-900">Complete Your Profile</h1>
        <p className="text-sm text-gray-600">Please complete these details before continuing.</p>

        <input
          type="text"
          value={form.name}
          onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg"
          placeholder="Full name"
        />

        <input
          type="email"
          value={form.email}
          onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg"
          placeholder="Email address"
        />

        <input
          type="text"
          value={form.phone}
          onChange={(e) => setForm((prev) => ({ ...prev, phone: e.target.value }))}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg"
          placeholder="Phone number"
        />

        <input
          type="text"
          value={form.address}
          onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg"
          placeholder="Street Address"
        />

        <div className="flex gap-4">
          <input
            type="text"
            value={form.postcode}
            onChange={(e) => setForm((prev) => ({ ...prev, postcode: e.target.value }))}
            className="w-1/3 px-4 py-2 border border-gray-300 rounded-lg"
            placeholder="Postcode"
          />
          <input
            type="text"
            value={form.state}
            onChange={(e) => setForm((prev) => ({ ...prev, state: e.target.value }))}
            className="w-2/3 px-4 py-2 border border-gray-300 rounded-lg"
            placeholder="State"
          />
        </div>

        <textarea
          value={form.bio}
          onChange={(e) => setForm((prev) => ({ ...prev, bio: e.target.value }))}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg"
          rows={4}
          placeholder="Short bio"
        />

        <div className="space-y-2">
          <label className="text-sm font-medium text-gray-700">Skills</label>
          <div className="flex gap-2">
            <input
              type="text"
              value={newSkill}
              onChange={(e) => setNewSkill(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && addSkill()}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg"
              placeholder="Add a skill"
            />
            <button onClick={addSkill} className="px-4 py-2 bg-indigo-600 text-white rounded-lg">Add</button>
          </div>
          
          {/* Suggested Skills */}
          <div className="mt-2">
            <p className="text-xs text-gray-500 mb-2">Suggested top skills:</p>
            <div className="flex flex-wrap gap-2">
              {['JavaScript', 'TypeScript', 'React.js', 'Node.js', 'Python', 'PHP', 'Laravel', 'Java', 'SQL', 'AWS'].map((skill) => (
                <button
                  key={skill}
                  onClick={() => {
                    if (!form.skills.includes(skill)) {
                      setForm((prev) => ({ ...prev, skills: [...prev.skills, skill] }));
                    }
                  }}
                  className={`px-3 py-1 text-xs border rounded-full transition-colors ${
                    form.skills.includes(skill)
                      ? 'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed'
                      : 'bg-white border-indigo-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300'
                  }`}
                  disabled={form.skills.includes(skill)}
                >
                  + {skill}
                </button>
              ))}
            </div>
          </div>

          <div className="flex flex-wrap gap-2 mt-4">
            {form.skills.map((skill) => (
              <span key={skill} className="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm flex items-center">
                {skill}
                <button className="ml-2 text-indigo-400 hover:text-indigo-600 focus:outline-none" onClick={() => removeSkill(skill)}>x</button>
              </span>
            ))}
          </div>
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium text-gray-700">Resume (Optional)</label>
          <label className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
            <Upload className="w-4 h-4" />
            {uploadingResume ? 'Uploading...' : 'Upload Resume'}
            <input
              type="file"
              className="hidden"
              accept=".pdf,.doc,.docx"
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
                  alert('Resume upload failed');
                } finally {
                  setUploadingResume(false);
                }
              }}
            />
          </label>
          {resumeUrl && (
            <a href={resumeUrl} target="_blank" rel="noreferrer" className="block text-indigo-600 underline">
              View uploaded resume
            </a>
          )}
        </div>

        <button
          onClick={saveProfile}
          disabled={saving}
          className="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
        >
          {saving ? 'Saving...' : 'Save and Continue'}
        </button>
      </div>
    </div>
  );
}
