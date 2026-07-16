<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use Illuminate\Database\Seeder;

class AircraftSeeder extends Seeder
{
    public function run(): void
    {
        $fleet = [
            // Geniş gövde — iç hatta SADECE IST-Ankara/İzmir/Antalya (3x/gün) + uzun menzilli dış hat
            ['model' => 'B777-300ER', 'code' => 'G', 'body_type' => 'wide',   'total_capacity' => 349, 'business_seats' => 42, 'premium_economy_seats' => 24, 'economy_seats' => 283, 'count' => 10],
            ['model' => 'A330-300',   'code' => 'M', 'body_type' => 'wide',   'total_capacity' => 289, 'business_seats' => 30, 'premium_economy_seats' => 21, 'economy_seats' => 238, 'count' => 9],
            ['model' => 'B787-9',     'code' => 'D', 'body_type' => 'wide',   'total_capacity' => 300, 'business_seats' => 30, 'premium_economy_seats' => 21, 'economy_seats' => 249, 'count' => 8],
            ['model' => 'A350-900',   'code' => 'X', 'body_type' => 'wide',   'total_capacity' => 329, 'business_seats' => 32, 'premium_economy_seats' => 24, 'economy_seats' => 273, 'count' => 8],

            // Dar gövde — fiziksel business var, iç hatta satılmaz (kural FlightService'te), dış hatta business+economy
            ['model' => 'A321neo',    'code' => 'R', 'body_type' => 'narrow', 'total_capacity' => 182, 'business_seats' => 20, 'premium_economy_seats' => 0,  'economy_seats' => 162, 'count' => 15],
            ['model' => 'A320neo',    'code' => 'S', 'body_type' => 'narrow', 'total_capacity' => 168, 'business_seats' => 16, 'premium_economy_seats' => 0,  'economy_seats' => 152, 'count' => 8],
            ['model' => 'B737-800',   'code' => 'B', 'body_type' => 'narrow', 'total_capacity' => 151, 'business_seats' => 16, 'premium_economy_seats' => 0,  'economy_seats' => 135, 'count' => 11],
            ['model' => 'B737 MAX 8', 'code' => 'N', 'body_type' => 'narrow', 'total_capacity' => 151, 'business_seats' => 16, 'premium_economy_seats' => 0,  'economy_seats' => 135, 'count' => 8],
        ];

        $total = 0;

        foreach ($fleet as $type) {
            for ($i = 1; $i <= $type['count']; $i++) {
                Aircraft::create([
                    'tail_number'            => 'TC-DH' . $type['code'] . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'model'                  => $type['model'],
                    'body_type'              => $type['body_type'],
                    'total_capacity'         => $type['total_capacity'],
                    'business_seats'         => $type['business_seats'],
                    'premium_economy_seats'  => $type['premium_economy_seats'],
                    'economy_seats'          => $type['economy_seats'],
                ]);
                $total++;
            }
        }

        $this->command->info("Toplam {$total} uçak eklendi.");
    }
}
