<?php

namespace App\Services;

use Resend;

class ResendMailService
{
    protected $resend;

    public function __construct()
    {
        $apiKey = env('RESEND_API_KEY');
        if (!$apiKey) {
            throw new \Exception('RESEND_API_KEY not configured');
        }
        $this->resend = Resend::client($apiKey);
    }

    public function send($to, $subject, $message)
    {
        try {
            $response = $this->resend->emails->send([
                'from' => env('MAIL_FROM_ADDRESS', 'noreply@uplifts.dev'),
                'to' => [$to],
                'subject' => $subject,
                'text' => $message,
            ]);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Resend Email Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
