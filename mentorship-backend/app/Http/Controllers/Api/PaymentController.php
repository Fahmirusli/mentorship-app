<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Mentorship;
use App\Models\Schedule;
use App\Services\ToyyibPayService;
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
                'duration_minutes' => 'required|integer|min:15|max:180',
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

            // 2. Calculate Fee
            $mentorProfile = $mentorship->mentor->mentorProfile;
            $fee = $mentorProfile ? ($mentorProfile->hourly_rate ?? 50) : 50;

            // Adjust fee based on duration (proportional to 60 minutes)
            $adjustedFee = ($fee / 60) * $validated['duration_minutes'];
            $adjustedFee = round($adjustedFee, 2);

            // 3. Mark schedule slot as booked
            $scheduledAt = Carbon::parse($validated['scheduled_at']);
            $scheduleDate = $scheduledAt->format('Y-m-d');
            $scheduleTime = $scheduledAt->format('H:i:s');
            
            $schedule = Schedule::where('mentor_id', $mentorship->mentor_id)
                ->where('date', $scheduleDate)
                ->where('start_time', $scheduleTime)
                ->where('is_available', true)
                ->first();

            // 4. Create Appointment with PENDING PAYMENT status
            $appointment = Appointment::create([
                'mentorship_id' => $mentorship->id,
                'mentor_id' => $mentorship->mentor_id,
                'mentee_id' => $mentorship->mentee_id,
                'scheduled_at' => $validated['scheduled_at'],
                'duration_minutes' => $validated['duration_minutes'],
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
                $appointment->update([
                    'payment_status' => 'paid',
                    'status' => 'scheduled'
                ]);

                // Update schedule booked_slots
                $schedule = Schedule::where('mentor_id', $appointment->mentor_id)
                    ->where('date', $appointment->scheduled_at->format('Y-m-d'))
                    ->where('start_time', $appointment->scheduled_at->format('H:i:s'))
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
                // Payment failed or pending
                $appointment->update([
                    'payment_status' => $status == 2 ? 'pending' : 'failed',
                    'notes' => $appointment->notes . "\nPayment failed: " . $reason
                ]);

                // Release the schedule slot if payment failed
                if ($status == 3) {
                    $schedule = Schedule::where('mentor_id', $appointment->mentor_id)
                        ->where('date', $appointment->scheduled_at->format('Y-m-d'))
                        ->where('start_time', $appointment->scheduled_at->format('H:i:s'))
                        ->first();
                    
                    if ($schedule && $schedule->booked_slots > 0) {
                        $schedule->decrement('booked_slots');
                        $schedule->update(['is_available' => true]);
                    }
                }

                Log::warning("Payment failed/pending for appointment: " . $appointment->id . ", Status: " . $status);
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
