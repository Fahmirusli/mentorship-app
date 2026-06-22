'use client';

import { useState, useEffect } from 'react';
import { Calendar as CalendarIcon, Clock, Plus, Trash2, Loader, CheckCircle, XCircle } from 'lucide-react';
import { api } from '@/lib/api';

interface Appointment {
    id: number;
    mentee_name: string;
    date: string;
    time: string;
    duration_minutes: number;
    status: string;
    scheduled_at: string;
}

interface TimeSlot {
    id?: number;
    date: string;
    start_time: string;
    end_time: string;
    is_available: boolean;
    fee: number;
}

interface ScheduleApiResponse {
    schedules?: Array<{
        id?: number;
        date?: string;
        start_time?: string;
        end_time?: string;
        is_available?: boolean | number;
        fee?: number | string;
    }>;
}

const toDateInputValue = (value?: string) => {
    if (!value) return '';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return '';
    return parsed.toISOString().slice(0, 10);
};

const toTimeInputValue = (value?: string) => {
    if (!value) return '';
    return value.slice(0, 5);
};

export default function MentorSchedule() {
    const [loading, setLoading] = useState(true);
    const [schedule, setSchedule] = useState<TimeSlot[]>([]);
    const [newSlot, setNewSlot] = useState<TimeSlot>({
        date: new Date().toISOString().split('T')[0],
        start_time: '09:00',
        end_time: '17:00',
        is_available: true,
        fee: 50,
    });
    const [appointments, setAppointments] = useState<Appointment[]>([]);

    useEffect(() => {
        fetchSchedule();
        fetchAppointments();
    }, []);

    const fetchAppointments = async () => {
        try {
            const response = await api.get('/appointments');
            const data = Array.isArray(response) ? response : response.data;
            if (data) setAppointments(data);
        } catch (error) {
            console.error('Error fetching appointments:', error);
        }
    };

    const fetchSchedule = async () => {
        try {
            const response = await api.get<ScheduleApiResponse>('/schedules/my-schedule');
            const rows = Array.isArray(response.schedules) ? response.schedules : [];
            setSchedule(
                rows.map((slot) => ({
                    id: slot.id,
                    date: toDateInputValue(slot.date),
                    start_time: toTimeInputValue(slot.start_time),
                    end_time: toTimeInputValue(slot.end_time),
                    is_available: Boolean(slot.is_available),
                    fee: Number(slot.fee ?? 50),
                }))
            );
        } catch (error) {
            console.error('Error fetching schedule:', error);
        } finally {
            setLoading(false);
        }
    };

    const addTimeSlot = async () => {
        if (!newSlot.date) {
            alert('Please select a valid date.');
            return;
        }

        if (newSlot.start_time >= newSlot.end_time) {
            alert('End time must be after start time.');
            return;
        }

        if (Number.isNaN(newSlot.fee) || newSlot.fee < 0) {
            alert('Please enter a valid price.');
            return;
        }

        try {
            const response = await api.post('/schedules', newSlot);
            if (response.schedule) {
                const slot = response.schedule;
                setSchedule([
                    ...schedule,
                    {
                        id: slot.id,
                        date: toDateInputValue(slot.date),
                        start_time: toTimeInputValue(slot.start_time),
                        end_time: toTimeInputValue(slot.end_time),
                        is_available: Boolean(slot.is_available),
                        fee: Number(slot.fee ?? newSlot.fee),
                    },
                ]);
            }
            // Reset but keep date same for convenience
            setNewSlot({
                ...newSlot,
                start_time: '09:00',
                end_time: '17:00',
            });
        } catch (error: unknown) {
            console.error('Error adding time slot:', error);
            const err = error as Error;
            alert(err?.message || 'Failed to add time slot');
        }
    };

    const deleteTimeSlot = async (id: number) => {
        if (!confirm('Are you sure you want to delete this time slot?')) return;
        try {
            await api.delete(`/schedules/${id}`);
            setSchedule(schedule.filter(slot => slot.id !== id));
        } catch (error) {
            console.error('Error deleting time slot:', error);
        }
    };

    const markCompleted = async (id: number) => {
        if (!confirm('Are you sure you want to mark this as completed? Funds will be released to your wallet.')) return;
        try {
            await api.post(`/appointments/${id}/mark-completed`, {});
            alert('Session completed! Funds added to your wallet.');
            fetchAppointments();
        } catch (error: any) {
            alert(error?.message || 'Failed to complete session');
        }
    };

    const markMissed = async (id: number) => {
        if (!confirm('Are you sure the mentee missed this session? They will be notified to reschedule.')) return;
        try {
            await api.post(`/appointments/${id}/mark-missed`, {});
            alert('Session marked as missed.');
            fetchAppointments();
        } catch (error: any) {
            alert(error?.message || 'Failed to mark as missed');
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <Loader className="w-8 h-8 animate-spin text-indigo-600" />
            </div>
        );
    }

    // Group slots by date for display
    const groupedSlots: Record<string, TimeSlot[]> = {};
    schedule.forEach(slot => {
        const d = slot.date || 'unscheduled';
        if (!groupedSlots[d]) groupedSlots[d] = [];
        groupedSlots[d].push(slot);
    });

    // Sort dates
    const sortedDates = Object.keys(groupedSlots).sort((a, b) => {
        if (a === 'unscheduled') return 1;
        if (b === 'unscheduled') return -1;
        return a.localeCompare(b);
    });

    const pastAppointments = appointments.filter(a => {
        if (a.status !== 'scheduled' && a.status !== 'rescheduled') return false;
        const apptDate = new Date(a.scheduled_at);
        return apptDate < new Date();
    });

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-6xl mx-auto">
                <div className="bg-white rounded-xl shadow-sm p-8">
                    <div className="flex items-center justify-between mb-8">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Schedule Management</h1>
                            <p className="text-gray-600 mt-2">Set your availability for specific dates</p>
                        </div>
                        <CalendarIcon className="w-12 h-12 text-indigo-600" />
                    </div>

                    {/* Add New Time Slot */}
                    <div className="bg-indigo-50 rounded-lg p-6 mb-8">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Add Availability</h2>
                        <div className="mb-5 rounded-xl border border-indigo-200 bg-white px-5 py-4 text-sm text-gray-700 shadow-sm">
                            <p className="font-semibold text-indigo-700 mb-3 flex items-center gap-2">
                                <span className="text-base">📋</span> How "Add Availability" Works
                            </p>
                            <ul className="space-y-2">
                                <li className="flex items-start gap-2">
                                    <span className="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                                    <span><strong>Set a date, start time & end time</strong> — this creates one availability block (e.g. 09:00 – 17:00 on Monday). Mentees will see each hour within that block as a selectable slot (09:00, 10:00, 11:00 … 16:00).</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                                    <span><strong>Set the Price (RM)</strong> — this is the exact amount the mentee will be charged for a 1-hour session. Make sure it matches what you intend to charge.</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">3</span>
                                    <span><strong>Targeted Skills Sessions:</strong> Please ensure your single-session availability blocks are <strong>2 hours or more</strong>, as mentees booking single sessions usually need in-depth help.</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">4</span>
                                    <span><strong>Once a mentee books a slot</strong>, that slot will appear greyed-out and locked on the booking page so no one else can double-book it.</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">5</span>
                                    <span><strong>To remove a slot</strong>, click the <span className="text-red-500 font-semibold">🗑 delete</span> button next to it below. Only delete slots that have no bookings yet.</span>
                                </li>
                            </ul>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Date</label>
                                <input
                                    type="date"
                                    value={newSlot.date}
                                    onChange={(e) => setNewSlot({ ...newSlot, date: e.target.value })}
                                    min={new Date().toISOString().slice(0, 10)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                                <input
                                    type="time"
                                    value={newSlot.start_time}
                                    onChange={(e) => setNewSlot({ ...newSlot, start_time: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                                <input
                                    type="time"
                                    value={newSlot.end_time}
                                    onChange={(e) => setNewSlot({ ...newSlot, end_time: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Price (RM)</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={newSlot.fee}
                                    onChange={(e) => setNewSlot({ ...newSlot, fee: Number(e.target.value) })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div className="flex items-end">
                                <button
                                    onClick={addTimeSlot}
                                    className="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center justify-center gap-2"
                                >
                                    <Plus className="w-4 h-4" />
                                    Add Slot
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Current Schedule */}
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Upcoming Availability</h2>

                        {sortedDates.length === 0 ? (
                            <div className="text-center py-12 bg-gray-50 rounded-lg">
                                <Clock className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500">No availability set yet</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {sortedDates.map((date) => (
                                    <div key={date} className="border border-gray-200 rounded-lg overflow-hidden">
                                        <div className="bg-gray-50 px-4 py-2 border-b border-gray-200 font-medium text-gray-700">
                                            {date === 'unscheduled'
                                                ? 'Unscheduled'
                                                : new Date(date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                                        </div>
                                        <div className="p-4 space-y-2">
                                            {groupedSlots[date].map((slot) => (
                                                <div key={slot.id} className="flex items-center justify-between p-3 bg-white border border-gray-100 rounded-lg hover:shadow-sm">
                                                    <div className="flex items-center gap-6 text-gray-900 font-medium">
                                                        <div className="flex items-center">
                                                            <Clock className="w-4 h-4 text-indigo-600 mr-2" />
                                                            {slot.start_time} - {slot.end_time}
                                                        </div>
                                                        <div className="text-indigo-700 font-semibold">RM {Number(slot.fee).toFixed(2)}</div>
                                                    </div>
                                                    <button
                                                        onClick={() => slot.id && deleteTimeSlot(slot.id)}
                                                        className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Past Appointments to Verify */}
                    {pastAppointments.length > 0 && (
                        <div className="mt-12 bg-orange-50 border border-orange-200 rounded-xl p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Clock className="w-5 h-5 text-orange-600" />
                                Action Required: Verify Past Sessions
                            </h2>
                            <p className="text-sm text-gray-600 mb-4">
                                The following sessions have passed. Please mark them as completed to receive your funds, or mark them as missed if the mentee did not attend.
                            </p>
                            <div className="space-y-4">
                                {pastAppointments.map(appt => (
                                    <div key={appt.id} className="flex flex-col md:flex-row md:items-center justify-between p-4 bg-white border border-orange-100 rounded-lg shadow-sm">
                                        <div>
                                            <p className="font-semibold text-gray-900">{appt.mentee_name}</p>
                                            <p className="text-sm text-gray-500">
                                                {new Date(appt.scheduled_at).toLocaleString()} ({appt.duration_minutes} mins)
                                            </p>
                                        </div>
                                        <div className="flex gap-2 mt-3 md:mt-0">
                                            <button
                                                onClick={() => markMissed(appt.id)}
                                                className="px-4 py-2 text-sm font-medium text-orange-700 bg-orange-100 hover:bg-orange-200 rounded-lg flex items-center gap-1 transition"
                                            >
                                                <XCircle className="w-4 h-4" />
                                                Mark Missed
                                            </button>
                                            <button
                                                onClick={() => markCompleted(appt.id)}
                                                className="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg flex items-center gap-1 transition"
                                            >
                                                <CheckCircle className="w-4 h-4" />
                                                Mark Completed
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
