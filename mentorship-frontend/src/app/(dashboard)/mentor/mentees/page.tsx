'use client';

import { useState, useEffect } from 'react';
import { Users, Mail, MessageSquare, BookOpen, Calendar } from 'lucide-react';
import { api, authService } from '@/lib/api';

export default function MyMentees() {
    const [mentees, setMentees] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [showInviteModal, setShowInviteModal] = useState(false);
    const [inviteEmail, setInviteEmail] = useState('');
    const [inviteStatus, setInviteStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

    useEffect(() => {
        fetchMentees();
    }, []);

    const fetchMentees = async () => {
        try {
            // Fetch mentorships with mentee details
            const response = await api.get('/mentorships');
            setMentees(Array.isArray(response) ? response : response.data || []);
        } catch (error) {
            console.error('Error fetching mentees:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-7xl mx-auto">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">My Mentees</h1>
                        <p className="text-gray-600 mt-2">Manage your mentorship relations</p>
                    </div>
                    <button
                        onClick={() => setShowInviteModal(true)}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium"
                    >
                        Invite New Mentee
                    </button>
                </div>

                {/* Invite Modal */}
                {showInviteModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                        <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Invite New Mentee</h2>
                            <p className="text-gray-600 mb-6">Enter the email address of the mentee you'd like to invite.</p>

                            <div className="mb-6">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input
                                    type="email"
                                    value={inviteEmail}
                                    onChange={(e) => setInviteEmail(e.target.value)}
                                    placeholder="mentee@example.com"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                                {inviteStatus === 'success' && <p className="text-green-600 text-sm mt-2">Invitation sent successfully!</p>}
                                {inviteStatus === 'error' && <p className="text-red-600 text-sm mt-2">Failed to send invitation. Please try again.</p>}
                            </div>

                            <div className="flex justify-end space-x-3">
                                <button
                                    onClick={() => {
                                        setShowInviteModal(false);
                                        setInviteStatus('idle');
                                        setInviteEmail('');
                                    }}
                                    className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={async () => {
                                        if (!inviteEmail) return;
                                        setInviteStatus('sending');
                                        try {
                                            await api.post('/invite-mentee', { email: inviteEmail });
                                            setInviteStatus('success');
                                            setTimeout(() => {
                                                setShowInviteModal(false);
                                                setInviteStatus('idle');
                                                setInviteEmail('');
                                            }, 2000);
                                        } catch (error) {
                                            setInviteStatus('error');
                                        }
                                    }}
                                    disabled={inviteStatus === 'sending'}
                                    className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50"
                                >
                                    {inviteStatus === 'sending' ? 'Sending...' : 'Send Invitation'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {mentees.length === 0 ? (
                    <div className="text-center py-12 bg-white rounded-xl shadow-sm">
                        <Users className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <h3 className="text-xl font-semibold text-gray-900 mb-2">No mentees yet</h3>
                        <p className="text-gray-500">When mentees book sessions with you, they'll appear here.</p>
                    </div>
                ) : (
                    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {mentees.map((mentorship, idx) => (
                            <div key={idx} className="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex items-center space-x-3">
                                            <div className="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                {mentorship.mentee?.name?.charAt(0) || 'M'}
                                            </div>
                                            <div>
                                                <h3 className="font-bold text-gray-900">{mentorship.mentee?.name || 'Unknown Mentee'}</h3>
                                                <p className="text-sm text-gray-500">{mentorship.mentee?.bio || 'Mentee'}</p>
                                            </div>
                                        </div>
                                        <span className={`px-2 py-1 text-xs rounded-full font-medium ${mentorship.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                                            }`}>
                                            {mentorship.status}
                                        </span>
                                    </div>

                                    <div className="space-y-3 mb-6">
                                        <div className="flex items-center text-sm text-gray-600">
                                            <BookOpen className="w-4 h-4 mr-2" />
                                            <span>Goal: {mentorship.goals || 'General Mentorship'}</span>
                                        </div>
                                        <div className="flex items-center text-sm text-gray-600">
                                            <Calendar className="w-4 h-4 mr-2" />
                                            <span>Sessions: {mentorship.appointments?.length || 0} scheduled</span>
                                        </div>
                                    </div>

                                    <div className="flex space-x-2">
                                        <button 
                                            onClick={() => {
                                                window.dispatchEvent(new CustomEvent('openChat', {
                                                    detail: {
                                                        userId: mentorship.mentee?.id,
                                                        name: mentorship.mentee?.name || 'Unknown Mentee',
                                                        profile_image: null,
                                                        role: 'mentee'
                                                    }
                                                }));
                                            }}
                                            className="flex-1 px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 flex items-center justify-center"
                                        >
                                            <MessageSquare className="w-4 h-4 mr-2" />
                                            Message
                                        </button>
                                        <button className="flex-1 px-3 py-2 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center justify-center">
                                            <Calendar className="w-4 h-4 mr-2" />
                                            Schedule
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
