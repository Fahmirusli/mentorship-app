<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToyyibPayService
{
    private $url;
    private $secretKey;
    private $categoryCode;

    public function __construct()
    {
        // Use Sandbox by default if not specified
        $this->url = env('TOYYIBPAY_URL', 'https://dev.toyyibpay.com'); 
        $this->secretKey = env('TOYYIBPAY_SECRET_KEY', 'default-secret-key-for-dev');
        $this->categoryCode = env('TOYYIBPAY_CATEGORY_CODE', 'default-category-code');
    }

    public function createBill($title, $description, $amount, $refId, $email, $name, $phone)
    {
        $payload = [
            'userSecretKey' => $this->secretKey,
            'categoryCode' => $this->categoryCode,
            'billName' => $title,
            'billDescription' => $description,
            'billPriceSetting' => 1,
            'billPayorInfo' => 1,
            'billAmount' => $amount * 100, // ToyyibPay uses cents usually
            'billReturnUrl' => route('api.payment.return'), // We will create this route
            'billCallbackUrl' => route('api.payment.callback'),
            'billExternalReferenceNo' => $refId,
            'billTo' => $name,
            'billEmail' => $email,
            'billPhone' => $phone,
            'billSplitPayment' => 0,
            'billPaymentChannel' => '0',
            'billContentEmail' => 'Thank you for booking your mentorship session!',
            'billChargeToCustomer' => 1,
        ];

        // Mock for default key to prevent crash if not configured
        if (str_starts_with($this->secretKey, 'default-')) {
            Log::info("ToyyibPay: Using Mock Bill Code for testing.");
            return 'BILL-' . uniqid();
        }

        try {
            Log::info("ToyyibPay Create Bill Payload: ", $payload);
            
            $response = Http::asForm()->post($this->url . '/index.php/api/createBill', $payload);
            
            Log::info("ToyyibPay Response: " . $response->body());

            if ($response->successful()) {
                // Returns string (BillCode) or JSON object depending on version? 
                // Documentation says: [{"BillCode":"..."}]
                $data = $response->json();
                
                if (is_array($data) && isset($data[0]['BillCode'])) {
                    return $data[0]['BillCode'];
                }
                
                // Fallback for some error cases
                return null;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error("ToyyibPay Error: " . $e->getMessage());
            return null;
        }
    }

    public function getBillTransactions($billCode)
    {
        try {
            $response = Http::get($this->url . '/index.php/api/getBillTransactions', [
                'billCode' => $billCode,
                'billPaymentStatus' => 1 // Only successful
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("ToyyibPay Transaction Error: " . $e->getMessage());
            return [];
        }
    }
}
