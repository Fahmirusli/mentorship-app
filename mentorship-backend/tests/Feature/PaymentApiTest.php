<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    /**
     * Test that the API rejects unauthenticated payment initiation.
     */
    public function test_rejects_unauthenticated_payment(): void
    {
        $response = $this->postJson('/api/payment/initiate', [
            'mentor_id' => 1,
            'scheduled_at' => '2030-01-01 10:00:00',
        ]);

        $response->assertStatus(401);
    }
}
