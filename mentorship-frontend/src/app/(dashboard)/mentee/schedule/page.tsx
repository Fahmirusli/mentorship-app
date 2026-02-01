'use client';

import { useState, useEffect } from 'react';
import { Calendar, Clock, User, Video, MapPin, Loader, Plus } from 'lucide-react';
import { api } from '@/lib/api';

interface Appointment {
    id: number;
    mentor_name: string;
    mentor_avatar?: string;
    date: string;
    time: string;
    duration: number;
    topic: string;
    status: 'upcoming' | 'completed' | 'cancelled';
    meeting_link?: string;
}

export default function MenteeSchedule() {
    const [loading, setLoading] = useState(true);
    const [appointments, setAppointments] = useState<Appointment[]>([]);
    const [filter, setFilter] = useState<'all' | 'upcoming' | 'completed'>('upcoming');

    useEffect(() => {
        fetchAppointments();
    }, []);

    const fetchAppointments = async () => {
        try {
            const response = await api.get('/appointments?status=' + filter);

            // Mock data for now
            const mockAppointments: Appointment[] = [
                {
                    id: 1,
                    mentor_name: 'Dr. Sarah Johnson',
                    date: '2024-01-25',
                    time: '14:00',
                    duration: 60,
                    topic: 'Career Development Strategy',
                    status: 'upcoming',
                    meeting_link: 'https://meet.google.com/abc-defg-hij'
                },
                {
                    id: 2,
                    mentor_name: 'John Smith',
                    date: '2024-01-28',
                    time: '10:00',
                    duration: 45,
                    topic: 'React Best Practices',
                    status: 'upcoming'
                },
                {
                    id: 3,
                    mentor_name: 'Emily Chen',
                    date: '2024-01-20',
                    time: '15:00',
                    duration: 60,
                    topic: 'Portfolio Review',
                    status: 'completed'
                }
            ];

            setAppointments(mockAppointments);
        } catch (error) {
            console.error('Error fetching appointments:', error);
        } finally {
            setLoading(false);
        }
    };

    const cancelAppointment = async (id: number) => {
        if (!confirm('Are you sure you want to cancel this appointment?')) return;

        try {
            await api.delete(`/appointments/${id}`);
            setAppointments(appointments.filter(a => a.id !== id));
        } catch (error) {
            console.error('Error cancelling appointment:', error);
            alert('Failed to cancel appointment');
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'upcoming':
                return 'bg-blue-100 text-blue-700';
            case 'completed':
                return 'bg-green-100 text-green-700';
            case 'cancelled':
                return 'bg-red-100 text-red-700';
            default:
                return 'bg-gray-100 text-gray-700';
        }
    };

    const filteredAppointments = appointments.filter(a =>
        filter === 'all' || a.status === filter
    );

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
                <div className="bg-white rounded-xl shadow-sm p-8">
                    <div className="flex items-center justify-between mb-8">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">My Schedule</h1>
                            <p className="text-gray-600 mt-2">Manage your mentorship sessions</p>
                        </div>
                        <button
                            onClick={() => window.location.href = '/mentee/mentors'}
                            className="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2"
                        >
                            <Plus className="w-5 h-5" />
                            Book New Session
                        </button>
                    </div>

                    {/* Filter Tabs */}
                    <div className="flex gap-2 mb-6">
                        <button
                            onClick={() => setFilter('upcoming')}
                            className={`px-4 py-2 rounded-lg font-medium transition ${filter === 'upcoming'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Upcoming
                        </button>
                        <button
                            onClick={() => setFilter('completed')}
                            className={`px-4 py-2 rounded-lg font-medium transition ${filter === 'completed'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Completed
                        </button>
                        <button
                            onClick={() => setFilter('all')}
                            className={`px-4 py-2 rounded-lg font-medium transition ${filter === 'all'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            All
                        </button>
                    </div>

                    {/* Appointments List */}
                    <div className="space-y-4">
                        {filteredAppointments.length === 0 ? (
                            <div className="text-center py-12 bg-gray-50 rounded-lg">
                                <Calendar className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500">No appointments found</p>
                                <p className="text-sm text-gray-400 mt-2">
                                    {filter === 'upcoming' ? 'Book a session to get started' : 'No appointments in this category'}
                                </p>
                            </div>
                        ) : (
                            filteredAppointments.map((appointment) => (
                                <div
                                    key={appointment.id}
                                    className="border border-gray-200 rounded-lg p-6 hover:border-indigo-300 transition"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex gap-4 flex-1">
                                            <div className="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                {appointment.mentor_name.charAt(0)}
                                            </div>

                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <h3 className="text-lg font-semibold text-gray-900">
                                                        {appointment.topic}
                                                    </h3>
                                                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(appointment.status)}`}>
                                                        {appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}
                                                    </span>
                                                </div>

                                                <div className="flex items-center gap-4 text-sm text-gray-600 mb-3">
                                                    <div className="flex items-center gap-1">
                                                        <User className="w-4 h-4" />
                                                        <span>{appointment.mentor_name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Calendar className="w-4 h-4" />
                                                        <span>{new Date(appointment.date).toLocaleDateString()}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="w-4 h-4" />
                                                        <span>{appointment.time} ({appointment.duration} min)</span>
                                                    </div>
                                                </div>

                                                {appointment.meeting_link && appointment.status === 'upcoming' && (
                                                    <a
                                                        href={appointment.meeting_link}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                                    >
                                                        <Video className="w-4 h-4" />
                                                        Join Meeting
                                                    </a>
                                                )}
                                            </div>
                                        </div>

                                        {appointment.status === 'upcoming' && (
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => window.location.href = `/appointments/${appointment.id}/reschedule`}
                                                    className="px-4 py-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition text-sm"
                                                >
                                                    Reschedule
                                                </button>
                                                <button
                                                    onClick={() => cancelAppointment(appointment.id)}
                                                    className="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition text-sm"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
