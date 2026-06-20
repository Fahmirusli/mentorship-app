<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Mentorship;
use App\Models\Schedule;
use App\Services\ToyyibPayService;
use App\Services\GoogleMeetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $toyyibPay;

    public function __construct(ToyyibPayService $toyyibPay)
    {
        $this->toyyibPay = $toyyibPay;
    }

    public function initiate(Request $request)
    {
        try {
            // 1. Validate Request 
            $validated = $request->validate([
                'mentorship_id' => 'nullable|exists:mentorships,id',
                'mentor_id' => 'required_without:mentorship_id|exists:users,id',
                'scheduled_at' => 'required|date|after:now',
                'notes' => 'nullable|string',
            ]);

            $user = $request->user();
            $mentorship = null;

            if ($request->filled('mentorship_id')) {
                $mentorship = Mentorship::with(['mentor.mentorProfile', 'mentee'])->findOrFail($request->mentorship_id);
            } else {
                // Find existing or create new mentorship
                $mentorship = Mentorship::firstOrCreate(
                    [
                        'mentee_id' => $user->id,
                        'mentor_id' => $request->mentor_id
                    ],
                    [
                        'status' => 'active',
                        'start_date' => now(),
                        'goal' => 'Session Booking'
                    ]
                );
                // Load relations
                $mentorship->load(['mentor.mentorProfile', 'mentee']);
            }

            // 2. Resolve date/time in app timezone to avoid UTC shifts from clients
            $scheduledAt = Carbon::parse($validated['scheduled_at'], config('app.timezone'))
                ->setTimezone(config('app.timezone'));
            $scheduleDate = $scheduledAt->format('Y-m-d');
            $scheduleTime = $scheduledAt->format('H:i:s');

            // Find the availability block that *contains* the selected time.
            // Mentors create a block e.g. 09:00–17:00; mentees pick any hour within it (09:00, 10:00 …).
            // So we need: block.start_time <= selected_time < block.end_time
            $schedule = Schedule::where('mentor_id', $mentorship->mentor_id)
                ->where('date', $scheduleDate)
                ->whereRaw('TIME(start_time) <= ?', [$scheduleTime])
                ->whereRaw('TIME(end_time) > ?', [$scheduleTime])
                ->where('is_available', true)
                ->first();

            if (!$schedule) {
                return response()->json([
                    'message' => 'Selected slot is no longer available. Please choose another time.'
                ], 422);
            }

            $appointmentStart = $scheduledAt->copy();
            // Extract just the time part from end_time (which may be stored as a full datetime)
            $scheduleDate4Appt = $schedule->date->format('Y-m-d');
            $endTimeOnly = Carbon::parse($schedule->end_time)->format('H:i:s');
            $appointmentEnd = Carbon::createFromFormat('Y-m-d H:i:s', $scheduleDate4Appt . ' ' . $endTimeOnly, config('app.timezone'));

            $conflict = Appointment::query()
                ->where(function ($query) use ($mentorship) {
                    $query->where('mentor_id', $mentorship->mentor_id)
                        ->orWhereHas('mentorship', function ($mentorshipQuery) use ($mentorship) {
                            $mentorshipQuery->where('mentor_id', $mentorship->mentor_id);
                        });
                })
                ->whereIn('status', ['scheduled', 'pending_payment', 'rescheduled'])
                ->where(function ($query) use ($appointmentStart, $appointmentEnd) {
                    $query->whereBetween('scheduled_at', [$appointmentStart, $appointmentEnd])
                        ->orWhereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?', [$appointmentStart, $appointmentEnd])
                        ->orWhere(function ($sub) use ($appointmentStart, $appointmentEnd) {
                            $sub->where('scheduled_at', '<=', $appointmentStart)
                                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) >= ?', [$appointmentEnd]);
                        });
                })
                ->exists();

            if ($conflict) {
                if ($schedule->is_available) {
                    $schedule->update(['is_available' => false]);
                }

                return response()->json([
                    'message' => 'Selected slot is no longer available. Please choose another time.'
                ], 422);
            }

            // 3. Calculate Fee and Duration from the matched schedule block
            $mentorProfile = $mentorship->mentor->mentorProfile;
            $baseSessionFee = (float) ($schedule->fee ?? ($mentorProfile->hourly_rate ?? 50));

            // Duration is the full length of the mentor's schedule block (not split into sub-hours)
            // start_time/end_time may be stored as full datetimes — extract only the time component
            $scheduleDate4Dur = $schedule->date->format('Y-m-d');
            $startTimeOnly = Carbon::parse($schedule->start_time)->format('H:i:s');
            $endTimeOnly2  = Carbon::parse($schedule->end_time)->format('H:i:s');
            $scheduleStart = Carbon::createFromFormat('Y-m-d H:i:s', $scheduleDate4Dur . ' ' . $startTimeOnly, config('app.timezone'));
            $scheduleEnd   = Carbon::createFromFormat('Y-m-d H:i:s', $scheduleDate4Dur . ' ' . $endTimeOnly2,  config('app.timezone'));
            $durationMinutes = (int) $scheduleStart->diffInMinutes($scheduleEnd);
            if ($durationMinutes < 15) {
                $durationMinutes = 60; // safe fallback
            }

            // Fee is the flat fee for the entire block (exactly what mentor entered)
            $adjustedFee = $baseSessionFee;

            // 4. Create Appointment with PENDING PAYMENT status
            $appointment = Appointment::create([
                'mentorship_id' => $mentorship->id,
                'mentor_id' => $mentorship->mentor_id,
                'mentee_id' => $mentorship->mentee_id,
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'duration_minutes' => $durationMinutes,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'fee' => $adjustedFee,
                'notes' => $request->notes,
            ]);

            // 5. Create Bill with ToyyibPay
            // Note: billName has 30 char limit
            $billCode = $this->toyyibPay->createBill(
                "Session #" . $appointment->id,
                "Mentorship with " . $mentorship->mentor->name . " on " . $scheduledAt->format('d M Y, h:i A'),
                $adjustedFee,
                $appointment->id,
                $user->email,
                $user->name,
                $user->phone ?? '0123456789'
            );

            if ($billCode) {
                $appointment->update(['bill_code' => $billCode]);
                
                // Mark schedule as unavailable temporarily
                if ($schedule) {
                    $schedule->update(['is_available' => false]);
                }
                
                // Get payment URL
                $baseUrl = env('TOYYIBPAY_URL', 'https://dev.toyyibpay.com');
                
                return response()->json([
                    'message' => 'Payment initiated successfully',
                    'payment_url' => $baseUrl . '/' . $billCode,
                    'appointment_id' => $appointment->id,
                    'bill_code' => $billCode,
                    'amount' => $adjustedFee
                ]);
            }

            // If bill creation failed, cleanup
            $appointment->delete();
            
            return response()->json([
                'message' => 'Failed to initiate payment gateway. Please try again.',
                'error' => 'ToyyibPay service unavailable'
            ], 500);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment Initiation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while processing your payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            // ToyyibPay sends: refno, status, billcode, order_id, amount, etc.
            $refNo = $request->input('refno');
            $status = $request->input('status'); // 1 = success, 2 = pending, 3 = failed
            $billCode = $request->input('billcode');
            $amount = $request->input('amount');
            $reason = $request->input('reason', '');

            Log::info("ToyyibPay Callback Received", $request->all());

            // Find appointment by bill code
            $appointment = Appointment::where('bill_code', $billCode)->first();

            if (!$appointment) {
                Log::error("ToyyibPay Callback: Appointment not found for bill code: " . $billCode);
                return response('Appointment not found', 404);
            }

            if ($status == 1) {
                // Payment successful
                // Generate Google Meet link
                $meetLink = null;
                try {
                    $meetService = app(GoogleMeetService::class);
                    $meetLink = $meetService->generateSimpleMeetLink();
                } catch (\Exception $e) {
                    Log::warning('Failed to generate Meet link: ' . $e->getMessage());
                }

                $appointment->update([
                    'payment_status' => 'paid',
                    'status' => 'scheduled',
                    'meeting_link' => $meetLink
                ]);

                // Update schedule booked_slots
                $apptTime = $appointment->scheduled_at->format('H:i:s');
                $schedule = Schedule::where('mentor_id', $appointment->mentor_id)
                    ->where('date', $appointment->scheduled_at->format('Y-m-d'))
                    ->where('start_time', '<=', $apptTime)
                    ->where('end_time', '>', $apptTime)
                    ->first();
                
                if ($schedule) {
                    $schedule->increment('booked_slots');
                    if ($schedule->booked_slots >= $schedule->total_slots) {
                        $schedule->update(['is_available' => false]);
                    }
                }

                // Create transaction record
                \App\Models\Transaction::firstOrCreate(
                    ['bill_code' => $billCode], 
                    [
                        'user_id' => $appointment->mentee_id,
                        'appointment_id' => $appointment->id,
                        'amount' => $appointment->fee,
                        'status' => 'paid',
                        'payment_provider' => 'toyyibpay',
                        'payment_metadata' => json_encode($request->all()),
                        'paid_at' => now(),
                    ]
                );

                Log::info("Payment successful for appointment: " . $appointment->id);
                
            } else {
                // Payment failed (status 3) or pending/cancelled (status 2)
                // Restore the schedule slot so others can book it
                $apptTime = $appointment->scheduled_at->format('H:i:s');
                $schedule = Schedule::where('mentor_id', $appointment->mentor_id)
                    ->where('date', $appointment->scheduled_at->format('Y-m-d'))
                    ->where('start_time', '<=', $apptTime)
                    ->where('end_time', '>', $apptTime)
                    ->first();

                if ($schedule) {
                    $schedule->update(['is_available' => true]);
                }

                if ($status == 3) {
                    // Hard failure — delete the appointment so no ghost booking remains
                    Log::warning("Payment failed — deleting appointment: " . $appointment->id . ", Reason: " . $reason);
                    $appointment->delete();
                } else {
                    // Status 2 = pending (user may still complete payment)
                    $appointment->update([
                        'payment_status' => 'pending',
                        'notes' => ($appointment->notes ?? '') . "\nPayment pending: " . $reason,
                    ]);
                    Log::warning("Payment pending for appointment: " . $appointment->id);
                }
            }
            
            return response('OK', 200);
            
        } catch (\Exception $e) {
            Log::error('Payment Callback Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response('Error processing callback', 500);
        }
    }

    public function returnPage(Request $request)
    {
        try {
            // When user is redirected back from ToyyibPay
            // status_id: 1 = success, 2 = pending, 3 = failed
            $statusId = $request->input('status_id');
            $billCode = $request->input('billcode');
            $transactionId = $request->input('transaction_id');
            $orderId = $request->input('order_id');
            
            Log::info("ToyyibPay Return Page", $request->all());
            
            // Map status
            $statusMap = [
                1 => 'success',
                2 => 'pending',
                3 => 'failed'
            ];
            
            $status = $statusMap[$statusId] ?? 'unknown';
            
            // Redirect to frontend
            $frontendUrl = env('FRONTEND_URL', 'https://uplifts.dev');
            $redirectUrl = $frontendUrl . "/mentee/schedule?payment=" . $status;
            
            if ($billCode) {
                $redirectUrl .= "&bill=" . $billCode;
            }
            
            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('Payment Return Error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'https://uplifts.dev') . '/mentee/schedule?payment=error');
        }
    }
}
