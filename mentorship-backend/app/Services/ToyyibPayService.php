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
        // Use environment configuration
        $this->url = env('TOYYIBPAY_URL', 'https://dev.toyyibpay.com'); 
        $this->secretKey = env('TOYYIBPAY_SECRET_KEY');
        $this->categoryCode = env('TOYYIBPAY_CATEGORY_CODE');
        
        // Validate configuration
        if (!$this->secretKey || !$this->categoryCode) {
            Log::warning('ToyyibPay configuration incomplete. Check TOYYIBPAY_SECRET_KEY and TOYYIBPAY_CATEGORY_CODE in .env file');
        }
    }

    public function createBill($title, $description, $amount, $refId, $email, $name, $phone)
    {
        // Format amount to 2 decimal places (ToyyibPay expects amount in cents/sen)
        $amountInCents = intval($amount * 100);
        
        $payload = [
            'userSecretKey' => $this->secretKey,
            'categoryCode' => $this->categoryCode,
            'billName' => $title,
            'billDescription' => $description,
            'billPriceSetting' => 1,
            'billPayorInfo' => 1,
            'billAmount' => $amountInCents,
            'billReturnUrl' => route('api.payment.return'),
            'billCallbackUrl' => route('api.payment.callback'),
            'billExternalReferenceNo' => (string)$refId,
            'billTo' => $name,
            'billEmail' => $email,
            'billPhone' => $phone,
            'billSplitPayment' => 0,
            'billPaymentChannel' => '0',
            'billContentEmail' => 'Thank you for booking your mentorship session!',
            'billChargeToCustomer' => 1,
        ];

        try {
            Log::info("ToyyibPay Create Bill Request", [
                'payload' => $payload,
                'url' => $this->url . '/index.php/api/createBill'
            ]);
            
            $response = Http::asForm()->post($this->url . '/index.php/api/createBill', $payload);
            
            Log::info("ToyyibPay Create Bill Response", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle array response: [{"BillCode":"xyz123"}]
                if (is_array($data) && isset($data[0]['BillCode'])) {
                    return $data[0]['BillCode'];
                }
                
                // Handle direct object response: {"BillCode":"xyz123"}
                if (is_array($data) && isset($data['BillCode'])) {
                    return $data['BillCode'];
                }
                
                Log::error("ToyyibPay: Unexpected response format", ['data' => $data]);
                return null;
            }
            
            Log::error("ToyyibPay: API request failed", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
            
        } catch (\Exception $e) {
            Log::error("ToyyibPay Exception: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
