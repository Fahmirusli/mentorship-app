<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MentorApiTest extends TestCase
{
    /**
     * Test that the API can list mentors.
     */
    public function test_can_list_mentors(): void
    {
        $response = $this->getJson('/api/mentors');

        $response->assertStatus(200);
    }
}
