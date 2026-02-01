'use client';

import { useState } from 'react';
import { BookOpen, Upload, Download, FileText, Video, Link as LinkIcon, Trash2 } from 'lucide-react';

export default function MentorResources() {
    const [resources, setResources] = useState([
        { id: 1, title: 'React Best Practices', type: 'pdf', downloads: 125, date: '2023-10-15' },
        { id: 2, title: 'Mock Interview Guide', type: 'video', downloads: 89, date: '2023-10-20' },
        { id: 3, title: 'System Design Templates', type: 'link', downloads: 256, date: '2023-11-01' },
    ]);

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-6xl mx-auto">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Resources</h1>
                        <p className="text-gray-600 mt-2">Share materials with your mentees</p>
                    </div>
                    <button className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium flex items-center">
                        <Upload className="w-4 h-4 mr-2" />
                        Upload Resource
                    </button>
                </div>

                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {resources.map((resource) => (
                        <div key={resource.id} className="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                            <div className="flex items-start justify-between mb-4">
                                <div className="p-3 bg-indigo-50 rounded-lg">
                                    {resource.type === 'pdf' && <FileText className="w-6 h-6 text-indigo-600" />}
                                    {resource.type === 'video' && <Video className="w-6 h-6 text-indigo-600" />}
                                    {resource.type === 'link' && <LinkIcon className="w-6 h-6 text-indigo-600" />}
                                </div>
                                <button className="text-gray-400 hover:text-red-500">
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>

                            <h3 className="font-bold text-gray-900 mb-1">{resource.title}</h3>
                            <p className="text-sm text-gray-500 mb-4">Added on {resource.date}</p>

                            <div className="flex items-center justify-between text-sm text-gray-600 border-t pt-4">
                                <span className="flex items-center">
                                    <Download className="w-4 h-4 mr-1" />
                                    {resource.downloads} downloads
                                </span>
                                <span className="uppercase text-xs font-semibold bg-gray-100 px-2 py-1 rounded">
                                    {resource.type}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
