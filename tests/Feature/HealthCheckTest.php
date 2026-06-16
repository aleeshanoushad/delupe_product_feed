<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_health_status(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'database' => 'connected',
            ]);
    }
}
