'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { BookOpen, CheckCircle, ArrowLeft, Loader, User } from 'lucide-react';
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

export default function MenteeCourseDetails() {
    const params = useParams();
    const router = useRouter();
    const enrollmentId = params.id as string;
    
    const [loading, setLoading] = useState(true);
    const [enrollment, setEnrollment] = useState<Enrollment | null>(null);

    useEffect(() => {
        if (enrollmentId) {
            fetchEnrollment();
        }
    }, [enrollmentId]);

    const fetchEnrollment = async () => {
        try {
            const res = await api.get('/my-courses');
            const target = (res.enrollments || []).find((e: any) => e.id.toString() === enrollmentId);
            if (target) {
                setEnrollment(target);
            } else {
                alert('Course enrollment not found.');
                router.push('/mentee/courses');
            }
        } catch (error) {
            console.error('Error fetching enrollment:', error);
        } finally {
            setLoading(false);
        }
    };

    const toggleTask = async (taskIndex: number, currentCompleted: number[]) => {
        if (!enrollment) return;
        
        const isCompleted = currentCompleted.includes(taskIndex);
        const newStatus = !isCompleted;

        // Optimistic UI update
        const updatedCompleted = newStatus 
            ? [...currentCompleted, taskIndex]
            : currentCompleted.filter(i => i !== taskIndex);
            
        const total = enrollment.course.syllabus.length;
        const newPercent = total > 0 ? Math.round((updatedCompleted.length / total) * 100) : 0;
        
        if (newPercent === 100 && newStatus === true && enrollment.progress_percent !== 100) {
            import('canvas-confetti').then((confetti) => {
                confetti.default({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 },
                    zIndex: 9999
                });
            });
        }

        setEnrollment({
            ...enrollment,
            completed_tasks: updatedCompleted,
            progress_percent: newPercent,
            status: newPercent >= 100 ? 'completed' : 'active'
        });

        try {
            await api.patch(`/enrollments/${enrollment.id}/progress`, {
                task_index: taskIndex,
                completed: newStatus
            });
        } catch (error) {
            console.error('Error updating progress:', error);
            alert('Failed to update progress. Reverting.');
            await fetchEnrollment(); // Revert on failure
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <Loader className="w-8 h-8 animate-spin text-indigo-600" />
            </div>
        );
    }

    if (!enrollment) return null;

    const course = enrollment.course;
    const completedTasks = enrollment.completed_tasks || [];

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-4xl mx-auto">
                <button 
                    onClick={() => router.push('/mentee/courses')}
                    className="flex items-center gap-2 text-gray-500 hover:text-indigo-600 font-medium mb-6 transition"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Back to My Courses
                </button>

                <div className="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
                    <div className="p-8 border-b border-gray-100 bg-indigo-900 text-white">
                        <div className="flex gap-2 mb-4 flex-wrap">
                            {course.tags?.map((tag, i) => (
                                <span key={i} className="text-xs px-2 py-1 bg-white/20 rounded-full font-medium">
                                    {tag}
                                </span>
                            ))}
                        </div>
                        <h1 className="text-3xl font-bold mb-4">{course.title}</h1>
                        <div className="flex items-center gap-2 text-indigo-100">
                            <User className="w-4 h-4" />
                            <span>Mentor: {course.mentor?.name || 'Mentor'}</span>
                        </div>
                    </div>

                    <div className="p-8">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Course Description</h2>
                        <p className="text-gray-600 mb-8 whitespace-pre-wrap">{course.description}</p>

                        <div className="bg-indigo-50 rounded-xl p-6 mb-8 border border-indigo-100">
                            <div className="flex items-center justify-between mb-2">
                                <h3 className="font-bold text-indigo-900 flex items-center gap-2">
                                    <BookOpen className="w-5 h-5" />
                                    Your Learning Progress
                                </h3>
                                <span className="font-bold text-indigo-700 text-lg">{enrollment.progress_percent}%</span>
                            </div>
                            <div className="w-full h-3 bg-white rounded-full overflow-hidden shadow-inner mb-2">
                                <div 
                                    className="h-full bg-indigo-600 rounded-full transition-all duration-1000 ease-out"
                                    style={{ width: `${enrollment.progress_percent}%` }}
                                ></div>
                            </div>
                            <p className="text-sm text-indigo-600">
                                {completedTasks.length} of {course.syllabus?.length || 0} tasks completed
                            </p>
                        </div>

                        <h2 className="text-xl font-bold text-gray-900 mb-4">Syllabus & Checkpoints</h2>
                        <div className="space-y-3">
                            {course.syllabus?.map((task, idx) => {
                                const isCompleted = completedTasks.includes(idx);
                                return (
                                    <div 
                                        key={idx} 
                                        onClick={() => toggleTask(idx, completedTasks)}
                                        className={`flex items-center p-4 rounded-xl border transition cursor-pointer hover:shadow-sm ${
                                            isCompleted 
                                            ? 'bg-green-50 border-green-200' 
                                            : 'bg-white border-gray-200 hover:border-indigo-300'
                                        }`}
                                    >
                                        <div className="flex-shrink-0 mr-4">
                                            {isCompleted ? (
                                                <CheckCircle className="w-6 h-6 text-green-500" />
                                            ) : (
                                                <div className="w-6 h-6 rounded-full border-2 border-gray-300"></div>
                                            )}
                                        </div>
                                        <div className="flex flex-col">
                                            <span className={`text-lg ${isCompleted ? 'text-gray-500 line-through' : 'text-gray-900 font-medium'}`}>
                                                {task.title}
                                            </span>
                                            {task.link && (
                                                <a 
                                                    href={task.link} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    className="text-sm text-blue-500 hover:underline mt-1"
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    {task.link}
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
