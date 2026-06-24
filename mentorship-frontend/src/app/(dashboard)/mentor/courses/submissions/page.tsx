'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';
import { Loader, CheckCircle, XCircle, Clock, FileText, User, BookOpen, ExternalLink } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

interface Submission {
    id: number;
    course_enrollment_id: number;
    task_index: number;
    file_url?: string;
    link?: string;
    notes?: string;
    status: 'pending' | 'approved' | 'rejected';
    created_at: string;
    enrollment: {
        id: number;
        mentee: {
            id: number;
            name: string;
        };
        course: {
            id: number;
            title: string;
            syllabus: { title: string }[];
        };
    };
}

export default function MentorSubmissions() {
    const [submissions, setSubmissions] = useState<Submission[]>([]);
    const [loading, setLoading] = useState(true);
    
    // Review Modal State
    const [reviewModalOpen, setReviewModalOpen] = useState(false);
    const [selectedSubmission, setSelectedSubmission] = useState<Submission | null>(null);
    const [feedback, setFeedback] = useState('');
    const [action, setAction] = useState<'approved' | 'rejected'>('approved');
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        fetchSubmissions();
    }, []);

    const fetchSubmissions = async () => {
        try {
            const res = await api.get('/mentor/submissions');
            setSubmissions(res.submissions || []);
        } catch (error) {
            console.error('Error fetching submissions:', error);
        } finally {
            setLoading(false);
        }
    };

    const openReviewModal = (submission: Submission, actionType: 'approved' | 'rejected') => {
        setSelectedSubmission(submission);
        setAction(actionType);
        setFeedback('');
        setReviewModalOpen(true);
    };

    const handleReviewSubmit = async () => {
        if (!selectedSubmission) return;
        if (action === 'rejected' && !feedback.trim()) {
            alert('Please provide feedback for the rejection.');
            return;
        }

        setSubmitting(true);
        try {
            await api.patch(`/submissions/${selectedSubmission.id}/review`, {
                status: action,
                mentor_feedback: feedback
            });
            if (action === 'approved') {
                toast.success('Submission approved successfully! 🎉', {
                    duration: 4000,
                    position: 'top-center',
                    style: {
                        background: '#10B981',
                        color: '#fff',
                        fontWeight: 'bold',
                        padding: '16px',
                        borderRadius: '12px',
                    },
                    iconTheme: {
                        primary: '#fff',
                        secondary: '#10B981',
                    },
                });
            } else {
                toast.success('Submission rejected and sent back to mentee.', {
                    position: 'top-center',
                });
            }
            
            setReviewModalOpen(false);
            await fetchSubmissions(); // Refresh the list
        } catch (error: any) {
            console.error('Error reviewing submission:', error);
            alert(error?.message || 'Failed to review submission');
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

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <Toaster />
            <div className="max-w-5xl mx-auto">
                <div className="flex items-center gap-6 mb-8 border-b border-gray-200">
                    <Link href="/mentor/courses" className="px-1 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition">
                        My Courses
                    </Link>
                    <Link href="/mentor/courses/submissions" className="px-1 py-3 border-b-2 border-indigo-600 text-indigo-600 font-semibold">
                        Review Submissions
                    </Link>
                </div>

                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Review Submissions</h1>
                        <p className="text-gray-600 mt-2">Approve or reject mentee work to advance their learning progress.</p>
                    </div>
                    <Clock className="w-12 h-12 text-indigo-600 opacity-20" />
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    {submissions.length === 0 ? (
                        <div className="p-16 text-center">
                            <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">All Caught Up!</h2>
                            <p className="text-gray-500">There are no pending submissions from your mentees at this time.</p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {submissions.map((sub) => {
                                const taskTitle = sub.enrollment.course.syllabus[sub.task_index]?.title || `Task #${sub.task_index + 1}`;
                                return (
                                    <li key={sub.id} className="p-6 hover:bg-gray-50 transition">
                                        <div className="flex flex-col md:flex-row gap-6 items-start md:items-center">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <span className="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full flex items-center gap-1">
                                                        <Clock className="w-3 h-3" />
                                                        Pending Review
                                                    </span>
                                                    <span className="text-sm text-gray-500">
                                                        {new Date(sub.created_at).toLocaleDateString()}
                                                    </span>
                                                </div>
                                                <h3 className="text-lg font-bold text-gray-900 mb-1">{taskTitle}</h3>
                                                <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-4">
                                                    <div className="flex items-center gap-1">
                                                        <User className="w-4 h-4 text-gray-400" />
                                                        Mentee: <span className="font-medium text-gray-900">{sub.enrollment.mentee.name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <BookOpen className="w-4 h-4 text-gray-400" />
                                                        Course: {sub.enrollment.course.title}
                                                    </div>
                                                </div>

                                                <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                    {sub.notes && (
                                                        <div className="mb-3">
                                                            <strong className="text-gray-900 text-sm block mb-1">Mentee's Notes:</strong>
                                                            <p className="text-gray-700 text-sm italic">"{sub.notes}"</p>
                                                        </div>
                                                    )}
                                                    
                                                    <div className="flex flex-wrap gap-3 mt-2">
                                                        {sub.file_url && (
                                                            <a 
                                                                href={sub.file_url.startsWith('http') ? sub.file_url : `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || 'https://api.uplifts.dev'}${sub.file_url.startsWith('/') ? '' : '/'}${sub.file_url}`} 
                                                                target="_blank" 
                                                                rel="noopener noreferrer"
                                                                className="flex items-center gap-2 text-sm text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition"
                                                            >
                                                                <FileText className="w-4 h-4" />
                                                                View Uploaded File
                                                            </a>
                                                        )}
                                                        {sub.link && (
                                                            <a 
                                                                href={sub.link} 
                                                                target="_blank" 
                                                                rel="noopener noreferrer"
                                                                className="flex items-center gap-2 text-sm text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition"
                                                            >
                                                                <ExternalLink className="w-4 h-4" />
                                                                View Resource Link
                                                            </a>
                                                        )}
                                                        {!sub.file_url && !sub.link && (
                                                            <span className="text-sm text-gray-500 italic">No attachments provided.</span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex md:flex-col gap-2 w-full md:w-auto mt-4 md:mt-0">
                                                <button 
                                                    onClick={() => openReviewModal(sub, 'approved')}
                                                    className="flex-1 px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition flex items-center justify-center gap-2"
                                                >
                                                    <CheckCircle className="w-4 h-4" />
                                                    Approve
                                                </button>
                                                <button 
                                                    onClick={() => openReviewModal(sub, 'rejected')}
                                                    className="flex-1 px-4 py-2 bg-white text-red-600 font-medium border border-red-200 rounded-lg hover:bg-red-50 transition flex items-center justify-center gap-2"
                                                >
                                                    <XCircle className="w-4 h-4" />
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            </div>

            {/* Review Modal */}
            {reviewModalOpen && selectedSubmission && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl">
                        <h3 className={`text-xl font-bold mb-2 ${action === 'approved' ? 'text-green-600' : 'text-red-600'}`}>
                            {action === 'approved' ? 'Approve Submission' : 'Reject Submission'}
                        </h3>
                        <p className="text-gray-500 text-sm mb-6">
                            {action === 'approved' 
                                ? "Approving this will advance the mentee's learning progress for this course." 
                                : "Rejecting this will require the mentee to review your feedback and resubmit their work."}
                        </p>
                        
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Feedback for Mentee {action === 'rejected' ? '(Required)' : '(Optional)'}
                                </label>
                                <textarea
                                    rows={4}
                                    placeholder={action === 'rejected' ? "Explain why it was rejected and what they need to fix..." : "Great job on this task!"}
                                    value={feedback}
                                    onChange={(e) => setFeedback(e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
                                />
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                onClick={() => setReviewModalOpen(false)}
                                className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                disabled={submitting}
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleReviewSubmit}
                                disabled={submitting || (action === 'rejected' && !feedback.trim())}
                                className={`px-6 py-2 text-white rounded-lg transition flex items-center gap-2 disabled:opacity-50 ${
                                    action === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'
                                }`}
                            >
                                {submitting && <Loader className="w-4 h-4 animate-spin" />}
                                Confirm {action === 'approved' ? 'Approval' : 'Rejection'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
