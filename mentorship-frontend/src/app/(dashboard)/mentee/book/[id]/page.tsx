'use client';

import { useState, useEffect, use } from 'react';
import { ArrowLeft, Calendar, CreditCard, Lock, Clock } from 'lucide-react';
import { api } from '@/lib/api';

interface ScheduleSlot {
    id: number;
    date: string;
    start_time: string;   // "HH:mm"
    end_time: string;     // "HH:mm"
    fee: number;
    is_available: boolean;
    is_booked: boolean;
    // Computed display strings
    displayStart: string;
    displayEnd: string;
    durationLabel: string;
}

function fmt24to12(time: string): string {
    const [h, m] = time.split(':').map(Number);
    const suffix = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 === 0 ? 12 : h % 12;
    return `${h12}:${m.toString().padStart(2, '0')} ${suffix}`;
}

function durationLabel(start: string, end: string): string {
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    const mins = (eh * 60 + em) - (sh * 60 + sm);
    if (mins <= 0) return '';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    if (h === 0) return `${m} min`;
    if (m === 0) return `${h} hr`;
    return `${h} hr ${m} min`;
}

export default function BookSession({ params }: { params: Promise<{ id: string }> }) {
    const { id } = use(params);
    const [selectedDate, setSelectedDate] = useState('');
    const [topic, setTopic] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const [allSlots, setAllSlots] = useState<ScheduleSlot[]>([]);
    const [mentorName, setMentorName] = useState('');
    const [selectedSlot, setSelectedSlot] = useState<ScheduleSlot | null>(null);

    // Unique dates that have at least one slot
    const availableDates = [...new Set(allSlots.map(s => s.date))].sort();

    // Slots for the selected date
    const slotsForDate = allSlots.filter(s => s.date === selectedDate);
    const availableCount = slotsForDate.filter(s => !s.is_booked).length;
    const bookedCount = slotsForDate.filter(s => s.is_booked).length;

    useEffect(() => {
        const fetchSchedule = async () => {
            try {
                const startDate = new Date().toISOString().split('T')[0];
                const endDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                const response = await api.get(`/schedules/mentor/${id}?start_date=${startDate}&end_date=${endDate}`);
                setMentorName(response.mentor?.name || '');

                const raw: any[] = response.schedules || [];
                const slots: ScheduleSlot[] = raw.map((s: any) => ({
                    id: s.id,
                    date: s.date,
                    start_time: s.start_time,
                    end_time: s.end_time,
                    fee: Number(s.fee ?? 50),
                    is_available: Boolean(s.is_available),
                    is_booked: Boolean(s.is_booked),
                    displayStart: fmt24to12(s.start_time),
                    displayEnd: fmt24to12(s.end_time),
                    durationLabel: durationLabel(s.start_time, s.end_time),
                }));

                setAllSlots(slots);
            } catch (err) {
                console.error('Error fetching schedule:', err);
                setError('Failed to load available time slots');
            }
        };
        fetchSchedule();
    }, [id]);

    // Clear selection when date changes
    useEffect(() => {
        setSelectedSlot(null);
    }, [selectedDate]);

    const handleSelectSlot = (slot: ScheduleSlot) => {
        if (slot.is_booked) return;
        setSelectedSlot(slot);
    };

    const handleBook = async () => {
        if (!selectedSlot) return;
        setLoading(true);
        setError('');
        try {
            // Send the schedule's start_time as the session start; backend derives duration from block
            const scheduledAt = `${selectedSlot.date} ${selectedSlot.start_time}:00`;

            const response = await api.post('/payment/initiate', {
                mentor_id: id,
                scheduled_at: scheduledAt,
                notes: topic,
            });

            if (response.payment_url) {
                window.location.href = response.payment_url;
            } else {
                throw new Error('No payment URL received');
            }
        } catch (err: any) {
            console.error(err);
            setError(err.response?.data?.message || err.message || 'Failed to initiate payment. Please try again.');
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div className="max-w-lg w-full bg-white rounded-xl shadow-lg overflow-hidden">
                {/* Header */}
                <div className="bg-indigo-600 px-6 py-4 flex items-center">
                    <button onClick={() => window.history.back()} className="text-white/80 hover:text-white mr-4">
                        <ArrowLeft className="w-5 h-5" />
                    </button>
                    <div>
                        <h1 className="text-xl font-bold text-white">Book Session</h1>
                        {mentorName && <p className="text-indigo-200 text-xs mt-0.5">with {mentorName}</p>}
                    </div>
                </div>

                <div className="p-6 space-y-6">
                    {error && (
                        <div className="p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-200">
                            {error}
                        </div>
                    )}

                    {/* Date Selection */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
                        <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input
                                type="date"
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                value={selectedDate}
                                onChange={(e) => setSelectedDate(e.target.value)}
                                min={new Date().toISOString().split('T')[0]}
                            />
                        </div>
                        {/* Show which dates have availability */}
                        {availableDates.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-1.5">
                                {availableDates.map(d => {
                                    const hasAvail = allSlots.some(s => s.date === d && !s.is_booked);
                                    return (
                                        <button
                                            key={d}
                                            onClick={() => setSelectedDate(d)}
                                            className={`text-xs px-2.5 py-1 rounded-full border transition ${
                                                selectedDate === d
                                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                                    : hasAvail
                                                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:border-indigo-400'
                                                        : 'bg-gray-50 text-gray-400 border-gray-200'
                                            }`}
                                        >
                                            {new Date(d + 'T00:00:00').toLocaleDateString('en-MY', { weekday: 'short', month: 'short', day: 'numeric' })}
                                            {!hasAvail && ' (full)'}
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Session Slots */}
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <label className="block text-sm font-medium text-gray-700">Available Sessions</label>
                            {slotsForDate.length > 0 && (
                                <span className="text-xs text-gray-500">
                                    {availableCount} available · {bookedCount} booked
                                </span>
                            )}
                        </div>

                        {/* Legend */}
                        {slotsForDate.length > 0 && (
                            <div className="flex items-center gap-4 text-xs text-gray-500 mb-3">
                                <span className="flex items-center gap-1.5">
                                    <span className="w-3 h-3 rounded-sm bg-indigo-100 border border-indigo-300 inline-block" />
                                    Available
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <span className="w-3 h-3 rounded-sm bg-gray-100 border border-gray-200 inline-block" />
                                    Already booked
                                </span>
                            </div>
                        )}

                        <div className="space-y-2">
                            {slotsForDate.length === 0 ? (
                                <p className="text-sm text-gray-500 text-center py-6 bg-gray-50 rounded-lg">
                                    {selectedDate ? 'No sessions set for this date' : 'Please select a date above'}
                                </p>
                            ) : (
                                slotsForDate.map(slot => {
                                    const isSelected = selectedSlot?.id === slot.id;
                                    if (slot.is_booked) {
                                        return (
                                            <div
                                                key={slot.id}
                                                className="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed select-none"
                                                title="Already booked"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <Clock className="w-4 h-4 text-gray-300" />
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-400">
                                                            {slot.displayStart} – {slot.displayEnd}
                                                        </p>
                                                        <p className="text-xs text-gray-400">{slot.durationLabel}</p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2 text-gray-400">
                                                    <Lock className="w-3.5 h-3.5" />
                                                    <span className="text-xs font-medium">Booked</span>
                                                </div>
                                            </div>
                                        );
                                    }
                                    return (
                                        <button
                                            key={slot.id}
                                            onClick={() => handleSelectSlot(slot)}
                                            className={`w-full flex items-center justify-between px-4 py-3 rounded-lg border transition text-left ${
                                                isSelected
                                                    ? 'bg-indigo-600 border-indigo-600 shadow-md'
                                                    : 'bg-white border-gray-200 hover:border-indigo-400 hover:bg-indigo-50'
                                            }`}
                                        >
                                            <div className="flex items-center gap-3">
                                                <Clock className={`w-4 h-4 ${isSelected ? 'text-indigo-200' : 'text-indigo-600'}`} />
                                                <div>
                                                    <p className={`text-sm font-semibold ${isSelected ? 'text-white' : 'text-gray-800'}`}>
                                                        {slot.displayStart} – {slot.displayEnd}
                                                    </p>
                                                    <p className={`text-xs ${isSelected ? 'text-indigo-200' : 'text-gray-500'}`}>
                                                        {slot.durationLabel}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className={`text-right`}>
                                                <p className={`text-base font-bold ${isSelected ? 'text-white' : 'text-indigo-700'}`}>
                                                    RM {slot.fee.toFixed(2)}
                                                </p>
                                                <p className={`text-xs ${isSelected ? 'text-indigo-200' : 'text-gray-400'}`}>
                                                    per session
                                                </p>
                                            </div>
                                        </button>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    {/* Topic */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Discussion Topic</label>
                        <textarea
                            rows={3}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
                            placeholder="What would you like to discuss?"
                            value={topic}
                            onChange={(e) => setTopic(e.target.value)}
                        />
                    </div>

                    {/* Summary */}
                    <div className="bg-indigo-50 border border-indigo-100 p-4 rounded-lg">
                        {selectedSlot ? (
                            <>
                                <div className="flex justify-between text-sm mb-1.5">
                                    <span className="text-gray-600">Session</span>
                                    <span className="font-medium text-gray-800">
                                        {selectedSlot.displayStart} – {selectedSlot.displayEnd}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm mb-1.5">
                                    <span className="text-gray-600">Duration</span>
                                    <span className="font-medium text-gray-800">{selectedSlot.durationLabel}</span>
                                </div>
                                <div className="border-t border-indigo-200 pt-3 mt-2 flex justify-between items-center">
                                    <span className="font-bold text-gray-800">Total to Pay</span>
                                    <span className="font-bold text-xl text-indigo-700">
                                        RM {selectedSlot.fee.toFixed(2)}
                                    </span>
                                </div>
                            </>
                        ) : (
                            <p className="text-sm text-gray-400 text-center py-1">
                                Select an available session above to see the price
                            </p>
                        )}
                    </div>

                    <button
                        onClick={handleBook}
                        disabled={!selectedSlot || loading}
                        className="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
                    >
                        {loading ? (
                            <>
                                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2" />
                                Processing...
                            </>
                        ) : (
                            <>
                                <CreditCard className="w-4 h-4 mr-2" />
                                {selectedSlot
                                    ? `Pay RM ${selectedSlot.fee.toFixed(2)} & Confirm`
                                    : 'Pay & Confirm Booking'
                                }
                            </>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}
