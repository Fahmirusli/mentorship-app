'use client';

import { useEffect, useState } from 'react';
import { Download, FileText, Video, Link as LinkIcon, BookOpen } from 'lucide-react';
import { api } from '@/lib/api';

type ResourceItem = {
    id: number;
    title: string;
    description?: string | null;
    url: string;
    type: 'link' | 'file' | 'video';
    downloads_count?: number;
    created_at?: string;
    mentor?: { id: number, name: string };
};

export default function MenteeResources() {
    const [resources, setResources] = useState<ResourceItem[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchResources();
    }, []);

    const fetchResources = async () => {
        try {
            const data = await api.get('/mentee/resources');
            if (Array.isArray(data)) {
                setResources(data);
            } else if (data && Array.isArray(data.data)) {
                setResources(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch resources:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="relative w-16 h-16 mx-auto mb-4">
                    <div className="absolute inset-0 rounded-full border-4 border-indigo-100"></div>
                    <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-indigo-600 animate-spin"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 p-8 page-enter">
            <div className="max-w-5xl mx-auto">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                        <BookOpen className="w-8 h-8 text-indigo-600" />
                        Mentorship Resources
                    </h1>
                    <p className="text-gray-600 mt-2">Access learning materials shared by your active mentors.</p>
                </div>

                {resources.length === 0 ? (
                    <div className="glass-panel text-center py-16 rounded-2xl animate-fade-in-up">
                        <div className="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <FileText className="w-10 h-10 text-indigo-400" />
                        </div>
                        <h3 className="text-xl font-bold text-gray-900">No resources available</h3>
                        <p className="text-gray-500 mt-2">Your mentors haven't shared any materials with you yet.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 stagger-children">
                        {resources.map((resource) => (
                            <div key={resource.id} className="glass-panel p-6 rounded-2xl flex flex-col">
                                <div className="flex justify-between items-start mb-4">
                                    <div className={`p-3 rounded-xl ${
                                        resource.type === 'video' ? 'bg-rose-100 text-rose-600' :
                                        resource.type === 'link' ? 'bg-blue-100 text-blue-600' :
                                        'bg-indigo-100 text-indigo-600'
                                    }`}>
                                        {resource.type === 'video' ? <Video className="w-6 h-6" /> :
                                         resource.type === 'link' ? <LinkIcon className="w-6 h-6" /> :
                                         <FileText className="w-6 h-6" />}
                                    </div>
                                    <span className="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg uppercase tracking-wide">
                                        {resource.type}
                                    </span>
                                </div>
                                
                                <h3 className="font-bold text-gray-900 text-lg mb-1 line-clamp-2">{resource.title}</h3>
                                {resource.mentor && (
                                    <p className="text-xs text-indigo-600 font-medium mb-3">By {resource.mentor.name}</p>
                                )}
                                {resource.description && (
                                    <p className="text-sm text-gray-500 mb-4 line-clamp-3 flex-1">{resource.description}</p>
                                )}
                                
                                <div className="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                    <p className="text-xs text-gray-400 font-medium">
                                        {new Date(resource.created_at || '').toLocaleDateString()}
                                    </p>
                                    <a
                                        href={resource.url.startsWith('http') ? resource.url : `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || 'https://api.uplifts.dev'}${resource.url.startsWith('/') ? '' : '/'}${resource.url}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-indigo-600 transition-colors"
                                    >
                                        {resource.type === 'link' ? 'Open Link' : 'Download'}
                                    </a>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
