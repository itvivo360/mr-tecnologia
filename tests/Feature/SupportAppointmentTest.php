<?php

namespace Tests\Feature;

use App\Models\SupportAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Lunes 08:00 → hay agenda de 9 a 18 con bloques de 60 min.
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Santiago'));
        config(['shop.support.slot_capacity' => 1, 'shop.support.min_notice_hours' => 2]);
    }

    private function payload(string $start): array
    {
        return ['starts_at' => $start, 'topic' => 'repair', 'description' => 'El equipo no enciende desde ayer.'];
    }

    public function test_lists_slots_respecting_notice(): void
    {
        $slots = $this->actingAs(User::factory()->create())
            ->getJson(route('support.slots', ['date' => '2026-10-12']))
            ->assertOk()
            ->json('slots');

        $this->assertCount(9, $slots);
        $this->assertFalse($slots[0]['available']); // 09:00 está a menos de 2 h
        $this->assertTrue($slots[1]['available']);  // 10:00
    }

    public function test_slot_capacity_is_enforced(): void
    {
        $start = '2026-10-13T10:00:00-03:00';

        $this->actingAs(User::factory()->create())->post(route('support.store'), $this->payload($start))->assertSessionHas('success');
        $this->actingAs(User::factory()->create())->post(route('support.store'), $this->payload($start))->assertSessionHasErrors('starts_at');

        $this->assertSame(1, SupportAppointment::count());
    }

    public function test_rejects_off_hours_and_weekends(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('support.store'), $this->payload('2026-10-13T10:30:00-03:00'))->assertSessionHasErrors('starts_at');
        $this->actingAs($user)->post(route('support.store'), $this->payload('2026-10-17T10:00:00-03:00'))->assertSessionHasErrors('starts_at'); // sábado

        $this->assertSame(0, SupportAppointment::count());
    }

    public function test_customer_can_cancel_with_notice(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('support.store'), $this->payload('2026-10-14T11:00:00-03:00'));
        $appointment = SupportAppointment::firstOrFail();

        $this->actingAs($user)->post(route('support.cancel', $appointment))->assertSessionHas('success');
        $this->assertSame('cancelled', $appointment->fresh()->status->value);
    }
}
