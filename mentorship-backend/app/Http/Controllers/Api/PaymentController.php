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
                    'goals' => 'Session Booking'
                ]
            );
            // Load relations
            $mentorship->load(['mentor.mentorProfile', 'mentee']);
        }

        // 2. Calculate Fee
        $mentorProfile = $mentorship->mentor->mentorProfile;
        $fee = $mentorProfile ? ($mentorProfile->hourly_rate ?? 50) : 50;

        // 3. Create Appointment with PENDING PAYMENT status
        $appointment = Appointment::create([
            'mentorship_id' => $mentorship->id,
            'mentor_id' => $mentorship->mentor_id,
            'mentee_id' => $mentorship->mentee_id,
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'status' => 'pending_payment', // Custom status
            'payment_status' => 'pending',
            'fee' => $fee,
            'notes' => $request->notes,
        ]);

        // 4. Create Bill with ToyyibPay
        $billCode = $this->toyyibPay->createBill(
            "Mentorship Session with " . $mentorship->mentor->name,
            "Session on " . Carbon::parse($validated['scheduled_at'])->format('d M Y, h:i A'),
            $fee, // Amount
            $appointment->id, // Reference ID
            $user->email,
            $user->name,
            $user->phone ?? '0123456789'
        );

        if ($billCode) {
             $appointment->update(['bill_code' => $billCode]);
             
             // Sandbox URL or Prod URL depending on Env
             $baseUrl = env('TOYYIBPAY_URL', 'https://dev.toyyibpay.com');
             
             return response()->json([
                 'message' => 'Payment initiated',
                 'payment_url' => $baseUrl . '/' . $billCode,
                 'appointment_id' => $appointment->id
             ]);
        }

        return response()->json(['message' => 'Failed to initiate payment gateway'], 500);
    }

    public function callback(Request $request)
    {
        // ToyyibPay sends data like: refno, status, billcode, order_id, amount
        $refNo = $request->refno; // This is the transaction ID
        $status = $request->status; // 1 = success, 0 = fail
        $billCode = $request->billcode;
        $appointmentId = $request->order_id; // If we mapped order_id to Ref ID when creating bill

        Log::info("ToyyibPay Callback: ", $request->all());

        // Find appointment by bill code or ID
        $appointment = Appointment::where('bill_code', $billCode)->first();

        if ($appointment) {
            if ($status == '1') {
                $appointment->update([
                    'payment_status' => 'paid',
                    'status' => 'scheduled' // Confirm the appointment
                ]);

                // Log Transaction
                \App\Models\Transaction::firstOrCreate(
                    ['bill_code' => $billCode], 
                    [
                        'user_id' => $appointment->mentee_id,
                        'appointment_id' => $appointment->id,
                        'amount' => $appointment->fee,
                        'status' => 'paid',
                        'payment_provider' => 'toyyibpay',
                        'payment_metadata' => $request->all(),
                        'paid_at' => now(),
                    ]
                );
            } else {
                $appointment->update([
                    'payment_status' => 'failed'
                ]);
            }
        }
        
        return response('OK'); // ToyyibPay expects 200 OK
    }

    public function returnPage(Request $request)
    {
        // When user is redirected back
        // status_id=1 means success
        $statusId = $request->status_id;
        $billCode = $request->billcode;
        
        $msg = $statusId == 1 ? "Payment Successful!" : "Payment Failed.";
        
        // In a real app, redirect to a frontend page
        // return redirect("http://localhost:3000/mentee/appointments?status=" . ($statusId == 1 ? 'success' : 'failed'));
        
        return redirect(env('FRONTEND_URL', 'http://localhost:3000') . "/mentee/appointments?status=" . ($statusId == 1 ? 'success' : 'failed'));
    }
}
