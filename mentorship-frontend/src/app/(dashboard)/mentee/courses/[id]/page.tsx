'use client';

import { useState, useEffect, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { BookOpen, CheckCircle, ArrowLeft, Loader, User, Upload, Clock, XCircle, FileText } from 'lucide-react';
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

interface CourseSubmission {
    id: number;
    task_index: number;
    status: 'pending' | 'approved' | 'rejected';
    file_url?: string;
    link?: string;
    notes?: string;
    mentor_feedback?: string;
}

interface Enrollment {
    id: number;
    course: Course;
    progress_percent: number;
    status: string;
    completed_tasks: number[];
    submissions?: CourseSubmission[];
}

export default function MenteeCourseDetails() {
    const params = useParams();
    const router = useRouter();
    const enrollmentId = params.id as string;
    
    const [loading, setLoading] = useState(true);
    const [enrollment, setEnrollment] = useState<Enrollment | null>(null);

    // Modal State
    const [submitModalOpen, setSubmitModalOpen] = useState(false);
    const [selectedTaskIndex, setSelectedTaskIndex] = useState<number | null>(null);
    const [submitLink, setSubmitLink] = useState('');
    const [submitNotes, setSubmitNotes] = useState('');
    const [submitFile, setSubmitFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    
    // Feedback Modal State
    const [feedbackModal, setFeedbackModal] = useState<{ isOpen: boolean, rating: number, comment: string, submitting: boolean }>({
        isOpen: false, rating: 5, comment: '', submitting: false
    });
    const fileInputRef = useRef<HTMLInputElement>(null);

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
                
                // Fire confetti if newly completed
                if (target.progress_percent === 100) {
                    const confettiKey = `confetti_${target.id}`;
                    if (!localStorage.getItem(confettiKey)) {
                        import('canvas-confetti').then((confetti) => {
                            confetti.default({
                                particleCount: 150,
                                spread: 70,
                                origin: { y: 0.6 },
                                zIndex: 9999
                            });
                        });
                        localStorage.setItem(confettiKey, 'fired');
                    }
                }
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

    const openSubmitModal = (taskIndex: number) => {
        const existingSub = enrollment?.submissions?.find(s => s.task_index === taskIndex);
        if (existingSub?.status === 'pending' || existingSub?.status === 'approved') return;

        setSelectedTaskIndex(taskIndex);
        setSubmitLink(existingSub?.link || '');
        setSubmitNotes(existingSub?.notes || '');
        setSubmitFile(null);
        setSubmitModalOpen(true);
    };

    const handleSubmitWork = async () => {
        if (!enrollment || selectedTaskIndex === null) return;
        
        setSubmitting(true);
        const formData = new FormData();
        formData.append('task_index', selectedTaskIndex.toString());
        if (submitLink) formData.append('link', submitLink);
        if (submitNotes) formData.append('notes', submitNotes);
        if (submitFile) formData.append('file', submitFile);

        try {
            await api.post(`/enrollments/${enrollment.id}/submissions`, formData);
            alert('Work submitted successfully! Waiting for mentor approval.');
            setSubmitModalOpen(false);
            await fetchEnrollment(); // Refresh data
        } catch (error: any) {
            console.error('Submit error:', error);
            alert(error?.message || 'Failed to submit work');
        } finally {
            setSubmitting(false);
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
                            <p className="text-sm text-indigo-600 mb-4">
                                {completedTasks.length} of {course.syllabus?.length || 0} tasks completed
                            </p>
                            
                            {enrollment.progress_percent >= 100 && (
                                <button
                                    onClick={() => setFeedbackModal({ isOpen: true, rating: 5, comment: '', submitting: false })}
                                    className="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg transition text-sm font-medium"
                                >
                                    Leave Course Feedback
                                </button>
                            )}
                        </div>

                        <h2 className="text-xl font-bold text-gray-900 mb-4">Syllabus & Checkpoints</h2>
                        <div className="space-y-3">
                            {course.syllabus?.map((task, idx) => {
                                const isCompleted = completedTasks.includes(idx);
                                const submission = enrollment.submissions?.find(s => s.task_index === idx);
                                
                                let statusColor = 'bg-white border-gray-200 hover:border-indigo-300';
                                if (isCompleted || submission?.status === 'approved') statusColor = 'bg-green-50 border-green-200';
                                else if (submission?.status === 'pending') statusColor = 'bg-yellow-50 border-yellow-200';
                                else if (submission?.status === 'rejected') statusColor = 'bg-red-50 border-red-200';

                                return (
                                    <div 
                                        key={idx} 
                                        onClick={() => openSubmitModal(idx)}
                                        className={`flex flex-col p-4 rounded-xl border transition cursor-pointer hover:shadow-sm ${statusColor}`}
                                    >
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0 mr-4">
                                                {(isCompleted || submission?.status === 'approved') ? (
                                                    <CheckCircle className="w-6 h-6 text-green-500" />
                                                ) : submission?.status === 'pending' ? (
                                                    <Clock className="w-6 h-6 text-yellow-500" />
                                                ) : submission?.status === 'rejected' ? (
                                                    <XCircle className="w-6 h-6 text-red-500" />
                                                ) : (
                                                    <div className="w-6 h-6 rounded-full border-2 border-gray-300 group-hover:border-indigo-400"></div>
                                                )}
                                            </div>
                                            <div className="flex flex-col flex-1">
                                                <span className={`text-lg ${(isCompleted || submission?.status === 'approved') ? 'text-gray-500 line-through' : 'text-gray-900 font-medium'}`}>
                                                    {task.title}
                                                </span>
                                                {task.link && (
                                                    <a 
                                                        href={task.link} 
                                                        target="_blank" 
                                                        rel="noopener noreferrer" 
                                                        className="text-sm text-blue-500 hover:underline mt-1 flex items-center gap-1 w-fit"
                                                        onClick={(e) => e.stopPropagation()}
                                                    >
                                                        <FileText className="w-4 h-4" />
                                                        View Resource
                                                    </a>
                                                )}
                                            </div>
                                            <div>
                                                {(isCompleted || submission?.status === 'approved') ? (
                                                    <span className="text-sm font-medium text-green-600 bg-green-100 px-3 py-1 rounded-full">Approved</span>
                                                ) : submission?.status === 'pending' ? (
                                                    <span className="text-sm font-medium text-yellow-600 bg-yellow-100 px-3 py-1 rounded-full">Pending Review</span>
                                                ) : submission?.status === 'rejected' ? (
                                                    <span className="text-sm font-medium text-red-600 bg-red-100 px-3 py-1 rounded-full">Rejected: Resubmit</span>
                                                ) : (
                                                    <button className="text-sm font-medium text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full hover:bg-indigo-100 transition">
                                                        Submit Work
                                                    </button>
                                                )}
                                                {submission?.file_url && (
                                                    <a 
                                                        href={submission.file_url.startsWith('http') ? submission.file_url : `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || 'https://api.uplifts.dev'}${submission.file_url.startsWith('/') ? '' : '/'}${submission.file_url}`} 
                                                        target="_blank" 
                                                        rel="noopener noreferrer" 
                                                        className="text-sm text-indigo-600 hover:underline mt-2 flex items-center gap-1 w-fit"
                                                        onClick={(e) => e.stopPropagation()}
                                                    >
                                                        <FileText className="w-4 h-4" />
                                                        View My File
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                        {submission?.status === 'rejected' && submission.mentor_feedback && (
                                            <div className="mt-3 ml-10 p-3 bg-red-100/50 rounded-lg border border-red-100 text-sm text-red-800">
                                                <strong>Mentor Feedback:</strong> {submission.mentor_feedback}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>

            {submitModalOpen && selectedTaskIndex !== null && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl">
                        <h3 className="text-xl font-bold text-gray-900 mb-2">Submit Your Work</h3>
                        <p className="text-gray-500 text-sm mb-6">
                            Task: {course.syllabus[selectedTaskIndex]?.title}
                        </p>
                        
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Upload File (PDF, Image, etc.)</label>
                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    onChange={(e) => setSubmitFile(e.target.files?.[0] || null)}
                                    className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                            </div>
                            
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Or paste a Link (Github, Figma, etc.)</label>
                                <input
                                    type="url"
                                    placeholder="https://"
                                    value={submitLink}
                                    onChange={(e) => setSubmitLink(e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Notes for your mentor</label>
                                <textarea
                                    rows={3}
                                    placeholder="Any context or questions..."
                                    value={submitNotes}
                                    onChange={(e) => setSubmitNotes(e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
                                />
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                onClick={() => setSubmitModalOpen(false)}
                                className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                disabled={submitting}
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleSubmitWork}
                                disabled={submitting || (!submitFile && !submitLink && !submitNotes)}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition flex items-center gap-2"
                            >
                                {submitting && <Loader className="w-4 h-4 animate-spin" />}
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Feedback Modal */}
            {feedbackModal.isOpen && (
                <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                    <div className="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
                        <div className="p-6 border-b border-gray-100 flex justify-between items-start">
                            <div>
                                <h2 className="text-xl font-bold text-gray-900 mb-1">Leave Feedback</h2>
                                <p className="text-sm text-gray-500">Share your thoughts on {course.title}</p>
                            </div>
                            <button onClick={() => setFeedbackModal(prev => ({ ...prev, isOpen: false }))} className="text-gray-400 hover:text-gray-600">
                                <XCircle className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Rating (1-5)</label>
                                <div className="flex gap-2">
                                    {[1, 2, 3, 4, 5].map(star => (
                                        <button 
                                            key={star}
                                            onClick={() => setFeedbackModal(prev => ({ ...prev, rating: star }))}
                                            className={`text-2xl ${feedbackModal.rating >= star ? 'text-yellow-400' : 'text-gray-300'}`}
                                        >
                                            ★
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Comments (Optional)</label>
                                <textarea
                                    value={feedbackModal.comment}
                                    onChange={(e) => setFeedbackModal(prev => ({ ...prev, comment: e.target.value }))}
                                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    rows={4}
                                    placeholder="Write your review here..."
                                ></textarea>
                            </div>

                            <button
                                onClick={async () => {
                                    setFeedbackModal(prev => ({ ...prev, submitting: true }));
                                    try {
                                        await api.post('/feedback', {
                                            course_id: course.id,
                                            to_user_id: course.mentor?.id,
                                            rating: feedbackModal.rating,
                                            comment: feedbackModal.comment
                                        });
                                        setFeedbackModal(prev => ({ ...prev, isOpen: false }));
                                        alert('Feedback submitted successfully!');
                                    } catch (err: any) {
                                        console.error(err);
                                        alert(err?.response?.data?.message || 'Failed to submit feedback');
                                        setFeedbackModal(prev => ({ ...prev, submitting: false }));
                                    }
                                }}
                                disabled={feedbackModal.submitting}
                                className="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50"
                            >
                                {feedbackModal.submitting ? 'Submitting...' : 'Submit Feedback'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
