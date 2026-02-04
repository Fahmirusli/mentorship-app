'use client';

import { useState, useEffect, use } from 'react';
import {
    ArrowLeft, Calendar, Clock, Sparkles, CheckCircle, CreditCard
} from 'lucide-react';
import { api } from '@/lib/api';

export default function BookSession({ params }: { params: Promise<{ id: string }> }) {
    const { id } = use(params);
    const [selectedDate, setSelectedDate] = useState('');
    const [selectedTime, setSelectedTime] = useState('');
    const [topic, setTopic] = useState('');
    const [step, setStep] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const [availableSlots, setAvailableSlots] = useState<string[]>([]);
    const [schedules, setSchedules] = useState<any[]>([]);

    useEffect(() => {
        const fetchSchedule = async () => {
            try {
                // Fetch schedules for next 14 days to match backend
                const startDate = new Date().toISOString().split('T')[0];
                const endDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                
                const response = await api.get(`/schedules/mentor/${id}?start_date=${startDate}&end_date=${endDate}`);
                setSchedules(response.schedules || []);
                console.log('Loaded schedules:', response.schedules?.length, 'slots');
            } catch (err) {
                console.error('Error fetching schedule:', err);
                setError('Failed to load available time slots');
            }
        };
        fetchSchedule();
    }, [id]);

    useEffect(() => {
        if (selectedDate && schedules.length > 0) {
            generateSlots_v2();
        }
    }, [selectedDate, schedules]);

    const generateSlots_v2 = () => {
        const selected = new Date(selectedDate);
        const selectedDateStr = selectedDate; // Already in YYYY-MM-DD format

        // Find matching schedules for the selected date
        const relevantSchedules = schedules.filter(s => {
            // Match exact date from backend
            const scheduleDate = s.date ? s.date.split('T')[0] : null;
            return scheduleDate === selectedDateStr && s.is_available;
        });

        console.log('Selected date:', selectedDateStr);
        console.log('Matching schedules:', relevantSchedules.length);

        const slots: string[] = [];

        relevantSchedules.forEach(schedule => {
            // Parse start and end times
            const startHour = parseInt(schedule.start_time.split(':')[0]);
            const endHour = parseInt(schedule.end_time.split(':')[0]);

            // Generate hourly slots
            for (let hour = startHour; hour < endHour; hour++) {
                const timeStr = `${hour.toString().padStart(2, '0')}:00`;
                const displayTime = new Date(`2000-01-01T${timeStr}`).toLocaleTimeString('en-US', {
                    hour: '2-digit', minute: '2-digit', hour12: true
                });
                if (!slots.includes(displayTime)) {
                    slots.push(displayTime);
                }
            }
        });

        // Sort slots chronologically
        slots.sort((a, b) => {
            return new Date(`2000-01-01 ${a}`).getTime() - new Date(`2000-01-01 ${b}`).getTime();
        });

        console.log('Generated time slots:', slots);
        setAvailableSlots(slots);
    };

    const handleBook = async () => {
        setLoading(true);
        setError('');
        try {
            // Combine date and time
            // selectedDate is "2024-01-25", selectedTime is "09:00 AM"
            // Convert "09:00 AM" back to "HH:mm" for backend?
            // Actually PaymentController expects ISO String.
            const timeParts = selectedTime.match(/(\d+):(\d+) (AM|PM)/);
            if (!timeParts) throw new Error("Invalid time format");

            let hour = parseInt(timeParts[1]);
            if (timeParts[3] === 'PM' && hour < 12) hour += 12;
            if (timeParts[3] === 'AM' && hour === 12) hour = 0;

            const timeStr = `${hour.toString().padStart(2, '0')}:${timeParts[2]}:00`;
            const dateTimeString = `${selectedDate}T${timeStr}`;
            const scheduledAt = new Date(dateTimeString).toISOString();

            // Call Payment API
            const response = await api.post('/payment/initiate', {
                mentor_id: id, // URL param is Mentor ID
                scheduled_at: scheduledAt,
                duration_minutes: 60,
                notes: topic
            });

            if (response.payment_url) {
                // Redirect to ToyyibPay
                window.location.href = response.payment_url;
            } else {
                throw new Error('No payment URL received');
            }
        } catch (err: any) {
            console.error(err);
            setError(err.response?.data?.message || 'Failed to initiate payment. Please try again.');
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div className="max-w-md w-full bg-white rounded-xl shadow-lg overflow-hidden">
                {/* Header */}
                <div className="bg-indigo-600 px-6 py-4 flex items-center">
                    <button
                        onClick={() => window.history.back()}
                        className="text-white/80 hover:text-white mr-4"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </button>
                    <h1 className="text-xl font-bold text-white">Book Session</h1>
                </div>

                {step === 1 ? (
                    <div className="p-6">
                        <div className="space-y-6">
                            {/* Error Message */}
                            {error && (
                                <div className="p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-200">
                                    {error}
                                </div>
                            )}

                            {/* Date Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Select Date
                                </label>
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
                            </div>

                            {/* Time Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Select Time
                                </label>
                                <div className="grid grid-cols-3 gap-2">
                                    {availableSlots.length === 0 ? (
                                        <p className="col-span-3 text-sm text-gray-500 text-center py-4 bg-gray-50 rounded-lg">
                                            {selectedDate ? 'No available slots for this date' : 'Please select a date first'}
                                        </p>
                                    ) : (
                                        availableSlots.map((slot) => (
                                            <button
                                                key={slot}
                                                onClick={() => setSelectedTime(slot)}
                                                className={`px-2 py-2 text-sm rounded-lg border transition ${selectedTime === slot
                                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                                    : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-300'
                                                    }`}
                                            >
                                                {slot}
                                            </button>
                                        ))
                                    )}
                                </div>
                            </div>

                            {/* Topic */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Discussion Topic
                                </label>
                                <textarea
                                    rows={3}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
                                    placeholder="What would you like to discuss?"
                                    value={topic}
                                    onChange={(e) => setTopic(e.target.value)}
                                />
                            </div>

                            {/* Summary */}
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <div className="flex justify-between items-center text-sm mb-2">
                                    <span className="text-gray-600">Rate per hour</span>
                                    <span className="font-semibold">RM 50</span>
                                </div>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-gray-600">Duration</span>
                                    <span className="font-semibold">60 mins</span>
                                </div>
                            </div>

                            <button
                                onClick={handleBook}
                                disabled={!selectedDate || !selectedTime || loading}
                                className="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
                            >
                                {loading ? (
                                    <>
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Processing...
                                    </>
                                ) : (
                                    <>
                                        <CreditCard className="w-4 h-4 mr-2" />
                                        Pay & Confirm Booking
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                ) : null}
            </div>
        </div>
    );
}
