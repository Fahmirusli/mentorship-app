'use client';

import { useState, useEffect } from 'react';
import { BookOpen, Plus, Trash2, Loader, Save } from 'lucide-react';
import { api } from '@/lib/api';

interface SyllabusItem {
    title: string;
    link?: string;
}

interface Course {
    id?: number;
    title: string;
    description: string;
    price: number;
    tags: string[];
    syllabus: SyllabusItem[];
}

export default function MentorCourses() {
    const [loading, setLoading] = useState(true);
    const [courses, setCourses] = useState<Course[]>([]);
    const [isCreating, setIsCreating] = useState(false);
    
    // New Course State
    const [newCourse, setNewCourse] = useState<Course>({
        title: '',
        description: '',
        price: 0,
        tags: [],
        syllabus: [],
    });
    const [tagInput, setTagInput] = useState('');
    const [syllabusTitle, setSyllabusTitle] = useState('');
    const [syllabusLink, setSyllabusLink] = useState('');

    useEffect(() => {
        fetchCourses();
    }, []);

    const fetchCourses = async () => {
        try {
            const response = await api.get('/courses');
            setCourses(response.courses || []);
        } catch (error) {
            console.error('Error fetching courses:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddTag = () => {
        if (tagInput.trim() && !newCourse.tags.includes(tagInput.trim())) {
            setNewCourse({ ...newCourse, tags: [...newCourse.tags, tagInput.trim()] });
            setTagInput('');
        }
    };

    const handleAddSyllabus = () => {
        if (syllabusTitle.trim()) {
            setNewCourse({ 
                ...newCourse, 
                syllabus: [...newCourse.syllabus, { title: syllabusTitle.trim(), link: syllabusLink.trim() || undefined }] 
            });
            setSyllabusTitle('');
            setSyllabusLink('');
        }
    };

    const handleRemoveTag = (tag: string) => {
        setNewCourse({ ...newCourse, tags: newCourse.tags.filter(t => t !== tag) });
    };

    const handleRemoveSyllabus = (index: number) => {
        setNewCourse({ ...newCourse, syllabus: newCourse.syllabus.filter((_, i) => i !== index) });
    };

    const handleSaveCourse = async () => {
        if (!newCourse.title || !newCourse.description) {
            alert('Title and description are required.');
            return;
        }

        try {
            const response = await api.post('/courses', newCourse);
            setCourses([...courses, response.course]);
            setIsCreating(false);
            setNewCourse({ title: '', description: '', price: 0, tags: [], syllabus: [] });
            alert('Course created successfully!');
        } catch (error: any) {
            console.error('Error creating course:', error);
            alert(error?.message || 'Failed to create course');
        }
    };

    const handleDeleteCourse = async (id: number) => {
        if (!confirm('Are you sure you want to delete this course? Enrolled mentees will lose access.')) return;
        try {
            await api.delete(`/courses/${id}`);
            setCourses(courses.filter(c => c.id !== id));
        } catch (error: any) {
            console.error('Error deleting course:', error);
            alert(error?.message || 'Failed to delete course');
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
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-5xl mx-auto">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">My Mentorship Courses</h1>
                        <p className="text-gray-600 mt-2">Create full-path learning experiences for your mentees.</p>
                    </div>
                    {!isCreating && (
                        <button
                            onClick={() => setIsCreating(true)}
                            className="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2 transition"
                        >
                            <Plus className="w-5 h-5" />
                            Create New Course
                        </button>
                    )}
                </div>

                {isCreating && (
                    <div className="bg-white rounded-xl shadow-sm p-8 mb-8 border border-indigo-100">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-xl font-bold text-gray-900">Create a New Course</h2>
                            <button onClick={() => setIsCreating(false)} className="text-gray-500 hover:text-gray-700">Cancel</button>
                        </div>

                        <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Course Title</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Fullstack React Mastery"
                                        value={newCourse.title}
                                        onChange={(e) => setNewCourse({ ...newCourse, title: e.target.value })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Total Package Price (RM)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={newCourse.price}
                                        onChange={(e) => setNewCourse({ ...newCourse, price: Number(e.target.value) })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea
                                    rows={3}
                                    placeholder="Describe what the mentee will achieve by the end of this course..."
                                    value={newCourse.description}
                                    onChange={(e) => setNewCourse({ ...newCourse, description: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
                                ></textarea>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Related Skills (Tags)</label>
                                <div className="flex gap-2 mb-3 flex-wrap">
                                    {newCourse.tags.map((tag, idx) => (
                                        <span key={idx} className="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-sm flex items-center gap-1 border border-indigo-100">
                                            {tag}
                                            <button onClick={() => handleRemoveTag(tag)} className="hover:text-indigo-900 ml-1">&times;</button>
                                        </span>
                                    ))}
                                </div>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        placeholder="e.g. React, Node.js"
                                        value={tagInput}
                                        onChange={(e) => setTagInput(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleAddTag()}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                    <button onClick={handleAddTag} className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Add Tag</button>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Syllabus / Learning Path</label>
                                <p className="text-sm text-gray-500 mb-3">Add the topics or tasks the mentee will complete. This will generate their Learning Progress bar.</p>
                                
                                <div className="space-y-2 mb-4">
                                    {newCourse.syllabus.map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                            <div className="flex flex-col gap-1">
                                                <div className="flex items-center gap-3">
                                                    <span className="w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">{idx + 1}</span>
                                                    <span className="text-gray-800 font-medium">{item.title}</span>
                                                </div>
                                                {item.link && (
                                                    <a href={item.link} target="_blank" rel="noopener noreferrer" className="text-sm text-blue-500 hover:underline ml-9">
                                                        {item.link}
                                                    </a>
                                                )}
                                            </div>
                                            <button onClick={() => handleRemoveSyllabus(idx)} className="text-red-500 hover:text-red-700 p-1">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex flex-col md:flex-row gap-2">
                                    <input
                                        type="text"
                                        placeholder="Topic (e.g. Build a portfolio)"
                                        value={syllabusTitle}
                                        onChange={(e) => setSyllabusTitle(e.target.value)}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                    <input
                                        type="text"
                                        placeholder="Resource Link (Optional)"
                                        value={syllabusLink}
                                        onChange={(e) => setSyllabusLink(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleAddSyllabus()}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                    <button onClick={handleAddSyllabus} className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Add Task</button>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-gray-100 flex justify-end">
                                <button
                                    onClick={handleSaveCourse}
                                    className="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2 transition"
                                >
                                    <Save className="w-5 h-5" />
                                    Publish Course
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {courses.map((course) => (
                        <div key={course.id} className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <div className="p-6">
                                <div className="flex justify-between items-start mb-4">
                                    <h3 className="font-bold text-xl text-gray-900 line-clamp-2">{course.title}</h3>
                                    <span className="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold whitespace-nowrap ml-2">
                                        RM {course.price}
                                    </span>
                                </div>
                                <p className="text-gray-600 text-sm mb-4 line-clamp-3">{course.description}</p>
                                
                                <div className="flex flex-wrap gap-1 mb-6">
                                    {course.tags?.slice(0, 3).map((tag, i) => (
                                        <span key={i} className="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded">
                                            {tag}
                                        </span>
                                    ))}
                                    {(course.tags?.length || 0) > 3 && (
                                        <span className="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded">+{course.tags.length - 3}</span>
                                    )}
                                </div>

                                <div className="flex items-center justify-between border-t border-gray-100 pt-4">
                                    <div className="text-sm text-gray-500 flex items-center gap-1">
                                        <BookOpen className="w-4 h-4" />
                                        {course.syllabus?.length || 0} Topics
                                    </div>
                                    <button 
                                        onClick={() => course.id && handleDeleteCourse(course.id)}
                                        className="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}

                    {!isCreating && courses.length === 0 && (
                        <div className="col-span-full py-16 bg-white rounded-xl border border-dashed border-gray-300 flex flex-col items-center justify-center text-center">
                            <BookOpen className="w-12 h-12 text-gray-300 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900">No courses created</h3>
                            <p className="text-gray-500 max-w-sm mt-1">Create your first course to offer structured learning paths to your mentees.</p>
                            <button
                                onClick={() => setIsCreating(true)}
                                className="mt-6 px-6 py-2 bg-indigo-50 text-indigo-600 font-medium rounded-lg hover:bg-indigo-100 transition"
                            >
                                Get Started
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
