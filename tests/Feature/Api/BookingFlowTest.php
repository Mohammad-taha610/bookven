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

    public function test_booked_slot_excluded_from_court_slots_list(): void
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
        $this->assertNull($rows->firstWhere('id', $slot->id));
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

    public function test_user_can_create_multi_slot_booking_in_one_request(): void
    {
        $court = Court::factory()->create();
        $dow = now()->dayOfWeek;
        $slotA = Slot::factory()->create([
            'court_id' => $court->id,
            'day_of_week' => $dow,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);
        $slotB = Slot::factory()->create([
            'court_id' => $court->id,
            'day_of_week' => $dow,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
        ]);
        $user = $this->userWithAccessToCourt($court);
        $date = now()->toDateString();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/bookings', [
            'court_id' => $court->id,
            'slot_ids' => [$slotB->id, $slotA->id],
            'date' => $date,
            'advance_amount' => 100,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('bookings', $data);
        $this->assertArrayHasKey('booking_code', $data);
        $this->assertSame([$slotA->id, $slotB->id], $data['slot_ids']);
        $this->assertCount(2, $data['booking_ids']);
        $this->assertEqualsWithDelta(100.0, (float) $data['advance_amount'], 0.02);
        $this->assertSame($court->id, $data['court_id']);

        $ymd = str_replace('-', '', $date);
        $this->assertStringStartsWith('BV'.$ymd.'-'.$slotA->id.'-'.$slotB->id.'-', $data['booking_code']);

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bookings')->assertOk();
        $items = $list->json('data');
        $this->assertCount(1, $items);
        $this->assertSame($data['booking_code'], $items[0]['booking_code']);
        $this->assertSame([$slotA->id, $slotB->id], $items[0]['slot_ids']);
    }

    public function test_multi_slot_rejects_slot_id_and_slot_ids_together(): void
    {
        [$court, $slot] = $this->seedCourtWithSlot();
        $user = $this->userWithAccessToCourt($court);
        $date = now()->toDateString();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/bookings', [
            'court_id' => $court->id,
            'slot_id' => $slot->id,
            'slot_ids' => [$slot->id],
            'date' => $date,
        ])->assertStatus(422);
    }

    public function test_cancelled_clubbed_booking_still_appears_in_api(): void
    {
        $court = Court::factory()->create();
        $dow = now()->dayOfWeek;
        $slotA = Slot::factory()->create([
            'court_id' => $court->id,
            'day_of_week' => $dow,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);
        $slotB = Slot::factory()->create([
            'court_id' => $court->id,
            'day_of_week' => $dow,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
        ]);
        $user = $this->userWithAccessToCourt($court);
        $date = now()->toDateString();

        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/bookings', [
            'court_id' => $court->id,
            'slot_ids' => [$slotA->id, $slotB->id],
            'date' => $date,
        ])->assertStatus(201);

        $bookingId = $create->json('data.id');
        $code = $create->json('data.booking_code');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/bookings/{$bookingId}/cancel")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Booking cancelled.');

        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bookings')->assertOk();
        $item = collect($list->json('data'))->firstWhere('booking_code', $code);
        $this->assertNotNull($item);
        $this->assertSame(BookingStatus::Cancelled->value, $item['status']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/bookings/{$bookingId}")
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value)
            ->assertJsonPath('data.booking_code', $code);
    }
}
