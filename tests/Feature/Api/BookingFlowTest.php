<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Models\Court;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCourtWithSlot(): array
    {
        $court = Court::factory()->create();
        $slot = Slot::factory()->create([
            'court_id' => $court->id,
            'day_of_week' => now()->dayOfWeek,
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
        ]);

        return [$court, $slot];
    }

    protected function userWithAccessToCourt(Court $court): User
    {
        $user = User::factory()->create();
        $user->branches()->attach($court->branch_id);

        return $user;
    }

    public function test_user_can_create_and_confirm_booking_when_fully_paid(): void
    {
        [$court, $slot] = $this->seedCourtWithSlot();
        $user = $this->userWithAccessToCourt($court);

        $date = now()->toDateString();

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/bookings', [
            'court_id' => $court->id,
            'slot_id' => $slot->id,
            'date' => $date,
        ]);

        $create->assertStatus(201)->assertJsonPath('success', true);
        $bookingId = $create->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/bookings/{$bookingId}/confirm", [
                'payment_method' => 'Online',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Confirmed->value);
    }

    public function test_slot_marked_unavailable_after_booking(): void
    {
        [$court, $slot] = $this->seedCourtWithSlot();
        $user = $this->userWithAccessToCourt($court);
        $date = now()->toDateString();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/bookings', [
            'court_id' => $court->id,
            'slot_id' => $slot->id,
            'date' => $date,
        ])->assertStatus(201);

        $slots = $this->actingAs($user, 'sanctum')->getJson("/api/v1/courts/{$court->id}/slots?date=".$date);
        $slots->assertOk();
        $rows = collect($slots->json('data.slots'));
        $mine = $rows->firstWhere('id', $slot->id);
        $this->assertNotNull($mine);
        $this->assertTrue($mine['is_booked']);
    }

    public function test_booking_rejected_without_branch_assignment(): void
    {
        [$court, $slot] = $this->seedCourtWithSlot();
        $user = User::factory()->create();
        $date = now()->toDateString();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/bookings', [
            'court_id' => $court->id,
            'slot_id' => $slot->id,
            'date' => $date,
        ])->assertStatus(403);
    }
}
