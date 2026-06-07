'use client';

import { useEffect, useState } from 'react';
import { Upload, Download, FileText, Video, Link as LinkIcon, Trash2, X } from 'lucide-react';
import { api } from '@/lib/api';

type ResourceItem = {
    id: number;
    title: string;
    description?: string | null;
    url: string;
    type: 'link' | 'file' | 'video';
    downloads_count?: number;
    created_at?: string;
};

export default function MentorResources() {
    const [resources, setResources] = useState<ResourceItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const [showModal, setShowModal] = useState(false);
    const [form, setForm] = useState({
        title: '',
        description: '',
        url: '',
        type: 'link' as ResourceItem['type'],
    });
    const [file, setFile] = useState<File | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        fetchResources();
    }, []);

    const fetchResources = async () => {
        setLoading(true);
        setError('');
        try {
            const data = await api.get<ResourceItem[]>('/resources');
            setResources(data || []);
        } catch (err: any) {
            setError(err.message || 'Failed to load resources');
        } finally {
            setLoading(false);
        }
    };

    const handleCreate = async () => {
        if (!form.title.trim()) {
            setError('Title is required');
            return;
        }

        if (form.type === 'file') {
            if (!file) {
                setError('Please choose a file');
                return;
            }
        } else if (!form.url.trim()) {
            setError('URL is required');
            return;
        }

        setSaving(true);
        setError('');
        try {
            let created: ResourceItem;

            if (form.type === 'file') {
                const formData = new FormData();
                formData.append('title', form.title);
                if (form.description) {
                    formData.append('description', form.description);
                }
                formData.append('type', 'file');
                formData.append('file', file as File);
                created = await api.post<ResourceItem>('/resources', formData);
            } else {
                created = await api.post<ResourceItem>('/resources', form);
            }

            setResources((prev) => [created, ...prev]);
            setForm({ title: '', description: '', url: '', type: 'link' });
            setFile(null);
            setShowModal(false);
        } catch (err: any) {
            setError(err.message || 'Failed to create resource');
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm('Delete this resource?')) return;
        try {
            await api.delete(`/resources/${id}`);
            setResources((prev) => prev.filter((item) => item.id !== id));
        } catch (err: any) {
            setError(err.message || 'Failed to delete resource');
        }
    };

    const formatDate = (value?: string) => {
        if (!value) return 'Unknown date';
        try {
            return new Date(value).toISOString().slice(0, 10);
        } catch {
            return 'Unknown date';
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-6xl mx-auto">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Resources</h1>
                        <p className="text-gray-600 mt-2">Share materials with your mentees</p>
                    </div>
                    <button
                        onClick={() => setShowModal(true)}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium flex items-center"
                    >
                        <Upload className="w-4 h-4 mr-2" />
                        Upload Resource
                    </button>
                </div>

                {error && (
                    <div className="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        {error}
                    </div>
                )}

                {loading ? (
                    <div className="text-gray-600">Loading resources...</div>
                ) : (
                    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {resources.map((resource) => (
                            <div key={resource.id} className="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="p-3 bg-indigo-50 rounded-lg">
                                        {resource.type === 'file' && <FileText className="w-6 h-6 text-indigo-600" />}
                                        {resource.type === 'video' && <Video className="w-6 h-6 text-indigo-600" />}
                                        {resource.type === 'link' && <LinkIcon className="w-6 h-6 text-indigo-600" />}
                                    </div>
                                    <button
                                        onClick={() => handleDelete(resource.id)}
                                        className="text-gray-400 hover:text-red-500"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>

                                <h3 className="font-bold text-gray-900 mb-1">{resource.title}</h3>
                                <p className="text-sm text-gray-500 mb-4">Added on {formatDate(resource.created_at)}</p>

                                <div className="flex items-center justify-between text-sm text-gray-600 border-t pt-4">
                                    <span className="flex items-center">
                                        <Download className="w-4 h-4 mr-1" />
                                        {resource.downloads_count ?? 0} downloads
                                    </span>
                                    <span className="uppercase text-xs font-semibold bg-gray-100 px-2 py-1 rounded">
                                        {resource.type}
                                    </span>
                                </div>
                            </div>
                        ))}
                        {resources.length === 0 && (
                            <div className="text-gray-600">No resources yet.</div>
                        )}
                    </div>
                )}
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-black/40 flex items-center justify-center p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-bold text-gray-900">Upload Resource</h2>
                            <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input
                                    type="text"
                                    value={form.title}
                                    onChange={(e) => setForm({ ...form, title: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea
                                    value={form.description}
                                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    rows={3}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select
                                    value={form.type}
                                    onChange={(e) => setForm({ ...form, type: e.target.value as ResourceItem['type'] })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                >
                                    <option value="link">Link</option>
                                    <option value="file">File</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>

                            {form.type === 'file' ? (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">File</label>
                                    <input
                                        type="file"
                                        onChange={(e) => setFile(e.target.files?.[0] || null)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Allowed: pdf, doc, docx, ppt, pptx, xls, xlsx, csv, txt, zip, jpg, png, mp4. Max 200MB.
                                    </p>
                                </div>
                            ) : (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">URL</label>
                                    <input
                                        type="text"
                                        value={form.url}
                                        onChange={(e) => setForm({ ...form, url: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            )}
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                onClick={() => setShowModal(false)}
                                className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleCreate}
                                disabled={saving}
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {saving ? 'Saving...' : 'Save Resource'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
