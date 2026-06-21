'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { BookOpen, User, CheckCircle, Play, Loader } from 'lucide-react';
import { api } from '@/lib/api';

interface SyllabusItem {
    title: string;
    link?: string;
}

interface Course {
    id: number;
    title: string;
    description: string;
    price: number;
    tags: string[];
    syllabus: SyllabusItem[];
    mentor?: {
        id: number;
        name: string;
        avatar?: string;
    };
}

interface Enrollment {
    id: number;
    course: Course;
    progress_percent: number;
    status: string;
    completed_tasks: number[];
}

export default function MenteeCourses() {
    const router = useRouter();
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<'browse' | 'my-courses'>('browse');
    const [availableCourses, setAvailableCourses] = useState<Course[]>([]);
    const [myEnrollments, setMyEnrollments] = useState<Enrollment[]>([]);
    const [enrollingId, setEnrollingId] = useState<number | null>(null);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [coursesRes, enrollmentsRes] = await Promise.all([
                api.get('/courses'),
                api.get('/my-courses')
            ]);
            
            // Exclude courses we are already enrolled in
            const enrolledIds = (enrollmentsRes.enrollments || []).map((e: any) => e.course_id);
            const filteredCourses = (coursesRes.courses || []).filter((c: any) => !enrolledIds.includes(c.id));
            
            setAvailableCourses(filteredCourses);
            setMyEnrollments(enrollmentsRes.enrollments || []);
        } catch (error) {
            console.error('Error fetching courses:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleEnroll = async (courseId: number) => {
        if (!confirm('This will redirect you to the payment gateway to purchase this course. Proceed?')) return;
        
        setEnrollingId(courseId);
        try {
            const res = await api.post(`/payment/initiate-course`, { course_id: courseId });
            if (res.payment_url) {
                window.location.href = res.payment_url;
            } else {
                alert('Payment gateway could not be initiated.');
            }
        } catch (error: any) {
            console.error('Enrollment error:', error);
            alert(error?.message || 'Failed to initiate payment');
        } finally {
            setEnrollingId(null);
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
            <div className="max-w-6xl mx-auto">
                <div className="bg-white rounded-xl shadow-sm p-8 mb-8">
                    <div className="flex items-center justify-between mb-8">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Learning Center</h1>
                            <p className="text-gray-600 mt-2">Discover full-path courses or track your current progress.</p>
                        </div>
                        <BookOpen className="w-12 h-12 text-indigo-600" />
                    </div>

                    <div className="flex gap-2 border-b border-gray-200 mb-6">
                        <button
                            onClick={() => setActiveTab('browse')}
                            className={`px-6 py-3 font-medium transition border-b-2 ${
                                activeTab === 'browse'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            Browse Courses
                        </button>
                        <button
                            onClick={() => setActiveTab('my-courses')}
                            className={`px-6 py-3 font-medium transition border-b-2 ${
                                activeTab === 'my-courses'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            My Learning
                        </button>
                    </div>

                    {activeTab === 'browse' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {availableCourses.map(course => (
                                <div key={course.id} className="bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-indigo-300 hover:shadow-md transition flex flex-col">
                                    <div className="p-6 flex-1">
                                        <div className="flex justify-between items-start mb-3">
                                            <h3 className="font-bold text-lg text-gray-900 line-clamp-2">{course.title}</h3>
                                        </div>
                                        <div className="flex items-center gap-2 mb-4">
                                            <div className="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 text-xs font-bold">
                                                {course.mentor?.name?.charAt(0) || 'M'}
                                            </div>
                                            <span className="text-sm text-gray-600">{course.mentor?.name || 'Mentor'}</span>
                                        </div>
                                        <p className="text-gray-600 text-sm mb-4 line-clamp-3">{course.description}</p>
                                        
                                        <div className="flex flex-wrap gap-1 mb-4">
                                            {course.tags?.slice(0, 3).map((tag, i) => (
                                                <span key={i} className="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded">
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="p-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between mt-auto">
                                        <span className="font-bold text-gray-900">RM {course.price}</span>
                                        <button 
                                            onClick={() => handleEnroll(course.id)}
                                            disabled={enrollingId === course.id}
                                            className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
                                        >
                                            {enrollingId === course.id ? 'Enrolling...' : 'Enroll Now'}
                                        </button>
                                    </div>
                                </div>
                            ))}
                            {availableCourses.length === 0 && (
                                <div className="col-span-full py-12 text-center text-gray-500">
                                    No new courses available right now.
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'my-courses' && (
                        <div className="space-y-6">
                            {myEnrollments.map(enrollment => {
                                const course = enrollment.course;
                                return (
                                    <div key={enrollment.id} className="bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-indigo-300 transition p-6 flex flex-col md:flex-row gap-6">
                                        <div className="flex-1">
                                            <h3 className="font-bold text-xl text-gray-900 mb-2">{course.title}</h3>
                                            <div className="flex items-center gap-2 mb-6">
                                                <User className="w-4 h-4 text-gray-400" />
                                                <span className="text-sm text-gray-600">Mentor: {course.mentor?.name || 'Mentor'}</span>
                                            </div>

                                            {/* Progress Bar UI based on mockup */}
                                            <div className="bg-white rounded-xl border border-gray-100 p-5 shadow-sm max-w-md">
                                                <div className="flex items-center gap-2 mb-4">
                                                    <BookOpen className="w-5 h-5 text-indigo-600" />
                                                    <h4 className="font-bold text-gray-900">Learning Progress</h4>
                                                </div>
                                                
                                                <div className="mb-4">
                                                    <div className="flex justify-between text-sm mb-2">
                                                        <span className="text-gray-600 truncate mr-2">Goal: Complete Syllabus</span>
                                                        <span className="font-bold text-gray-900">{enrollment.progress_percent}%</span>
                                                    </div>
                                                    <div className="w-full h-2.5 bg-indigo-50 rounded-full overflow-hidden">
                                                        <div 
                                                            className="h-full bg-indigo-600 rounded-full transition-all duration-1000 ease-out"
                                                            style={{ width: `${enrollment.progress_percent}%` }}
                                                        ></div>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <div className="flex justify-between text-sm mb-2">
                                                        <span className="text-gray-600 truncate mr-2">Status</span>
                                                        <span className="font-bold text-gray-900">{enrollment.status === 'completed' ? 'Completed' : 'In Progress'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex flex-col justify-center gap-3 min-w-[200px]">
                                            <button 
                                                onClick={() => router.push(`/mentee/courses/${enrollment.id}`)}
                                                className="w-full px-5 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 flex items-center justify-center gap-2 transition"
                                            >
                                                <Play className="w-4 h-4" />
                                                Continue Learning
                                            </button>
                                            {enrollment.status === 'completed' && (
                                                <div className="w-full px-5 py-3 bg-green-50 text-green-700 font-medium rounded-xl flex items-center justify-center gap-2">
                                                    <CheckCircle className="w-5 h-5" />
                                                    Course Completed
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                            {myEnrollments.length === 0 && (
                                <div className="text-center py-12 bg-gray-50 rounded-lg">
                                    <BookOpen className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p className="text-gray-500">You haven't enrolled in any courses yet</p>
                                    <button 
                                        onClick={() => setActiveTab('browse')}
                                        className="mt-4 px-4 py-2 text-indigo-600 font-medium hover:bg-indigo-50 rounded-lg transition"
                                    >
                                        Browse Courses
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
