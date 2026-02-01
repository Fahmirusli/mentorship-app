<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
// use App\Mail\MenteeInvitation; // We won't create a real mailable yet to keep it simple

class InvitationController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Logic to send email would go here
        // Mail::to($request->email)->send(new MenteeInvitation($request->user()));

        // For now, simple success response
        return response()->json([
            'message' => 'Invitation sent successfully to ' . $request->email,
        ]);
    }
}
