'use client';

import { useState } from 'react';
import { Upload, CheckCircle, AlertCircle } from 'lucide-react';
import { api } from '@/lib/api';

export default function ProfileUpload() {
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setLoading(true);
        setError('');
        setMessage('');

        try {
            const formData = new FormData();
            formData.append('image', file);

            await api.post('/upload/profile-image', formData);
            setMessage('Profile image uploaded successfully!');
        } catch (err: any) {
            setError(err.message || 'Upload error');
        } finally {
            setLoading(false);
        }
    };

    const handleResumeUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setLoading(true);
        setError('');
        setMessage('');

        try {
            const formData = new FormData();
            formData.append('resume', file);

            await api.post('/upload/resume', formData);
            setMessage('Resume uploaded successfully!');
        } catch (err: any) {
            setError(err.message || 'Upload error');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-6">
            {/* Profile Image Upload */}
            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <label className="flex flex-col items-center justify-center cursor-pointer">
                    <Upload className="w-12 h-12 text-gray-400 mb-2" />
                    <span className="text-gray-600 font-medium">Upload Profile Picture</span>
                    <span className="text-sm text-gray-500">JPG, PNG (Max 2MB)</span>
                    <input 
                        type="file" 
                        accept="image/*" 
                        onChange={handleImageUpload}
                        disabled={loading}
                        className="hidden"
                    />
                </label>
            </div>

            {/* Resume Upload */}
            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <label className="flex flex-col items-center justify-center cursor-pointer">
                    <Upload className="w-12 h-12 text-gray-400 mb-2" />
                    <span className="text-gray-600 font-medium">Upload Resume</span>
                    <span className="text-sm text-gray-500">PDF, DOC, DOCX (Max 5MB)</span>
                    <input 
                        type="file" 
                        accept=".pdf,.doc,.docx"
                        onChange={handleResumeUpload}
                        disabled={loading}
                        className="hidden"
                    />
                </label>
            </div>

            {/* Messages */}
            {message && (
                <div className="flex items-center gap-2 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                    <CheckCircle className="w-5 h-5" />
                    {message}
                </div>
            )}

            {error && (
                <div className="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                    <AlertCircle className="w-5 h-5" />
                    {error}
                </div>
            )}
        </div>
    );
}
