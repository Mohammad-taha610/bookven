<?php

namespace Database\Seeders;

use App\Enums\CourtType;
use App\Enums\IndoorFacilityKind;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Court;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $north = Branch::query()->updateOrCreate(
            ['name' => 'Northside Soccer Park'],
            [
                'address' => '100 Field Road',
                'phone' => '+15550001111',
                'opening_hours' => 'Daily 06:00–23:00',
            ]
        );

        $central = Branch::query()->updateOrCreate(
            ['name' => 'Central Indoor Arena'],
            [
                'address' => '22 Stadium Ave',
                'phone' => '+15550002222',
                'opening_hours' => 'Mon–Sun 07:00–22:00',
            ]
        );

        $player = User::query()->updateOrCreate(
            ['email' => 'player@bookven.test'],
            [
                'name' => 'Demo Player',
                'password' => 'password',
                'phone' => '+10000000002',
                'role' => UserRole::User,
            ]
        );
        $player->branches()->sync([$central->id]);

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@bookven.test'],
            [
                'name' => 'Branch Manager',
                'password' => 'password',
                'phone' => '+10000000003',
                'role' => UserRole::Manager,
            ]
        );
        $manager->branches()->sync([$north->id, $central->id]);

        User::query()->updateOrCreate(
            ['email' => 'admin@bookven.test'],
            [
                'name' => 'Venue Admin',
                'password' => 'password',
                'phone' => '+10000000004',
                'role' => UserRole::Admin,
            ]
        );

        $c1 = Court::query()->updateOrCreate(
            ['branch_id' => $central->id, 'name' => 'Court A'],
            [
                'type' => CourtType::Indoor,
                'indoor_facility_kind' => IndoorFacilityKind::Court,
                'capacity' => 14,
                'price_per_hour' => '45.00',
                'image_url' => null,
            ]
        );

        $c2 = Court::query()->updateOrCreate(
            ['branch_id' => $central->id, 'name' => 'Court B'],
            [
                'type' => CourtType::Indoor,
                'indoor_facility_kind' => IndoorFacilityKind::Court,
                'capacity' => 12,
                'price_per_hour' => '40.00',
                'image_url' => null,
            ]
        );

        $c3 = Court::query()->updateOrCreate(
            ['branch_id' => $central->id, 'name' => 'Net 1'],
            [
                'type' => CourtType::Indoor,
                'indoor_facility_kind' => IndoorFacilityKind::Net,
                'capacity' => 10,
                'price_per_hour' => '55.00',
                'image_url' => null,
            ]
        );

        foreach ([$c1, $c2, $c3] as $court) {
            Slot::query()->where('court_id', $court->id)->delete();
            for ($d = 0; $d <= 6; $d++) {
                Slot::query()->create([
                    'court_id' => $court->id,
                    'day_of_week' => $d,
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00',
                ]);
                Slot::query()->create([
                    'court_id' => $court->id,
                    'day_of_week' => $d,
                    'start_time' => '18:00:00',
                    'end_time' => '19:00:00',
                ]);
            }
        }
    }
}
