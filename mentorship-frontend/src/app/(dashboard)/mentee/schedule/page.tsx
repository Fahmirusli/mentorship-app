'use client';

import { useState, useEffect, useCallback, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { Calendar, Clock, User, Video, Loader, Plus, CheckCircle, XCircle, X } from 'lucide-react';
import { api } from '@/lib/api';

interface Appointment {
    id: number;
    mentor_id?: number;
    mentor_name: string;
    mentor_avatar?: string;
    date: string;
    time: string;
    duration: number;
    topic: string;
    status: 'upcoming' | 'completed' | 'cancelled';
    meeting_link?: string;
}

type UiStatus = Appointment['status'];

interface BackendAppointment {
    id: number;
    mentorship?: {
        mentor?: { id?: number; name?: string };
    };
    mentor_name?: string;
    scheduled_at?: string;
    duration_minutes?: number;
    notes?: string;
    status?: string;
    meeting_link?: string;
}

function MenteeScheduleInner() {
    const searchParams = useSearchParams();
    const [loading, setLoading] = useState(true);
    const [appointments, setAppointments] = useState<Appointment[]>([]);
    const [filter, setFilter] = useState<'all' | 'upcoming' | 'completed'>('upcoming');
    const [paymentBanner, setPaymentBanner] = useState<'success' | 'failed' | 'pending' | null>(null);
    const [rescheduleModal, setRescheduleModal] = useState<{
        isOpen: boolean;
        appointment: Appointment | null;
        slotOptions: Array<{ date: string; time: string; label: string }>;
        selectedSlotIndex: number | null;
        loading: boolean;
    }>({
        isOpen: false,
        appointment: null,
        slotOptions: [],
        selectedSlotIndex: null,
        loading: false,
    });

    useEffect(() => {
        const payment = searchParams.get('payment');
        if (payment === 'success') setPaymentBanner('success');
        else if (payment === 'failed') setPaymentBanner('failed');
        else if (payment === 'pending') setPaymentBanner('pending');
    }, [searchParams]);

    const formatDatePart = (datePart: string) => {
        const [year, month, day] = datePart.split('-').map(Number);
        const dt = new Date(year, (month || 1) - 1, day || 1);
        return dt.toLocaleDateString();
    };

    const formatTimePart = (timePart: string) => {
        const [h, m] = timePart.split(':').map(Number);
        if (Number.isNaN(h) || Number.isNaN(m)) return 'TBD';
        const dt = new Date();
        dt.setHours(h, m, 0, 0);
        return dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    const splitScheduledAt = (scheduledAt?: string) => {
        if (!scheduledAt) return { date: '', time: '' };
        const normalized = scheduledAt.replace('T', ' ').trim();
        const [datePart = '', timePartRaw = ''] = normalized.split(' ');
        const timePart = timePartRaw.slice(0, 5);
        return { date: datePart, time: timePart };
    };

    const normalizeStatus = (status?: string): UiStatus => {
        if (status === 'completed') return 'completed';
        if (status === 'cancelled') return 'cancelled';
        if (status === 'scheduled' || status === 'pending_payment' || status === 'rescheduled') {
            return 'upcoming';
        }
        return 'upcoming';
    };

    const fetchAppointments = useCallback(async () => {
        try {
            const statusParam = filter === 'completed' ? 'completed' : filter === 'all' ? '' : 'upcoming';
            const response = await api.get(`/appointments${statusParam ? `?status=${statusParam}` : ''}`);
            const records: BackendAppointment[] = Array.isArray(response) ? response : [];

            const mappedAppointments: Appointment[] = records.map((item) => {
                const scheduled = splitScheduledAt(item.scheduled_at);
                const mentorName = item.mentorship?.mentor?.name || item.mentor_name || 'Mentor';
                const normalizedStatus = normalizeStatus(item.status);

                return {
                    id: item.id,
                    mentor_id: item.mentorship?.mentor?.id,
                    mentor_name: mentorName,
                    date: scheduled.date,
                    time: scheduled.time ? formatTimePart(scheduled.time) : 'TBD',
                    duration: item.duration_minutes || 60,
                    topic: item.notes || 'Mentorship Session',
                    status: normalizedStatus,
                    meeting_link: normalizeMeetingLink(item.meeting_link),
                };
            });

            setAppointments(mappedAppointments);
        } catch (error) {
            console.error('Error fetching appointments:', error);
        } finally {
            setLoading(false);
        }
    }, [filter]);

    const openRescheduleModal = async (appointment: Appointment) => {
        if (!appointment.mentor_id) {
            alert('Mentor information is missing for this appointment.');
            return;
        }

        setRescheduleModal(prev => ({ ...prev, isOpen: true, loading: true, appointment, slotOptions: [], selectedSlotIndex: null }));

        const startDate = new Date().toISOString().slice(0, 10);
        const endDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);

        let slotOptions: Array<{ date: string; time: string; label: string }> = [];

        try {
            const response: any = await api.get(`/schedules/mentor/${appointment.mentor_id}?start_date=${startDate}&end_date=${endDate}`);
            const schedules = Array.isArray(response?.schedules) ? response.schedules : [];

            slotOptions = schedules
                .map((slot: any) => {
                    const date = (slot?.date || '').toString().slice(0, 10);
                    const time = (slot?.start_time || '').toString().slice(0, 5);
                    if (!date || !time) return null;
                    const label = `${formatDatePart(date)} - ${formatTimePart(time)}`;
                    return { date, time, label };
                })
                .filter(Boolean);
        } catch (error) {
            console.error('Error loading available slots:', error);
            alert('Failed to load mentor available slots for reschedule.');
            setRescheduleModal(prev => ({ ...prev, isOpen: false }));
            return;
        }

        setRescheduleModal(prev => ({ ...prev, loading: false, slotOptions }));
    };

    const confirmReschedule = async () => {
        const { appointment, slotOptions, selectedSlotIndex } = rescheduleModal;
        if (!appointment || selectedSlotIndex === null) return;

        const chosenSlot = slotOptions[selectedSlotIndex];
        if (!chosenSlot) return;

        try {
            await api.patch(`/appointments/${appointment.id}/reschedule`, {
                scheduled_at: `${chosenSlot.date} ${chosenSlot.time}:00`,
                duration_minutes: appointment.duration,
                notes: appointment.topic,
            });

            await fetchAppointments();
            alert('Appointment rescheduled successfully');
            setRescheduleModal({ isOpen: false, appointment: null, slotOptions: [], selectedSlotIndex: null, loading: false });
        } catch (error: any) {
            console.error('Error rescheduling appointment:', error);
            alert(error?.message || 'Failed to reschedule appointment');
        }
    };

    const normalizeMeetingLink = (value?: string) => {
        if (!value) return undefined;
        const trimmed = value.trim();
        if (!trimmed) return undefined;
        if (/^https?:\/\//i.test(trimmed)) return trimmed;
        return `https://${trimmed}`;
    };

    useEffect(() => {
        fetchAppointments();
    }, [fetchAppointments]);

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

                {/* Payment Result Banner */}
                {paymentBanner === 'success' && (
                    <div className="mb-6 flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl shadow-sm">
                        <CheckCircle className="w-6 h-6 text-green-500 flex-shrink-0" />
                        <div className="flex-1">
                            <p className="font-semibold text-green-800">Payment Successful! 🎉</p>
                            <p className="text-sm text-green-600">Your session has been booked. Check your upcoming sessions below.</p>
                        </div>
                        <button onClick={() => setPaymentBanner(null)} className="text-green-400 hover:text-green-600"><X className="w-5 h-5" /></button>
                    </div>
                )}

                {paymentBanner === 'failed' && (
                    <div className="mb-6 flex items-center gap-4 p-5 bg-red-50 border border-red-200 rounded-xl shadow-sm">
                        <XCircle className="w-8 h-8 text-red-500 flex-shrink-0" />
                        <div className="flex-1">
                            <p className="font-bold text-red-800 text-lg">Payment Failed</p>
                            <p className="text-sm text-red-600 mt-0.5">Your payment was not completed. The slot is now available again.</p>
                            <a
                                href="/mentee/mentors"
                                className="inline-flex items-center mt-3 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition"
                            >
                                Find Another Mentor &rarr;
                            </a>
                        </div>
                        <button onClick={() => setPaymentBanner(null)} className="text-red-400 hover:text-red-600"><X className="w-5 h-5" /></button>
                    </div>
                )}

                {paymentBanner === 'pending' && (
                    <div className="mb-6 flex items-center gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-xl shadow-sm">
                        <Clock className="w-6 h-6 text-yellow-500 flex-shrink-0" />
                        <div className="flex-1">
                            <p className="font-semibold text-yellow-800">Payment Pending</p>
                            <p className="text-sm text-yellow-600">Your payment is being processed. We'll update your session once confirmed.</p>
                        </div>
                        <button onClick={() => setPaymentBanner(null)} className="text-yellow-400 hover:text-yellow-600"><X className="w-5 h-5" /></button>
                    </div>
                )}

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
                                            <div className="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
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
                                                        <span>{appointment.date ? formatDatePart(appointment.date) : 'TBD'}</span>
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
                                                    onClick={() => openRescheduleModal(appointment)}
                                                    className="px-4 py-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition text-sm font-medium border border-indigo-100"
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

            {/* Reschedule Modal */}
            {rescheduleModal.isOpen && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                        <div className="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 className="text-xl font-bold text-gray-900">Reschedule Session</h3>
                            <button 
                                onClick={() => setRescheduleModal({ isOpen: false, appointment: null, slotOptions: [], selectedSlotIndex: null, loading: false })}
                                className="text-gray-400 hover:text-gray-600 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <div className="p-6 max-h-[60vh] overflow-y-auto">
                            {rescheduleModal.loading ? (
                                <div className="flex flex-col items-center justify-center py-8">
                                    <Loader className="w-8 h-8 animate-spin text-indigo-600 mb-2" />
                                    <p className="text-gray-500 text-sm">Loading available slots...</p>
                                </div>
                            ) : rescheduleModal.slotOptions.length === 0 ? (
                                <div className="text-center py-8">
                                    <p className="text-gray-600 font-medium">No available slots found.</p>
                                    <p className="text-gray-500 text-sm mt-1">This mentor hasn't opened any upcoming slots.</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <p className="text-sm font-medium text-gray-700 mb-4">Select a new time for your session with {rescheduleModal.appointment?.mentor_name}:</p>
                                    {rescheduleModal.slotOptions.map((slot, idx) => (
                                        <div 
                                            key={idx}
                                            onClick={() => setRescheduleModal(prev => ({ ...prev, selectedSlotIndex: idx }))}
                                            className={`p-4 border rounded-xl cursor-pointer transition-all ${rescheduleModal.selectedSlotIndex === idx ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-gray-200 hover:border-indigo-300 hover:bg-gray-50'}`}
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className={`w-5 h-5 rounded-full border flex items-center justify-center ${rescheduleModal.selectedSlotIndex === idx ? 'border-indigo-600' : 'border-gray-300'}`}>
                                                    {rescheduleModal.selectedSlotIndex === idx && <div className="w-2.5 h-2.5 bg-indigo-600 rounded-full"></div>}
                                                </div>
                                                <div className="flex flex-col">
                                                    <span className={`font-semibold ${rescheduleModal.selectedSlotIndex === idx ? 'text-indigo-900' : 'text-gray-900'}`}>{formatDatePart(slot.date)}</span>
                                                    <span className={`text-sm ${rescheduleModal.selectedSlotIndex === idx ? 'text-indigo-700' : 'text-gray-500'}`}>{formatTimePart(slot.time)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                        {!rescheduleModal.loading && rescheduleModal.slotOptions.length > 0 && (
                            <div className="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                                <button 
                                    onClick={() => setRescheduleModal({ isOpen: false, appointment: null, slotOptions: [], selectedSlotIndex: null, loading: false })}
                                    className="px-5 py-2.5 text-gray-600 font-medium hover:bg-gray-200 rounded-xl transition"
                                >
                                    Cancel
                                </button>
                                <button 
                                    onClick={confirmReschedule}
                                    disabled={rescheduleModal.selectedSlotIndex === null}
                                    className="px-5 py-2.5 bg-indigo-600 text-white font-medium hover:bg-indigo-700 rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Confirm Reschedule
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function MenteeSchedule() {
    return (
        <Suspense fallback={<div className="min-h-screen flex items-center justify-center"><Loader className="w-8 h-8 animate-spin text-indigo-600" /></div>}>
            <MenteeScheduleInner />
        </Suspense>
    );
}
