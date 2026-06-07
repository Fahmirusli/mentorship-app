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
    phone: '',
    address: '',
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
        phone: user.phone || '',
        address: user.address || '',
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
    if (!form.name || !form.phone || !form.address || !form.bio || form.skills.length === 0) {
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
          placeholder="Address"
        />

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
          <div className="flex flex-wrap gap-2">
            {form.skills.map((skill) => (
              <span key={skill} className="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm">
                {skill}
                <button className="ml-2" onClick={() => removeSkill(skill)}>x</button>
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
